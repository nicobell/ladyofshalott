<?php

namespace Drupal\site_settings\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides menu links for the different settings entities.
 */
class SiteSettingsMenuItemsDeriver extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The site settings loader.
   *
   * @var \Drupal\site_settings\SiteSettingsLoaderInterface
   */
  protected $loaderPlugin;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $config;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    $instance = new static();
    /** @var \Drupal\site_settings\SiteSettingsLoaderPluginManager $loader */
    $loader = $container->get('plugin.manager.site_settings_loader');
    $instance->loaderPlugin = $loader->getActiveLoaderPlugin();
    $instance->config = $container->get('config.factory');
    $instance->moduleHandler = $container->get('module_handler');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {

    // Only show if enabled.
    if (!$this->config->get('site_settings.config')->get('show_groups_in_menu')) {
      return parent::getDerivativeDefinitions($base_plugin_definition);
    }

    // Add each Group pre-filtered list below the Site Settings page.
    $groups = $this->loaderPlugin->getGroups();
    foreach ($groups as $group) {
      /** @var \Drupal\site_settings\SiteSettingGroupEntityTypeInterface $group */
      $this->derivatives['entity.site_setting_group.' . $group->id() . '.collection'] = [
        'title' => $group->label(),
        'route_name' => 'entity.site_setting_entity.collection',
        'options' => [
          'query' => [
            'group' => $group->id(),
          ],
        ],
      ] + $base_plugin_definition;
      if ($this->moduleHandler->moduleExists('navigation')) {
        // Add derivative menu item to the 'content' menu provided by navigation
        // module, instead of the default admin menu.
        $this->derivatives['entity.site_setting_group.' . $group->id() . '.collection']['menu_name'] = 'content';
        // Alter the derivative menu parent, so it gets added as a child of the
        // general "Site settings" content menu item.
        $this->derivatives['entity.site_setting_group.' . $group->id() . '.collection']['parent'] = 'site_settings.navigation.content';
      }
    }

    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
