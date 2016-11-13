<?php
declare(strict_types=1);

abstract class ArtifactFileUtils {
  // Different package types have different naming standards, this regex
  // normalizes all of them.
  const FILENAME_REGEX = '/^yarn[-_](?P<legacy>legacy-)?v?(?P<version>[0-9\.]+[-_](?P<date>[0-9]+)\.(?P<time>[0-9]+))/';

  // Human-readable names of all the file types we archive
  public static $type_names = [
    'tar' => 'Tarball',
    'deb' => 'Debian package',
    'msi' => 'Windows installer',
    'rpm' => 'RPM',
    'js' => 'Standalone JS',
    'js-legacy' => 'Standalone JS (Node < 4.0)'
  ];

  public static function formatSize(int $size): string {
    $base = log($size, 1024);
    $suffixes = ['', ' KB', ' MB', ' GB', ' TB'];
    return round(pow(1024, $base - floor($base)), 2).$suffixes[floor($base)];
  }

  public static function getMetadata(SplFileInfo $file) {
    $filename_without_ext = $file->getBasename('.'.$file->getExtension());
    preg_match(self::FILENAME_REGEX, $filename_without_ext, $matches);
    if (empty($matches)) {
      Analog::warning('Unexpected artifact filename: '.$file->getFileName());
      return null;
    }

    $type = $file->getExtension();
    if ($type === 'js' && !empty($matches['legacy'])) {
      // Handle legacy JS file
      $type = 'js-legacy';
    } else if ($type === 'gz') {
      // Rename "gz" to "tar"
      $type = 'tar';
    }

    if (!array_key_exists($type, self::$type_names)) {
      Analog::warning('Unexpected artifact type: '.$type);
      return null;
    }

    $date = strtotime($matches['date'].' '.$matches['time']. ' UTC');
    return [
      'date' => $date,
      'filename' => $file->getFilename(),
      'size_bytes' => $file->getSize(),
      'size' => self::formatSize($file->getSize()),
      'type' => $type,
      'version' => $matches['version'],
      'url' => 'https://nightly.yarnpkg.com/'.$file->getFilename(),
    ];
  }

  public static function getTypeName(string $type): string {
    return self::$type_names[$type];
  }
}
