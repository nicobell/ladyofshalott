<?php

namespace Drupal\site_settings_sample_data\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Test the full site settings loader.
 *
 * @package Drupal\site_settings_sample_data\Controller
 */
class TestFullSiteSettingsLoaderTwigExtension extends ControllerBase {

  /**
   * Render the twig extension test template.
   */
  public function render() {
    return [
      '#type' => 'container',
      '#theme' => 'test_twig_extension',
    ];
  }

}
