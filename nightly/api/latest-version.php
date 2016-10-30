<?php
/**
 * Returns the latest available version number.
 */

require(__DIR__.'/../lib/api-core.php');

$type = $_GET['type'] ?? 'tar';
$latest = ArtifactManifest::load();
if (!empty($latest->$type)) {
  header('Content-Type: text/plain');
  echo $latest->$type->version;
} else {
  header('Status: 404 Not Found');
}
