<?php

namespace Drupal\Tests\site_settings\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests site settings revision support.
 *
 * @group site_settings
 */
class RevisionsTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'site_settings',
    'site_settings_sample_data',
    'user',
  ];

  /**
   * Test that site settings are revisionable.
   */
  public function testRevision() {
    /** @var \Drupal\site_settings\SiteSettingsLoaderPluginManager $plugin_manager */
    $plugin_manager = \Drupal::service('plugin.manager.site_settings_loader');
    $plugin_manager->setActiveLoaderPlugin('flattened');
    $site_settings_loader = $plugin_manager->getActiveLoaderPlugin();

    // Load original entity.
    $site_settings = \Drupal::entityTypeManager()
      ->getStorage('site_setting_entity')
      ->loadByProperties(['type' => 'test_plain_text']);
    /** @var \Drupal\site_settings\Entity\SiteSettingEntity $site_setting */
    $site_setting = reset($site_settings);
    $site_setting->set('field_testing', 'Test plain text revision value');
    $site_setting->setNewRevision();
    $site_setting->setRevisionUserId(\Drupal::currentUser()->id());
    $site_setting->setRevisionCreationTime(\Drupal::time()->getRequestTime());
    $site_setting->save();

    // Load the revision entity.
    $site_settings = $site_settings_loader->loadAll(TRUE);
    $this->assertSame('Test plain text revision value', $site_settings['other']['test_plain_text']);
    $plugin_manager->setActiveLoaderPlugin('full');
  }

}
