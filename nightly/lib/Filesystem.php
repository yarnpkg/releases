<?php
declare(strict_types=1);

/**
 * File system utilities
 */
class Filesystem {
  /**
   * Creates a temporary directory with a unique name.
   */
  public static function createTempDir(string $prefix) {
    $tempdir = tempnam(sys_get_temp_dir(), $prefix);
    // tempnam() creates a temp *file*, but we want a temp *directory*.
    unlink($tempdir);
    mkdir($tempdir);
    return $tempdir.'/';
  }
}
