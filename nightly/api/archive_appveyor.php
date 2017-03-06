<?php
/**
 * An AppVeyor webhook that pulls build artifacts from AppVeyor into the local
 * file system.
 */

require(__DIR__.'/../lib/api-core.php');

AppVeyor::validateWebhookAuth();

$payload = json_decode(file_get_contents('php://input'));
if (empty($payload) || empty($payload->eventData)) {
  api_error('400', 'No payload provided');
}
$build = AppVeyor::getAndValidateBuild($payload->eventData);

// Everything looks fine, grab the artifacts from AppVeyor's API. Don't trust
// the artifacts POSTed to this webhook, just in case.
// We have multiple builds with different Node.js versions, so just grabbing
// the artifact from any job should be sufficient.
$job_id = $build->build->jobs[0]->jobId;
$urls = AppVeyor::getArtifactsForJob($job_id);

ArtifactArchiver::archiveBuild($urls, $build->build->version);
