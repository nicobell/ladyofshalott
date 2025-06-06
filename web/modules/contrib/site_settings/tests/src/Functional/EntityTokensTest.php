<?php

namespace Drupal\Tests\site_settings\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test entity tokens.
 *
 * @group site_settings
 */
class EntityTokensTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['site_settings_sample_data', 'token'];

  /**
   * Test entity tokens.
   */
  public function testEntityTokens() {
    $value = \Drupal::service('token')->replace('[site_settings_entity:test_plain_text:field_testing:value]');
    $this->assertSame('Test plain text value', $value);

    $value = \Drupal::service('token')->replace('[site_settings_entity:test_textarea:field_test_textarea:value]');
    $this->assertSame('Test textarea value', $value);

    $value = \Drupal::service('token')->replace('[site_settings_entity:test_multiple_entries-0:field_testing:value]');
    $this->assertSame('Test multiple entries content 1', $value);

    $value = \Drupal::service('token')->replace('[site_settings_entity:test_multiple_entries-1:field_testing:value]');
    $this->assertSame('Test multiple entries content 2', $value);

    $value = \Drupal::service('token')->replace('[site_settings_entity:test_image:field_image:title]');
    $this->assertSame('Test image title', $value);
  }

}
