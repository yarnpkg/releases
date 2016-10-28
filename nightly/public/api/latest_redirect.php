<?php
/**
 * Redirects to the latest artifact of a particular type
 */

require(__DIR__.'/../../lib/api-core.php');

// Get file extension that was requested
$extension = $_GET['ext'];
// Handle "tar.gz" -> "gz"
$dot_pos = strrpos($extension, '.');
if ($dot_pos !== false) {
  $extension = substr($extension, $dot_pos + 1);
}

// Check if we have a file of that type
$latest = json_decode(file_get_contents(__DIR__.'/../latest.json'));
if (!empty($latest->$extension)) {
  header('Location: '.$latest->$extension->url);
} else {
  header('Status: 404 Not Found');
}
