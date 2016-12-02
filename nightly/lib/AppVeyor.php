<?php
declare(strict_types=1);

use GuzzleHttp\Client;

/**
 * Wrapper for AppVeyor's API, and utility methods for AppVeyor webhooks.
 */
class AppVeyor {
  public static function validateWebhookAuth() {
    $auth_token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (empty($auth_token) || $auth_token !== Config::APPVEYOR_WEBHOOK_AUTH_TOKEN) {
      api_error('403', 'Unauthorized');
    }
  }

  public static function getAndValidateBuild($build) {
    // First validate the data that was passed in as the payload. If the branch
    // or repo name is not valid, we can quit early (before calling AppVeyor's API)
    static::validateBuild($build, $build);
    $build_version = $build->buildVersion;
    if (empty($build_version)) {
      api_error('400', 'No build version found');
    }
    if (isset($build->passed) && !$build->passed) {
      api_response(sprintf(
        '[#%s] Build in wrong status (passed = false), not archiving it',
        $build_version
      ));
    }

    // Now, load this build from their API and revalidate it, to ensure it's legit
    // and the client isn't tricking us.
    $verified_build = static::call(
      'projects/%s/%s/build/%s',
      Config::APPVEYOR_USERNAME,
      Config::APPVEYOR_PROJECT_SLUG,
      $build_version
    );
    static::validateBuild($verified_build->build, $verified_build->project);
    return $verified_build;
  }

  public static function getArtifactsForJob($job_id) {
    $artifacts = static::call('buildjobs/%s/artifacts', $job_id);
    foreach ($artifacts as $artifact) {
      $filename = basename($artifact->fileName);
      $urls[$filename] = sprintf(
        'https://ci.appveyor.com/api/buildjobs/%s/artifacts/%s',
        $job_id,
        $artifact->fileName
      );
    }
    return $urls;
  }

  public static function validateBuild($build, $project) {
    if (
      (
        $build->branch !== Config::BRANCH &&
        !preg_match(Config::RELEASE_TAG_FORMAT, $build->branch)
      ) ||
      $project->repositoryName !== Config::ORG_NAME.'/'.Config::REPO_NAME
    ) {
      api_response(sprintf(
        '[#%s] Not archiving; this build is not on the correct branch: %s/%s',
        $build->version ?? $build->buildVersion,
        $project->repositoryName,
        $build->branch
      ));
    }
    if (!empty($build->pullRequestId)) {
      api_response(sprintf(
        '[#%s] Not archiving; this is a pull request (%s)',
        $build->version ?? $build->buildVersion,
        $build->pullRequestId
      ));
    }
  }

  public static function call(string $uri, ...$uri_args) {
    $client = new Client([
      'base_uri' => 'https://ci.appveyor.com/api/',
    ]);
    $response = $client->get(vsprintf($uri, $uri_args));
    return json_decode((string)$response->getBody());
  }
}
