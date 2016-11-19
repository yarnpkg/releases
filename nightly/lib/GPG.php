<?php
declare(strict_types=1);

/**
 * Handles GPG signing
 */
class GPG {
  public static function sign(string $filename, string $key): string {
    return static::exec('-u '.escapeshellarg($key).' --armor --output - --detach-sign '.escapeshellarg($filename));
  }

  private static function exec(string $arguments): string {
    exec('gpg '.$arguments.' 2>&1', $output, $ret);
    $output = implode("\n", $output);
    if ($ret !== 0) {
      throw new GPGException($output);
    }
    return $output;
  }
}

class GPGException extends Exception { }
