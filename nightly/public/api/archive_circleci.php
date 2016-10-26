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

use GuzzleHttp\Client;
use GuzzleHttp\Promise;

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
  if ($build->branch !== 'master' || $build->username !== 'yarnpkg' || $build->reponame !== 'yarn') {
    api_response('Not archiving; this build is not on the yarnpkg master branch');
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

// Now, load this build from their API and revalidate it, to ensure it's legit
// and the client isn't tricking us.
$build = call_circleci('project/github/yarnpkg/yarn/'.$build_num);
validate_build($build);

// Download the artifacts in parallel
$artifact_client = new Client();
$artifacts = call_circleci('project/github/yarnpkg/yarn/'.$build_num.'/artifacts');
$promises = [];
foreach ($artifacts as $artifact) {
  $filename = basename($artifact->path);
  $requests[$filename] = $artifact_client->getAsync($artifact->url, [
    'sink' => __DIR__.'/../'.$filename,
  ]);
}
$results = Promise\unwrap($requests);

api_response("Successfully archived these artifacts:\n".implode("\n", array_keys($requests)));
