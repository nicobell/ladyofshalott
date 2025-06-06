<?php

namespace Drupal\site_settings;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Provides an interface for a site settings loader.
 */
interface SiteSettingsLoaderInterface extends PluginInspectionInterface {

  /**
   * Is this plugin suitable for autoload?
   *
   * Plugins can set autoload false as the default,
   * but users could still opt-in to have loadAll()
   * called on every preprocess. To prevent opt-in,
   * set this to FALSE.
   *
   * @return bool
   *   Whether the plugin can autoload.
   */
  public function allowAutoload(): bool;

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
  public function loadByGroup(string $group, ?string $langcode = NULL): array;

  /**
   * Load site settings by entity bundle class.
   *
   * @param string $class
   *   The name of the class.
   * @param string|null $langcode
   *   The language code.
   *
   * @return array
   *   All settings that use the given entity bundle class.
   */
  public function loadByEntityBundleClass(string $class, ?string $langcode = NULL): array;

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
  public function loadAll(bool $rebuild_cache = FALSE, ?string $langcode = NULL): array;

  /**
   * Get all available groups.
   *
   * @return \Drupal\site_settings\SiteSettingGroupEntityTypeInterface[]
   *   An array of site settings groups keyed by group machine name.
   */
  public function getGroups(): array;

  /**
   * Rebuild the cache.
   */
  public function rebuildCache($langcode): void;

  /**
   * Clear the cache.
   */
  public function clearCache(): void;

}
