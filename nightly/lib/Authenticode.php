<?php
declare(strict_types=1);

/**
 * Handles Authenticode signing
 */
class Authenticode {
  /**
   * Signs a file, returning a path to the newly-signed file.
   */
  public static function sign(string $filename): string {
    // Windows 7 and 10 have deprecated SHA1 and require SHA256 or higher,
    // however Vista and XP don't support SHA256. To fix this, we sign using
    // *both* methods (dual signing).
    // Reference: http://www.elstensoftware.com/blog/2016/02/10/dual-signing-osslsigncode/

    // First sign with SHA1
    $tempfile = tempnam(sys_get_temp_dir(), 'yarnsign');
    self::execSign('-h sha1 -in '.escapeshellarg($filename).' -out '.escapeshellarg($tempfile));

    // Now sign with SHA256
    self::execSign('-nest -h sha2 -in '.escapeshellarg($tempfile).' -out '.escapeshellarg($tempfile));

    return $tempfile;
  }

  private static function execSign(string $params) {
    $command =
      'osslsigncode sign '.
      '-t http://timestamp.digicert.com '.
      '-n "Yarn Installer" '.
      '-i https://yarnpkg.com/ '.
      '-pkcs12 '.escapeshellarg(Config::AUTHENTICODE_KEY).' '.
      '-readpass '.escapeshellarg(Config::AUTHENTICODE_PASS).' '.
      $params;

    exec($command.' 2>&1', $output, $ret);
    $output = implode("\n", $output);
    if ($ret !== 0) {
      throw new AuthenticodeException($output);
    }
  }
}

class AuthenticodeException extends Exception { }
