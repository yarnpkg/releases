<?php
/**
 * A CircleCI webhook that pulls build artifacts from CircleCI into the local file
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

Analog::handler(__DIR__.'/../logs/archive_circleci.log');

$build = CircleCI::getAndValidateBuildFromPayload();
$artifacts = CircleCI::call(
  'project/github/%s/%s/%s/artifacts',
  Config::ORG_NAME,
  Config::REPO_NAME,
  $build->build_num
);
$urls = [];
foreach ($artifacts as $artifact) {
  $filename = basename($artifact->path);
  $urls[$filename] = $artifact->url;
}
ArtifactArchiver::archiveBuild($urls, $build->build_num);
