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

$releases_to_sign = [];
$promises = [];
foreach ($releases as $release) {
  $tarball = null;
  $has_sig = false;
  foreach ($release->assets as $asset) {
    if (Str::endsWith($asset->name, '.tar.gz')) {
      $tarball = $asset;
    } else if (Str::endsWith($asset->name, '.tar.gz.asc')) {
      $has_sig = true;
      break;
    }
  }
  if ($has_sig || !$tarball) {
    // This release's tarball already has a signature, skip it
    continue;
  }

  $download_path = tempnam(sys_get_temp_dir(), '');
  $download_handle = fopen($download_path, 'w');
  $releases_to_sign[] = [
    'tarball' => $tarball,
    'download_handle' => $download_handle,
    'download_path' => $download_path,
    'release' => $release,
  ];
  $promises[] = $client->getAsync($tarball->browser_download_url, [
    'sink' => $download_handle,
  ]);
}

if (count($releases_to_sign) === 0) {
  api_response('All releases have already been signed!');
}

// Download all the artifacts to be signed in parallel
$responses = Promise\unwrap($promises);

$output = "Signed:\n";
$promises = [];
$uri = new \Rize\UriTemplate\UriTemplate();
foreach ($releases_to_sign as $release) {
  $signature = GPG::sign($release['download_path'], Config::GPG_RELEASE);
  unlink($release['download_path']);

  $upload_url = $uri->expand(
    $release['release']->upload_url,
    ['name' => $release['tarball']->name.'.asc']
  );
  $promises[] = $client->postAsync($upload_url, [
    'body' => $signature,
    'headers' => [
      'Authorization' => 'token '.Config::GITHUB_TOKEN,
      'Content-Type' => 'application/pgp-signature',
    ],
  ]);
  $output .= $release['release']->tag_name.': '.$release['tarball']->name."\n";
}

// Upload all the signature files in parallel
$responses = Promise\unwrap($promises);

api_response($output);
