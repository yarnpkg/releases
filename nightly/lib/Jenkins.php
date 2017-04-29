<?php
declare(strict_types=1);

use GuzzleHttp\Client;

/**
 * Wrapper for Jenkins' API.
 */
class Jenkins {
  public static function build(string $job, string $token, array $args) {
    self::call(
      '/job/%s/buildWithParameters',
      [$job],
      array_merge(['token' => $token], $args)
    );
  }

  public static function call(string $uri, array $uri_args, array $post_data) {
    $crumb = self::getCrumb();
    $client = new Client([
      'base_uri' => Config::JENKINS_URL,
    ]);
    $response = $client->post(vsprintf($uri, $uri_args), [
      'form_params' => $post_data,
      'headers' => [
        $crumb->crumbRequestField => $crumb->crumb,
      ],
    ]);
    return json_decode((string)$response->getBody());
  }

  /**
   * The "crumb" is a CSRF token that's required for all POST requests to
   * Jenkins, including the API.
   */
  private static $crumb = null;
  public static function getCrumb() {
    if (self::$crumb === null) {
      $client = new Client([
        'base_uri' => Config::JENKINS_URL,
      ]);
      $response = $client->get('crumbIssuer/api/json');
      self::$crumb = json_decode((string)$response->getBody());
    }
    return self::$crumb;
  }
}
