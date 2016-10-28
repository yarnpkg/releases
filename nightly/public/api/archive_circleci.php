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

require(__DIR__.'/../../lib/api-core.php');

use Analog\Analog;
use GuzzleHttp\Client;
use GuzzleHttp\Promise;

Analog::handler(__DIR__.'/../../logs/archive_circleci.log');

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
if ($payload->payload->status !== 'success') {
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
    'sink' => Config::ARTIFACT_PATH.'/'.$filename,
  ]);
}
$results = Promise\unwrap($requests);

// Update latest.json to point to the newest files
$latest_manifest_path = __DIR__.'/../latest.json';
$latest = file_exists($latest_manifest_path)
  ? json_decode(file_get_contents($latest_manifest_path))
  : (object)[];
foreach ($requests as $filename => $_) {
  // Assumes there's only one file per extension
  $extension = pathinfo($filename, PATHINFO_EXTENSION);
  $latest->$extension = [
    // TODO 'date' => ...
    'filename' => $filename,
    'url' => 'https://nightly.yarnpkg.com/'.$filename,
  ];
}
file_put_contents($latest_manifest_path, json_encode($latest, JSON_PRETTY_PRINT));

api_response(sprintf(
  "Successfully archived these artifacts for build %s:\n%s",
  $build_num,
  implode("\n", array_keys($requests))
));
