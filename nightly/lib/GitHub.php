<?php
declare(strict_types=1);

use GuzzleHttp\Client;

/**
 * Wrapper for GitHub's API.
 */
class GitHub {
  public static function call(string $uri, ...$uri_args) {
    $client = new Client([
      'base_uri' => 'https://api.github.com/',
    ]);
    $response = $client->get(vsprintf($uri, $uri_args), [
      'headers' => [
        'Authorization' => 'token '.Config::GITHUB_TOKEN,
      ],
    ]);
    return json_decode((string)$response->getBody());
  }

  public static function post(string $uri, array $uri_args, array $post_data) {
    $client = new Client([
      'base_uri' => 'https://api.github.com/',
    ]);
    $response = $client->post(vsprintf($uri, $uri_args), [
      'headers' => [
        'Authorization' => 'token '.Config::GITHUB_TOKEN,
      ],
      'json' => $post_data,
    ]);
    return json_decode((string)$response->getBody());
  }

  public static function getOrCreateRelease(string $name) {
    // Check if this release exists
    try {
      return static::call(
        'repos/%s/%s/releases/tags/%s',
        Config::ORG_NAME,
        Config::REPO_NAME,
        $name
      );
    } catch (\GuzzleHttp\Exception\TransferException $e) {
      // Release doesn't exist yet, so create a new one
      return static::post(
        'repos/%s/%s/releases',
        [Config::ORG_NAME, Config::REPO_NAME],
        [
          'name' => $name,
          'tag_name' => $name,
        ]
      );
    }
  }

  /**
   * Upload an artifact to the specific GitHub release. Returns a promise so
   * multiple files can be uploaded in parallel.
   */
  public static function uploadReleaseArtifact(
    $release,
    string $filename,
    string $path
  ): \GuzzleHttp\Promise\PromiseInterface {
    $client = new Client();
    $uri = new \Rize\UriTemplate\UriTemplate();
    $upload_url = $uri->expand(
      $release->upload_url,
      ['name' => $filename]
    );
    return $client->postAsync($upload_url, [
      'body' => fopen($path, 'r'),
      'headers' => [
        'Authorization' => 'token '.Config::GITHUB_TOKEN,
        'Content-Type' => 'application/octet-stream',
      ],
    ]);
  }
}
