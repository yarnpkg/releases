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
}
