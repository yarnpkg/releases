<?php
declare(strict_types=1);

/**
 * Reads and writes data about the latest available artifacts.
 */
abstract class ArtifactManifest {
  const PATH = Config::ARTIFACT_PATH.'/latest.json';

  public static function load() {
    return json_decode(file_get_contents(self::PATH));
  }

  public static function save($contents) {
    file_put_contents(self::PATH, json_encode($latest, JSON_PRETTY_PRINT));
  }

  public static function exists(): boolean {
    return file_exists(self::PATH);
  }
}
