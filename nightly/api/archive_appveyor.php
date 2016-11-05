<?php
/**
 * An AppVeyor webhook that pulls build artifacts from AppVeyor into the local
 * file system.
 */

require(__DIR__.'/../lib/api-core.php');

use Analog\Analog;
use GuzzleHttp\Client;

Analog::handler(__DIR__.'/../logs/archive_appveyor.log');

function call_appveyor($uri, ...$uri_args) {
  $client = new Client([
    'base_uri' => 'https://ci.appveyor.com/api/',
  ]);
  $response = $client->get(vsprintf($uri, $uri_args));
  return json_decode($response->getBody());
}

function validate_build($build, $project) {
  if (
    $build->branch !== Config::BRANCH ||
    $project->repositoryName !== Config::ORG_NAME.'/'.Config::REPO_NAME
  ) {
    api_response(sprintf(
      '[#%s] Not archiving; this build is not on the correct branch: %s/%s',
      $build->version ?? $build->buildVersion,
      $project->repositoryName,
      $build->branch
    ));
  }
  if (!empty($build->pullRequestId)) {
    api_response(sprintf(
      '[#%s] Not archiving; this is a pull request (%s)',
      $build->version ?? $build->buildVersion,
      $build->pullRequestId
    ));
  }
}

// Validate auth token
$auth_token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (empty($auth_token) || $auth_token !== Config::APPVEYOR_WEBHOOK_AUTH_TOKEN) {
  api_error('403', 'Unauthorized');
}

$payload = json_decode(file_get_contents('php://input'));
if (empty($payload) || empty($payload->eventData)) {
  api_error('400', 'No payload provided');
}
$build = $payload->eventData;
// First validate the data that was passed in as the payload. If the branch
// or repo name is not valid, we can quit early (before calling AppVeyor's API)
validate_build($build, $build);
$build_version = $build->buildVersion;
if (empty($build_version)) {
  api_error('400', 'No build version found');
}
if (!$build->passed) {
  api_response(sprintf(
    '[#%s] Build in wrong status (passed = false), not archiving it',
    $build_version
  ));
}

// Now, load this build from their API and revalidate it, to ensure it's legit
// and the client isn't tricking us.
$verified_build = call_appveyor(
  'projects/%s/%s/build/%s',
  Config::APPVEYOR_USERNAME,
  Config::APPVEYOR_PROJECT_SLUG,
  $build_version
);
validate_build($verified_build->build, $verified_build->project);

// Everything looks fine, grab the artifacts from AppVeyor's API. Don't trust
// the artifacts POSTed to this webhook, just in case.
// We have multiple builds with different Node.js versions, so just grabbing
// the artifact from any job should be sufficient.
$job_id = $verified_build->build->jobs[0]->jobId;
$artifacts = call_appveyor(
  'buildjobs/%s/artifacts',
  $job_id
);
$urls = [];
foreach ($artifacts as $artifact) {
  $filename = basename($artifact->fileName);
  $urls[$filename] = sprintf(
    'https://ci.appveyor.com/api/buildjobs/%s/artifacts/%s',
    $job_id,
    $artifact->fileName
  );
}

ArtifactArchiver::archiveBuild($urls, $build_version);
