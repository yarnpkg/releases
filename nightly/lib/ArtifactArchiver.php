<?php
declare(strict_types=1);

use Analog\Analog;
use GuzzleHttp\Client;
use GuzzleHttp\Promise;

class ArtifactArchiver {
  /**
   * Archives a build locally. $artifacts is an array of filename => URL to
   * download the artifact from the build server.
   */
  public static function archiveBuild(array $artifacts, $build_identifier) {
    // Download the artifacts in parallel
    $artifact_client = new Client();
    $promises = [];
    $file_handles = [];
    foreach ($artifacts as $filename => $url) {
      $file_handle = fopen(Config::ARTIFACT_PATH.$filename, 'w');
      $requests[$filename] = $artifact_client->getAsync($url, [
        'sink' => $file_handle,
      ]);
      $file_handles[] = $file_handle;
    }
    $results = Promise\unwrap($requests);
    $output = '';

    // Guzzle locks the download files, and GPG also tries to lock them :/
    // Easiest way to fix this is to explicitly close all the handles once
    // Guzzle is done with them.
    foreach ($file_handles as $file_handle) {
      try {
        fclose($file_handle);
      } catch (Exception $e) {
        // Ignore, maybe file was already closed.
      }
    }

    // Update latest.json to point to the newest files
    $latest = ArtifactManifest::exists()
      ? ArtifactManifest::load()
      : (object)[];
    foreach ($requests as $filename => $_) {
      $output .= $filename.'... ';
      $full_path = Config::ARTIFACT_PATH.$filename;

      $file = new SplFileInfo($full_path);
      $metadata = ArtifactFileUtils::getMetadata($file);
      if (!$metadata) {
        unlink($full_path); // Scary!
        $output .= "Skipped (unknown type)\n";
        continue;
      }

      $latest->{$metadata['type']} = $metadata;

      // GPG sign the artifact
      $signature = GPG::sign($full_path, Config::GPG_NIGHTLY);
      file_put_contents($full_path.'.asc', $signature);

      // If it's a Debian package, also copy it to the incoming directory.
      // This is used to populate the Debian repository.
      if ($metadata['type'] === 'deb') {
        copy(
          Config::ARTIFACT_PATH.$filename,
          Config::DEBIAN_INCOMING_PATH.$filename
        );
        $output .= 'Queued for adding to Debian repo, ';
      }

      $output .= "Done.\n";
    }
    ArtifactManifest::save($latest);

    $output .= sprintf("\nArchiving of build %s completed!", $build_identifier);
    echo $output;
    Analog::info($output);
  }
}
