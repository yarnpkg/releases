<?php
declare(strict_types=1);

use GuzzleHttp\Client;

/**
 * Utilities for publishing release versions of Yarn
 */
class Release {
  /**
   * Checks if the specified release is complete (that is, it contains all the
   * artifacts required by a Yarn release). If so, kicks off some post-release
   * processing jobs.
   *
   * This is required because the Windows build and Linux build are built on two
   * separate build systems, and they both upload artifacts to GitHub. We can
   * only finalise the release once all the artifacts have been uploaded.
   */
  public static function performPostReleaseJobsIfReleaseIsComplete(string $version): string {
    $release = GitHub::getRelease('v'.$version);

    // File types that are *required* for a release to be complete
    $has_file = [
      '.deb' => false,
      '.js' => false,
      '.js.asc' => false,
      '.msi' => false,
      '.tar.gz' => false,
      '.tar.gz.asc' => false,
    ];
    foreach ($release->assets as $asset) {
      foreach ($has_file as $type => $_) {
        if (Str::endsWith($asset->name, $type)) {
          $has_file[$type] = true;
          break;
        }
      }
    }

    // Check if any required files are missing
    $missing_files = array_filter($has_file, function($exists) {
      return !$exists;
    });
    if (count($missing_files) > 0) {
      return
        'Release is not complete yet; these file types are missing: '.
        implode(', ', array_keys($missing_files));
    }

    // If we got here, the release must be complete!
    static::performPostReleaseJobs($version, !$release->prerelease);
    return 'Release is complete! Post-release jobs scheduled.';
  }

  /**
   * Kicks off a Jenkins build to perform any post-release processing (such as
   * pushing to Chocolatey and Homebrew, bumping the version number on the Yarn
   * site, etc.)
   */
  public static function performPostReleaseJobs(string $version, bool $is_stable) {
    Jenkins::build(
      Config::JENKINS_VERSION_JOB,
      Config::JENKINS_VERSION_TOKEN,
      [
        'YARN_VERSION' => $version,
        'YARN_RC' => $is_stable ? 'false' : 'true',
        'cause' => 'Automated release of Yarn '.$version,
      ]
    );
  }
}
