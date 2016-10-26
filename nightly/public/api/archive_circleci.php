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

$payload = json_decode(file_get_contents('php://input'));
if (empty($payload)) {
  api_error('400', 'No payload provided');
}
$build_num = $payload->payload->build_num;
if (empty($build_num)) {
  api_error('400', 'No build number found');
}

// Load this build from their API to ensure the data is legit
$build = call_circleci('project/github/yarnpkg/yarn/'.$build_num);
if ($build->branch !== 'master') {
  api_response('Not archiving; this build is not on the master branch');
}

// Download the artifacts in parallel
$artifact_client = new Client(['defaults' => [
    'verify' => false
]]);
$artifacts = call_circleci('project/github/yarnpkg/yarn/'.$build_num.'/artifacts');
$promises = [];
foreach ($artifacts as $artifact) {
  $filename = basename($artifact->path);
  $requests[$filename] = $artifact_client->getAsync($artifact->url, [
    'sink' => __DIR__.'/../'.$filename,
    'verify' => false,
  ]);
}
$results = Promise\unwrap($requests);

api_response("Successfully archived these artifacts:\n".implode("\n", array_keys($requests)));
