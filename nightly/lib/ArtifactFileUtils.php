<?php
abstract class ArtifactFileUtils {
  public static function formatSize($size) {
    $base = log($size, 1024);
    $suffixes = ['', ' KB', ' MB', ' GB', ' TB'];
    return round(pow(1024, $base - floor($base)), 2).$suffixes[floor($base)];
  }
}
