<?php
declare(strict_types=1);

/**
 * String utilities
 */
class Str {
  public static function endsWith(string $haystack, string $needle): bool {
    return substr($haystack, -strlen($needle)) === $needle;
  }
}
