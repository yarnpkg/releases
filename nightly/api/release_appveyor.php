<?php
/**
 * An AppVeyor publishing webhook that pulls build artifacts from AppVeyor and
 * deploys them to the GitHub release.
 */

require(__DIR__.'/../lib/api-core.php');

use GuzzleHttp\Client;

AppVeyor::validateWebhookAuth();

$payload = json_decode(file_get_contents('php://input'));
if (empty($payload)) {
  api_error('400', 'No payload provided');
}

// Ignore the Node.js 4.x build
if ($payload->environmentVariables->node_version === '4') {
  api_response(sprintf(
    '[#%s] Ignoring Node.js 4.x build',
    $payload->buildVersion
  ));
}

$build = AppVeyor::getAndValidateBuild($payload);

// Ensure provided job ID is part of this build
$job_id = $payload->jobId;
$is_valid_job = false;
foreach ($build->build->jobs as $job) {
  if ($job->jobId === $job_id) {
    $is_valid_job = true;
    break;
  }
}
if (!$is_valid_job) {
  api_error('400', 'Invalid job ID: '.$job_id);
}

// Get artifacts for this job, and just download the first one
$urls = AppVeyor::getArtifactsForJob($job_id);
$url = current($urls);
$filename = key($urls);
$tempfile = tempnam(sys_get_temp_dir(), 'yarn-artifact');
$client = new Client();
$client->get($url, ['sink' => $tempfile]);

$signed_tempfile = Authenticode::sign($tempfile);

// Get version number from filename, and get the release with this version number
preg_match('/yarn-(?P<version>.+?)(-unsigned)?\.msi/', $filename, $matches);
if (empty($matches)) {
  api_error('400', 'Unexpected filename: '.$filename);
}
$tag = 'v'.$matches['version'];
$release = GitHub::getOrCreateRelease($tag);

// Upload the file to the release
$signed_filename = str_replace('-unsigned', '', $filename);
GitHub::uploadReleaseArtifact($release, $signed_filename, $signed_tempfile)->wait();
api_response('Published '.$signed_filename.' to '.$tag);
