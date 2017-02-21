<?php
/**
 * Signs releases on GitHub.
 */

require(__DIR__.'/../lib/api-core.php');

use Analog\Analog;
use GuzzleHttp\Client;
use GuzzleHttp\Promise;

Analog::handler(__DIR__.'/../logs/sign_releases.log');

// Validate auth token
$auth_token = $_GET['token'] ?? '';
if (empty($auth_token) || $auth_token !== Config::SIGN_AUTH_TOKEN) {
api_error('403', 'Unauthorized');
}

// Grab releases from GitHub
$client = new Client();
$releases = GitHub::call(
  'repos/%s/%s/releases',
  Config::ORG_NAME,
  Config::REPO_NAME
);

$files_to_sign = [];
$promises = [];
foreach ($releases as $release) {
  $files_in_release = [];
  $signed_files = [];

  foreach ($release->assets as $asset) {
    if (preg_match(Config::SIGN_FILE_TYPES, $asset->name)) {
      $files_in_release[] = $asset;
    } else if (Str::endsWith($asset->name, '.asc')) {
      $signed_files[str_replace('.asc', '', $asset->name)] = true;
    }
  }

  foreach ($files_in_release as $asset) {
    if (array_key_exists($asset->name, $signed_files)) {
      // File is already signed
      continue;
    }
    $download_path = tempnam(sys_get_temp_dir(), '');
    $download_handle = fopen($download_path, 'w');
    $files_to_sign[] = [
      'asset' => $asset,
      'download_handle' => $download_handle,
      'download_path' => $download_path,
      'release' => $release,
    ];
    $promises[] = $client->getAsync($asset->browser_download_url, [
      'sink' => $download_handle,
    ]);
  }
}

if (count($files_to_sign) === 0) {
  api_response('All releases have already been signed!');
}

// Download all the artifacts to be signed in parallel
$responses = Promise\unwrap($promises);

$output = "Signed:\n";
$promises = [];
$uri = new \Rize\UriTemplate\UriTemplate();
foreach ($files_to_sign as $file) {
  $signature = GPG::sign($file['download_path'], Config::GPG_RELEASE);
  unlink($file['download_path']);

  $upload_url = $uri->expand(
    $file['release']->upload_url,
    ['name' => $file['asset']->name.'.asc']
  );
  $promises[] = $client->postAsync($upload_url, [
    'body' => $signature,
    'headers' => [
      'Authorization' => 'token '.Config::GITHUB_TOKEN,
      'Content-Type' => 'application/pgp-signature',
    ],
  ]);
  $output .= $file['release']->tag_name.': '.$file['asset']->name."\n";
}

// Upload all the signature files in parallel
$responses = Promise\unwrap($promises);

api_response($output);
