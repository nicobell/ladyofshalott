<?php

namespace Drupal\site_settings\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\site_settings\SiteSettingsLoaderPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configuration admin form for how site settings should behave.
 *
 * @package Drupal\site_settings\Form
 */
class SiteSettingsConfigForm extends ConfigFormBase {

  /**
   * The site settings loader plugin manager.
   *
   * @var \Drupal\site_settings\SiteSettingsLoaderPluginManager
   */
  protected $pluginManager;

  /**
   * Constructs a ContentEntityForm object.
   *
   * @param \Drupal\site_settings\SiteSettingsLoaderPluginManager $plugin_manager
   *   The site settings loader plugin manager.
   */
  public function __construct(SiteSettingsLoaderPluginManager $plugin_manager) {
    $this->pluginManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.site_settings_loader')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [
      'site_settings.config',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'site_settings_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['#id'] = Html::getUniqueId($this->getFormId());
    $config = $this->config('site_settings.config');

    // Generate an array of annotated plugins for generating PDF.
    $plugins = [];
    foreach ($this->pluginManager->getDefinitions() as $pid => $plugin) {
      $plugins[$pid] = $plugin['label'];
    }
    $form['loader_plugin'] = [
      '#title' => $this->t('Loader plugin'),
      '#type' => 'select',
      '#required' => TRUE,
      '#options' => $plugins,
      '#default_value' => 'full',
      '#description' => $this->t('Decide how to load the site settings. See the <a href="@module_homepage" target="_blank">module homepage</a> for details. The full loader is recommended and help texts throughout the user interface are geared at that. The flattened loader is there to maintain backwards compatibility with older versions of this module (though remains supported).', [
        '@module_homepage' => 'https://www.drupal.org/project/site_settings',
      ]),
      '#ajax' => [
        'callback' => '::hideInputs',
        'wrapper' => 'site-settings-config-form',
        'event' => 'change',
        'progress' => [
          'type' => 'throbber',
        ],
      ],
    ];
    if (isset($plugins[$config->get('loader_plugin')])) {
      $form['loader_plugin']['#default_value'] = $config->get('loader_plugin');
    }

    // Global setting.
    $form['template_key'] = [
      '#type' => 'machine_name',
      '#access' => FALSE,
      '#title' => $this->t('Template key'),
      '#description' => $this->t('The key at which site settings should be made available in templates such as {{ site_settings.your_settings_group.your_setting_name }} with a template key of "site_settings".'),
      '#default_value' => $config->get('template_key'),
      '#required' => TRUE,
      '#machine_name' => [
        'exists' => [$this, 'machineNameExists'],
      ],
    ];

    // Disable autoloading.
    $form['disable_auto_loading'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable auto-loading'),
      '#description' => $this->t('By default, site settings are passed to every template. On a larger site with many templates or a site with many site settings, this can have an impact on performance. Please see the project homepage for details on how to implement your own autoloader in your theme or module. Note that you will need to clear the cache for the change to take effect.'),
      '#default_value' => $config->get('disable_auto_loading'),
    ];

    if ($pluginId = $form_state->getValue('loader_plugin')) {
      $plugin = $this->pluginManager->getPlugin($pluginId);
      $access_auto_loading = $plugin->allowAutoload();
      $form['template_key']['#access'] = $access_auto_loading;
      $form['disable_auto_loading']['#access'] = $access_auto_loading;
    }

    // Administrative display settings.
    $form['content_display_settings'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Administrative display settings'),
    ];
    $form['content_display_settings']['hide_description'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide the administrative description'),
      '#description' => $this->t('Automatically hide the administrative description field for all site settings. This avoids having to manage form display on each site setting entity type.'),
      '#default_value' => $config->get('hide_description'),
    ];
    $form['content_display_settings']['hide_advanced'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide the administrative advanced options'),
      '#description' => $this->t('Automatically hide the administrative advanced options (typically revision information). This avoids having to manage form display on each site setting entity type.'),
      '#default_value' => $config->get('hide_advanced'),
    ];
    $form['content_display_settings']['hide_group'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide the administrative group'),
      '#description' => $this->t('Automatically hide the administrative group field for all site settings. This avoids having to manage form display on each site setting entity type.'),
      '#default_value' => $config->get('hide_group'),
    ];
    $form['content_display_settings']['simple_summary'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use a simple auto-administrative summary'),
      '#description' => $this->t('Instead of displaying the rendered entity in a view mode (usually "Teaser") on the site settings list, display a simple auto-generated summary. This provides a basic override to keep the site settings list more like a table of data which can suit many simple use cases.'),
      '#default_value' => $config->get('simple_summary'),
    ];
    $form['content_display_settings']['show_groups_in_menu'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Automatically show Groups in the admin menu'),
      '#description' => $this->t('This feature automatically lists all Groups as quick child links below the Admin > Content > Site Settings link. You must clear the menu cache after changing this setting.'),
      '#default_value' => $config->get('show_groups_in_menu'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Machine name validation callback.
   *
   * This method needs to exist, but there can be only one so it never exists.
   *
   * @param string $value
   *   The input value.
   *
   * @return bool
   *   That the machine name does not exist.
   */
  public function machineNameExists(string $value): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    parent::submitForm($form, $form_state);
    $this->config('site_settings.config')
      ->set('template_key', $form_state->getValue('template_key'))
      ->set('loader_plugin', $form_state->getValue('loader_plugin'))
      ->set('disable_auto_loading', $form_state->getValue('disable_auto_loading'))
      ->set('hide_description', $form_state->getValue('hide_description'))
      ->set('hide_advanced', $form_state->getValue('hide_advanced'))
      ->set('hide_group', $form_state->getValue('hide_group'))
      ->set('simple_summary', $form_state->getValue('simple_summary'))
      ->set('show_groups_in_menu', $form_state->getValue('show_groups_in_menu'))
      ->save();
  }

  /**
   * Hides useless fields for the plugin.
   *
   * @param array $form
   *   An associative array containing the structure of the form..
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   A render array for the settings form.
   */
  public function hideInputs(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild();
    $form['#id'] = 'site-settings-config-form';
    return $form;
  }

}
