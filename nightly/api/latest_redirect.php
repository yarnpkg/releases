<?php
/**
 * Redirects to the latest artifact of a particular type
 */

require(__DIR__.'/../lib/api-core.php');

// Get file extension that was requested
$extension = $_GET['ext'];

$suffix = '';
// Handle requests for ".asc" (GPG signature)
if (Str::endsWith($extension, '.asc')) {
  $suffix = '.asc';
  $extension = str_replace('.asc', '', $extension);
}

// Handle "tar.gz" -> "tar"
$dot_pos = strrpos($extension, '.');
if ($dot_pos !== false) {
  $extension = substr($extension, 0, $dot_pos);
}

// Check if we have a file of that type
$latest = ArtifactManifest::load();
if (!empty($latest->$extension)) {
  header('Location: '.$latest->$extension->url.$suffix);
} else {
  header('Status: 404 Not Found');
}
