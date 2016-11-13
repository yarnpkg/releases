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
    foreach ($artifacts as $filename => $url) {
      $requests[$filename] = $artifact_client->getAsync($url, [
        'sink' => Config::ARTIFACT_PATH.$filename,
      ]);
    }
    $results = Promise\unwrap($requests);
    $output = '';

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
      }

      $latest->{$metadata['type']} = [
        'date' => $metadata['date'],
        'filename' => $filename,
        'size_bytes' => $file->getSize(),
        'size' => ArtifactFileUtils::formatSize($file->getSize()),
        'version' => $metadata['version'],
        'url' => 'https://nightly.yarnpkg.com/'.$filename,
      ];

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
