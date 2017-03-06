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

$build = CircleCI::getAndValidateBuildFromPayload();
$urls = CircleCI::getArtifactsForBuild($build->build_num);
ArtifactArchiver::archiveBuild($urls, $build->build_num);
