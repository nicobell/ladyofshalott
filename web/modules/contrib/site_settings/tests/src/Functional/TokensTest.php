<?php

namespace Drupal\Tests\site_settings\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test tokens.
 *
 * @group site_settings
 */
class TokensTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['site_settings_sample_data'];

  /**
   * Test multiple tokens.
   */
  public function testMultipleTokens() {
    /** @var \Drupal\site_settings\SiteSettingsLoaderPluginManager $plugin_manager */
    $plugin_manager = \Drupal::service('plugin.manager.site_settings_loader');
    $plugin_manager->setActiveLoaderPlugin('flattened');

    $value = \Drupal::service('token')->replace('[site_settings:other--test_plain_text]');
    $this->assertSame('Test plain text value', $value);

    $value = \Drupal::service('token')->replace('[site_settings:other--test_textarea]');
    $this->assertSame('Test textarea value', $value);

    $value = \Drupal::service('token')->replace('[site_settings:other--test_multiple_entries--0--value]');
    $this->assertSame('Test multiple entries content 1', $value);

    $value = \Drupal::service('token')->replace('[site_settings:other--test_multiple_entries--1--value]');
    $this->assertSame('Test multiple entries content 2', $value);

    $value = \Drupal::service('token')->replace('[site_settings:other--test_multiple_entries_and_fields--0-field_testing]');
    $this->assertSame('Test multiple entries and fields content 1 field 1', $value);

    $value = \Drupal::service('token')->replace('[site_settings:other--test_multiple_entries_and_fields--0-field_test_textarea]');
    $this->assertSame('Test multiple entries and fields content 1 field 2', $value);

    $value = \Drupal::service('token')->replace('[site_settings:other--test_multiple_entries_and_fields--1-field_testing]');
    $this->assertSame('Test multiple entries and fields content 2 field 1', $value);

    $value = \Drupal::service('token')->replace('[site_settings:other--test_multiple_entries_and_fields--1-field_test_textarea]');
    $this->assertSame('Test multiple entries and fields content 2 field 2', $value);

    $plugin_manager->setActiveLoaderPlugin('full');
  }

}
