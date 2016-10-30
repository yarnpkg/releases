<?php
/**
 * A CircleCI webhook that pulls buildartifacts from CircleCI into the local file
 * system.
 *
 * Unfortunately, CircleCI does not provide any way of authenticating webhook
 * calls (such as a secret authentication token). This means that ALL post data
 * needs to considered untrustworthy as *anyone* could call this webhook
 * pretending to be CircleCI. For this reason, we need to hit their API to load
 * the build information for realz.
 */

require(__DIR__.'/../lib/api-core.php');

use Analog\Analog;
use GuzzleHttp\Client;
use GuzzleHttp\Promise;

Analog::handler(__DIR__.'/../logs/archive_circleci.log');

const API_URL = 'https://circleci.com/api/v1.1/%s?circle-token=%s';

function call_circleci($uri) {
  // TODO use Guzzle here
  $url = sprintf(API_URL, $uri, Config::CIRCLECI_TOKEN);
  return json_decode(file_get_contents($url, false, stream_context_create([
    'http' => [
      'header' => 'Accept: application/json',
    ],
  ])));
}

function validate_build($build) {
  if (
    $build->branch !== Config::BRANCH ||
    $build->username !== Config::ORG_NAME ||
    $build->reponame !== Config::REPO_NAME
  ) {
    api_response(sprintf(
      'Not archiving; this build is not on the correct branch: %s/%s/%s',
      $build->username,
      $build->reponame,
      $build->branch
    ));
  }
}

$payload = json_decode(file_get_contents('php://input'));
if (empty($payload)) {
  api_error('400', 'No payload provided');
}
// First validate the data that was passed in as the payload. If the branch
// or repo name is not valid, we can quit early (before calling CircleCI's API)
validate_build($payload->payload);
$build_num = $payload->payload->build_num;
if (empty($build_num)) {
  api_error('400', 'No build number found');
}
if (
  $payload->payload->status !== 'success' &&
  $payload->payload->status !== 'fixed'
) {
  api_response(sprintf('Build #%s in wrong status (%s), not archiving it', $build_num, $payload->payload->status));
}

// Now, load this build from their API and revalidate it, to ensure it's legit
// and the client isn't tricking us.
$project_uri = sprintf(
  'project/github/%s/%s/%s',
  Config::ORG_NAME,
  Config::REPO_NAME,
  $build_num
);
$build = call_circleci($project_uri);
validate_build($build);

// Download the artifacts in parallel
$artifact_client = new Client();
$artifacts = call_circleci($project_uri.'/artifacts');
$promises = [];
foreach ($artifacts as $artifact) {
  $filename = basename($artifact->path);
  $requests[$filename] = $artifact_client->getAsync($artifact->url, [
    'sink' => Config::ARTIFACT_PATH.$filename,
  ]);
}
$results = Promise\unwrap($requests);
$output = '';

// Update latest.json to point to the newest files
$latest = ArtifactManifest::exists()
  ? ArtifactManifest::load();
  : (object)[];
foreach ($requests as $filename => $_) {
  $output .= $filename.'... ';
  $full_path = Config::ARTIFACT_PATH.$filename;

  $metadata = ArtifactFileUtils::getMetadata(new SplFileInfo($full_path));
  if (!$metadata) {
    unlink($full_path); // Scary!
    $output .= "Skipped (unknown type)\n";
  }

  $latest->{$metadata['type']} = [
    'date' => $metadata['date'],
    'filename' => $filename,
    'version' => $metadata['version'],
    'url' => 'https://nightly.yarnpkg.com/'.$filename,
  ];

  // If it's a Debian package, also copy it to the incoming directory.
  // This is used to populate the Debian repository.
  if ($metadata['type'] === 'deb') {
    copy(
      Config::ARTIFACT_PATH.$filename,
      Config::DEBIAN_INCOMING_PATH.$filename
    );
    $output .= 'Queued for adding to Debian repo, ';
  }

  $output .= "Done.\n";
}
ArtifactManifest::save($latest);

$output .= sprintf("\nArchiving of build %s completed!", $build_num);
echo $output;
Analog::info($output);
