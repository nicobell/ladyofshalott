<?php

namespace Drupal\site_settings\Plugin\SiteSettingsLoader;

use Drupal\site_settings\SiteSettingsLoaderBase;
use Drupal\site_settings\SiteSettingsLoaderInterface;

/**
 * Load the site settings in a standard Drupal way.
 *
 * This loads the full site settings objects, respecting
 * the manage display settings. It is typically used
 * with twig filters for quick rendering in any template.
 *
 * @SiteSettingsLoader(
 *   id = "full",
 *   label = @Translation("Full Site Settings Loader"),
 *   autoload_by_default = FALSE
 * )
 *
 * @package Drupal\site_settings
 */
class FullSiteSettingsLoader extends SiteSettingsLoaderBase implements SiteSettingsLoaderInterface {

  /**
   * {@inheritDoc}
   */
  public function allowAutoload(): bool {
    return FALSE;
  }

  /**
   * Load site settings by group.
   *
   * @param string $group
   *   The name of the group.
   * @param string|null $langcode
   *   The language code.
   *
   * @return array
   *   All settings within the given group.
   */
  public function loadByGroup(string $group, ?string $langcode = NULL): array {
    if ($langcode) {
      return $this->entityTypeManager
        ->getStorage('site_setting_entity')
        ->loadByProperties([
          'group' => $group,
          'langcode' => $langcode,
        ]);
    }
    return $this->entityTypeManager
      ->getStorage('site_setting_entity')
      ->loadByProperties(['group' => $group]);
  }

  /**
   * Load site settings by group.
   *
   * @param bool $rebuild_cache
   *   Force rebuilding of the cache by setting to true.
   * @param string|null $langcode
   *   The language code.
   *
   * @return array
   *   All settings.
   */
  public function loadAll(bool $rebuild_cache = FALSE, ?string $langcode = NULL): array {
    if ($langcode) {
      return $this->entityTypeManager
        ->getStorage('site_setting_entity')
        ->loadByProperties([
          'langcode' => $langcode,
        ]);
    }
    return $this->entityTypeManager
      ->getStorage('site_setting_entity')
      ->loadMultiple();
  }

  /**
   * Rebuild the cache.
   */
  public function rebuildCache($langcode): void {
    $this->clearCache();
    // Do nothing else, leave the rest to entity type manager.
  }

  /**
   * Clear the cache.
   */
  public function clearCache(): void {
    $this->cacheTagsInvalidator->invalidateTags([
      'site_setting_entity_list',
    ]);
  }

}
