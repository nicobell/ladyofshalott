<?php

namespace Drupal\Tests\site_settings\Functional;

use Drupal\site_settings_sample_data\Entity\TestMultipleEntriesSiteSetting;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the loading of Site Settings.
 *
 * @group SiteSettings
 */
class SiteSettingsFullLoaderTest extends BrowserTestBase {

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
    'user',
  ];

  /**
   * Admin user.
   *
   * @var \Drupal\user\Entity\User|false
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function setUp(): void {
    parent::setUp();

    // Create the user and login.
    $this->adminUser = $this->createUser([], NULL, TRUE);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Test site settings twig extension.
   */
  public function testSiteSettingsTwigExtension() {
    // Open the site settings sample data controller.
    $this->drupalGet('site_settings_sample_data/test_full_site_settings_loader_twig_extension');
    $session = $this->assertSession();

    // Render for "Test multiple entries and fields name 1".
    $session->elementTextContains(
      'css',
      '#render-5',
      'Test multiple entries and fields name 1'
    );
    $session->elementTextContains(
      'css',
      '#render-5',
      'Test multiple entries and fields content 1 field 1'
    );
    $session->elementTextContains(
      'css',
      '#render-5',
      'Test multiple entries and fields content 1 field 2'
    );

    // Render for "Test multiple entries and fields name 2".
    $session->elementTextContains(
      'css',
      '#render-6',
      'Test multiple entries and fields name 2'
    );
    $session->elementTextContains(
      'css',
      '#render-6',
      'Test multiple entries and fields content 2 field 1'
    );
    $session->elementTextContains(
      'css',
      '#render-6',
      'Test multiple entries and fields content 2 field 2'
    );

    // Render for "Test boolean 2".
    $session->elementTextContains(
      'css',
      '#render-12',
      'Test boolean 2'
    );
    $session->elementTextContains(
      'css',
      '#render-12',
      'Off'
    );

    // Render all site settings check.
    $session->elementsCount('css', '#render-all > div', 12);

    // Render group site settings check.
    $session->elementTextContains(
      'css',
      '#render-group',
      'Test boolean 1'
    );
    $session->elementTextContains(
      'css',
      '#render-group',
      'Test boolean 2'
    );
    $session->elementTextContains(
      'css',
      '#render-group',
      'Off'
    );
    $session->elementTextContains(
      'css',
      '#render-group',
      'On'
    );

    // Render multiple by name site settings check.
    $session->elementTextContains(
      'css',
      '#render-name-multiple',
      'Test multiple entries content 1'
    );
    $session->elementTextContains(
      'css',
      '#render-name-multiple',
      'Test multiple entries content 2'
    );

    // Render single by name site settings check.
    $session->elementTextContains(
      'css',
      '#render-name-single',
      'Test plain text value'
    );

    // Render single field by name site settings check.
    $session->elementTextContains(
      'css',
      '#render-field',
      'Test plain text value'
    );

    // Render single field by name site settings when
    // there are multiple of that same site setting.
    $session->elementTextContains(
      'css',
      '#render-single-field-from-multiple',
      'Test multiple entries content 1'
    );

    // Render site setting entity label.
    $session->elementTextEquals(
      'css',
      '#render-single-object-label',
      'Test plain text name'
    );
  }

  /**
   * Test site settings load by entity bundle class.
   */
  public function testSiteSettingsLoadByEntityBundleClass() {
    /** @var \Drupal\site_settings\SiteSettingsLoaderPluginManager $plugin_manager */
    $plugin_manager = \Drupal::service('plugin.manager.site_settings_loader');
    $loader = $plugin_manager->getActiveLoaderPlugin();
    $entities = $loader->loadByEntityBundleClass(TestMultipleEntriesSiteSetting::class);
    $entities = array_values($entities);
    $this->assertCount(2, $entities);
    $this->assertSame('Test multiple entries name 1', $entities[0]->label());
    $this->assertSame('Test multiple entries name 2', $entities[1]->label());
  }

}
