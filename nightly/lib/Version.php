<?php
declare(strict_types=1);

/**
 * Versioning utilities.
 */
class Version {
  /**
   * Gets the version number for the latest stable version of Yarn.
   */
  public static function getLatestStableYarnVersion(): string {
    return trim(file_get_contents('https://yarnpkg.com/latest-version'));
  }

  /**
   * Determines if two version numbers are the same minor version.
   */
  public static function isSameMinorVersion(string $a, string $b): bool {
    $a_parts = explode('.', $a);
    $b_parts = explode('.', $b);
    return $a_parts[0] === $b_parts[0] && $a_parts[1] === $b_parts[1];
  }

  /**
   * Determines if the specified NEW version number should be considered a
   * stable release, based on the current stable version number of Yarn.
   */
  public static function isStableVersionNumber(string $new_version): bool {
    $latest_stable_version = static::getLatestStableYarnVersion();
    return static::isSameMinorVersion($latest_stable_version, $new_version);
  }
}
