<?php
/**
 * Gets a list of all available builds of a particular type.
 */

declare(strict_types=1);
require(__DIR__.'/../lib/api-core.php');

$type = $_GET['type'] ?? 'tar';
$dir = new FileSystemIterator(Config::ARTIFACT_PATH);
$files = [];
foreach ($dir as $file) {
  $metadata = ArtifactFileUtils::getMetadata($file);
  if (!$metadata || $metadata['type'] !== $type) {
    // Ignore this file if it's not the type we're looking for
    continue;
  }
  $files[] = $metadata;
}

usort($files, function ($a, $b) {
  return $b['date'] - $a['date'];
});
header('Content-Type: application/json; charset=utf-8');
echo json_encode($files, JSON_PRETTY_PRINT);
