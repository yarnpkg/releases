<?php
/**
 * Redirects to the latest artifact of a particular type
 */

require(__DIR__.'/../lib/api-core.php');

// Get file extension that was requested
$extension = $_GET['ext'];
// Handle "tar.gz" -> "tar"
$dot_pos = strrpos($extension, '.');
if ($dot_pos !== false) {
  $extension = substr($extension, 0, $dot_pos);
}

// Check if we have a file of that type
$latest = json_decode(file_get_contents(Config::ARTIFACT_PATH.'latest.json'));
if (!empty($latest->$extension)) {
  header('Location: '.$latest->$extension->url);
} else {
  header('Status: 404 Not Found');
}
