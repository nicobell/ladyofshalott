<?php

namespace Drupal\site_settings;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides the PDF generator plugin manager.
 */
class SiteSettingsLoaderPluginManager extends DefaultPluginManager {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Manages the site settings loader plugins.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cache_backend,
    ModuleHandlerInterface $module_handler,
    ConfigFactoryInterface $config_factory,
  ) {
    parent::__construct(
      'Plugin/SiteSettingsLoader',
      $namespaces,
      $module_handler,
      'Drupal\site_settings\SiteSettingsLoaderInterface',
      'Drupal\site_settings\Annotation\SiteSettingsLoader'
    );
    $this->alterInfo('site_settings_loader_plugin_info');
    $this->setCacheBackend($cache_backend, 'site_settings_loader_plugins');
    $this->configFactory = $config_factory;
  }

  /**
   * Get the active loader plugin.
   *
   * @return \Drupal\site_settings\SiteSettingsLoaderInterface
   *   The loader plugin.
   */
  public function getActiveLoaderPlugin(): SiteSettingsLoaderInterface|false {
    $loader_plugin = $this->configFactory
      ->get('site_settings.config')
      ->get('loader_plugin');
    if (!$loader_plugin) {
      return FALSE;
    }
    return $this->createInstance($loader_plugin);
  }

  /**
   * Set the active loader plugin.
   */
  public function setActiveLoaderPlugin($plugin_id): void {
    $this->configFactory
      ->getEditable('site_settings.config')
      ->set('loader_plugin', $plugin_id)
      ->save();
  }

  /**
   * Gets the loader plugin by id.
   *
   * @param string $plugin_id
   *   The plugin id.
   *
   * @return \Drupal\site_settings\SiteSettingsLoaderInterface
   *   The loader plugin.
   */
  public function getPlugin(string $plugin_id): SiteSettingsLoaderInterface {
    return $this->createInstance($plugin_id);
  }

}
