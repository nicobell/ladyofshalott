<?php

namespace Drupal\Tests\site_settings\Functional;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the blocks provided of Site Settings.
 *
 * @group SiteSettings
 */
class SiteSettingsBlockTest extends BrowserTestBase {
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Module list.
   *
   * @var array
   */
  protected static $modules = [
    'site_settings',
    'site_settings_sample_data',
    'field_ui',
    'block',
    'user',
  ];

  /**
   * Admin user.
   *
   * @var \Drupal\user\Entity\User|false
   */
  private $adminUser;

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function setUp():void {
    parent::setUp();

    // Create the user and login.
    $this->adminUser = $this->createUser([], NULL, TRUE);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Test site settings rendered block.
   */
  public function testRenderedSiteSettingsBlock() {
    $this->drupalPlaceBlock('single_rendered_site_settings_block', [
      'region' => 'header',
      'setting' => 'test_boolean',
      'view_mode' => 'teaser',
    ]);

    $this->drupalGet('<front>');
    $this->assertSession()->elementTextContains('css', 'header', 'Test boolean 1');
    $this->assertSession()->elementTextContains('css', 'header', 'Test boolean 2');
    $this->assertSession()->elementTextNotContains('css', 'header', 'Group');
  }

}
