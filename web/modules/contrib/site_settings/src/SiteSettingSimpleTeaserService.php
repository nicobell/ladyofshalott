<?php

namespace Drupal\site_settings;

/**
 * Helper service to generate a simple site setting teaser.
 *
 * @package Drupal\site_settings
 */
class SiteSettingSimpleTeaserService {

  /**
   * Simplify the teaser.
   *
   * @param array $build
   *   The teaser build.
   *
   * @return array
   *   The updated build array.
   */
  public function generateTeaser(array $build): array {
    foreach ($build as $key => &$value) {
      if (str_starts_with($key, '#')) {
        continue;
      }

      // Skip things shown in other columns.
      if (in_array($key, ['group', 'description', 'name', 'user_id'])) {
        hide($build[$key]);
        continue;
      }

      // Provide summary version of each field.
      if (
        isset($value[0]['#context']['value'])
        && function_exists('text_summary')
        && is_string($value[0]['#context']['value'])
      ) {
        $original_text = $value[0]['#context']['value'];
        $new_text = text_summary($value[0]['#context']['value'], NULL, 150);
        if ($new_text !== $original_text) {
          $new_text .= '...';
        }
        $value[0]['#context']['value'] = $new_text;
      }
    }
    return $build;
  }

}
