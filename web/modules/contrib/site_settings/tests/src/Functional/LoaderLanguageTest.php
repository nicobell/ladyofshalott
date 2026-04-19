<?php

namespace Drupal\Tests\site_settings\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\language\Traits\LanguageTestTrait;

/**
 * Tests site settings translation.
 *
 * @group site_settings
 */
class LoaderLanguageTest extends BrowserTestBase {

  use LanguageTestTrait;

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
    'language',
    'content_translation',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Set path prefixes for both languages.
    $this->config('language.negotiation')->set('url', [
      'source' => 'path_prefix',
      'prefixes' => [
        'en' => 'en',
        'fr' => 'fr',
      ],
    ])->save();
  }

  /**
   * Test that site settings are translatable.
   */
  public function testTranslation() {
    /** @var \Drupal\site_settings\SiteSettingsLoaderPluginManager $plugin_manager */
    $plugin_manager = \Drupal::service('plugin.manager.site_settings_loader');
    $plugin_manager->setActiveLoaderPlugin('flattened');
    $site_settings_loader = $plugin_manager->getActiveLoaderPlugin();

    // Add the translation.
    $site_settings = \Drupal::entityTypeManager()
      ->getStorage('site_setting_entity')
      ->loadByProperties(['type' => 'test_plain_text']);
    /** @var \Drupal\site_settings\Entity\SiteSettingEntity $site_setting */
    $site_setting = reset($site_settings);
    $original_value = $site_setting->get('field_testing')->value;
    $site_setting->addTranslation('fr', [
      'field_testing' => 'FR ' . $original_value,
    ]);
    $site_setting->save();

    // Load the translations in the target language.
    $site_settings_translated = $site_settings_loader->loadAll(TRUE, 'fr');
    $this->assertSame('FR ' . $original_value, $site_settings_translated['other']['test_plain_text']);
    $plugin_manager->setActiveLoaderPlugin('full');

    // Test translations in Twig output.
    $languages = $this->container->get('language_manager')->getLanguages();
    // First, check the Twig output in English.
    $this->drupalGet('site_settings_sample_data/test_full_site_settings_loader_twig_extension');
    $session = $this->assertSession();
    $session->pageTextContains('Test plain text value');
    $session->elementTextContains(
      'css',
      '#render-field',
      'Test plain text value'
    );

    $this->container->get('language_manager')->reset();
    $this->rebuildContainer();
    drupal_flush_all_caches();

    // Then, check the Twig output in French.
    $this->drupalGet('site_settings_sample_data/test_full_site_settings_loader_twig_extension', [
      'language' => $languages['fr'],
    ]);
    $session = $this->assertSession();
    $session->elementTextContains(
      'css',
      '#render-field',
      'FR Test plain text value'
    );
  }

}
