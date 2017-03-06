<?php
/**
 * A CircleCI webhook that pulls build artifacts from AppVeyor and deploys them
 * to the GitHub release.
 */

require(__DIR__.'/../lib/api-core.php');
set_time_limit(100);

use GuzzleHttp\Promise;

$build = CircleCI::getAndValidateBuildFromPayload();
// Only publish tagged releases
if (!preg_match(Config::RELEASE_TAG_FORMAT, $build->vcs_tag)) {
  api_response(sprintf(
    '[%s] Not publishing as release; this is not a release tag. branch=%s tag=%s',
    $build->build_num,
    $build->branch ?? '[none]',
    $build->vcs_tag ?? '[none]'
  ));
}

$artifacts = CircleCI::getArtifactsForBuild($build->build_num);
$tempdir = Filesystem::createTempDir('yarn-release');
ArtifactArchiver::downloadArtifacts($artifacts, $tempdir);

$release = GitHub::getOrCreateRelease($build->vcs_tag);
$output = '['.$build->build_num.'] Uploaded to '.$build->vcs_tag.":\n";

$promises = [];
foreach ($artifacts as $filename => $_) {
  $path = $tempdir.$filename;
  $promises[] = GitHub::uploadReleaseArtifact($release, $filename, $path);
  $output .= $filename."\n";

  // GPG sign all the files that need to be signed
  if (preg_match(Config::SIGN_FILE_TYPES, $filename)) {
    file_put_contents($path.'.asc', GPG::sign($path, Config::GPG_RELEASE));
    $promises[] = GitHub::uploadReleaseArtifact($release, $filename.'.asc', $path.'.asc');
  }
}
$responses = Promise\unwrap($promises);

api_response($output);
