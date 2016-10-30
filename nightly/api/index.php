<?php
declare(strict_types=1);
error_reporting(E_ALL);
require('../vendor/autoload.php');

// Latest files
$latest_filenames = [];
$latest_files = [];
$latest_manifest = ArtifactManifest::load();
foreach ($latest_manifest as $type => $details) {
  $latest_files[] = ArtifactFileUtils::getMetadata(
    new SplFileInfo(Config::ARTIFACT_PATH.'/'.$details->filename)
  );
  $latest_filenames[$details->filename] = true;
}
usort($latest_files, function ($a, $b) {
  return strcmp($a['file']->getFileName(), $b['file']->getFileName());
});

// All available files
$dir = new FileSystemIterator(Config::ARTIFACT_PATH);
$grouped_files = [];
foreach ($dir as $file) {
  $metadata = ArtifactFileUtils::getMetadata($file);

  if (
    !$metadata ||
    // Exclude files already included in the "latest" section at the top
    !empty($latest_filenames[$file->getFileName()])
  ) {
    continue;
  }
  if (!isset($grouped_files[$metadata['type']])) {
    $grouped_files[$metadata['type']] = [];
  }

  $grouped_files[$metadata['type']][] = $metadata;
}

function render_filename(SplFileInfo $file) {
?>
  <a href="<?= htmlspecialchars($file->getFileName()) ?>">
    <?= htmlspecialchars($file->getFileName()) ?>
  </a>
<?php
}

function render_date(array $file) {
  $date = gmdate('c', $file['date']);
?>
  <time datetime="<?= $date ?>">
    <?= timeAgoInWords($date) ?>
  </time>
<?php
}

// Let's start rendering :D
require('header.php');
?>

<p>These are the latest and greatest builds of Yarn. These builds are not guaranteed to be stable! Use at your own risk. Quick links:</p>
<ul>
  <li><a href="/latest.json">/latest.json</a>: Latest version number and download links in JSON format</li>
  <li><a href="/latest.tar.gz">/latest.tar.gz</a>: Tarball of the latest Yarn nightly release</li>
  <li><a href="/latest.deb">/latest.deb</a>: Debian package of the latest Yarn nightly release</li>
</ul>

<h2>Latest Version</h2>
<table>
  <thead>
    <tr>
      <th>Name</th>
      <th>Type</th>
      <th>Size</th>
      <th>Age</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($latest_files as $file) { ?>
      <tr>
        <td><?= render_filename($file['file']) ?></td>
        <td><?= ArtifactFileUtils::getTypeName($file['type']) ?></td>
        <td><?= ArtifactFileUtils::formatSize($file['file']->getSize()) ?></td>
        <td><?= render_date($file) ?></td>
      </tr>
    <?php } ?>
  </tbody>
</table>

<h2>Older Versions</h2>
<div class="tabs">
  <div class="nav nav-tabs bg-faded text-xs-center">
    <ul class="nav navbar-nav nav-inline">
      <?php foreach (ArtifactFileUtils::$type_names as $extension => $type_name) { ?>
        <a id="<?= $extension ?>-tab" class="nav-item nav-link" data-toggle="tab" href="#<?= $extension ?>">
          <?= $type_name ?>
        </a>
      <?php } ?>
    </ul>
  </div>

  <div class="tab-content">
    <?php
    foreach ($grouped_files as $extension => $files) {
      usort($files, function ($a, $b) {
        return $b['date'] - $a['date'];
      });
      ?>
      <div class="tab-pane" id="<?= $extension ?>">
        <table>
          <thead>
            <tr>
              <th width="60%">Name</th>
              <th width="20%">Size</th>
              <th width="20%">Date</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($files as $file) { ?>
              <tr>
                <td><?= render_filename($file['file']) ?></td>
                <td><?= ArtifactFileUtils::formatSize($file['file']->getSize()) ?></td>
                <td><?= render_date($file) ?></td>
              </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
    <?php } ?>
  </div>
</div>

<?php require('footer.php') ?>
<script>$('#tar-tab').tab('show')</script>
