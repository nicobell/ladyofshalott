<?php

namespace Drupal\site_settings\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\site_settings\SiteSettingsLoaderPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'SimpleSiteSettingsBlock' block.
 *
 * @Block(
 *  id = "simple_site_settings_block",
 *  admin_label = @Translation("Simple site settings block"),
 * )
 */
class SimpleSiteSettingsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The site settings loader plugin manager.
   *
   * @var \Drupal\site_settings\SiteSettingsLoaderPluginManager
   */
  protected $pluginManager;

  /**
   * Construct.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Drupal\Core\Entity\EntityTypeManagerInterface.
   * @param \Drupal\site_settings\SiteSettingsLoaderPluginManager $plugin_manager
   *   The site settings loader plugin manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    SiteSettingsLoaderPluginManager $plugin_manager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->pluginManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.site_settings_loader')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'setting' => NULL,
      'label_display' => FALSE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {

    // Allow selection of a site settings entity type.
    $form['setting'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'site_setting_entity_type',
      '#title' => $this->t('Site setting type'),
      '#weight' => '20',
      '#required' => TRUE,
    ];
    if (isset($this->configuration['setting']) && !empty($this->configuration['setting'])) {
      $setting_entity_type = $this->entityTypeManager
        ->getStorage('site_setting_entity_type')
        ->load($this->configuration['setting']);
      $form['setting']['#default_value'] = $setting_entity_type;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    $this->configuration['setting'] = $form_state->getValue('setting');
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if the entity type doesn't exist.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Exception
   *   Thrown if the storage handler couldn't be loaded.
   */
  public function build(): array {
    $build = [
      '#cache' => [
        'tags' => ['site_setting_entity_list'],
      ],
    ];

    $base_fields = [];

    // Get all settings in the selected bundle.
    $entities = $this->entityTypeManager
      ->getStorage('site_setting_entity')
      ->loadByProperties(['type' => $this->configuration['setting']]);

    if (empty($entities)) {
      return $build;
    }

    // Loop through the entities and their fields.
    foreach ($entities as $entity) {
      /** @var \Drupal\site_settings\SiteSettingEntityInterface $entity */

      // Determine which fields to exclude from render.
      if (!$base_fields) {
        $base_fields = array_keys($entity->getEntityType()->getKeys());
        $base_fields = array_merge($base_fields, [
          'name',
          'user_id',
          'type',
          'created',
          'changed',
        ]);
      }

      /** @var \Drupal\site_settings\SiteSettingsLoaderInterface $site_settings_loader */
      $site_settings_loader = $this->pluginManager->getActiveLoaderPlugin();

      // Maintain backwards compatibility for site settings block with
      // flattened loader which previously set default image size output.
      if (method_exists($site_settings_loader, 'setDefaultImageSizeOutput')) {

        // Get the renderer for a basic rendering. Users can use the templating
        // system to do something more advanced.
        $site_settings_loader->setDefaultImageSizeOutput(400, 400);
      }

      $fields = $entity->getFields();
      foreach ($fields as $key => $field) {

        // Exclude base fields from output.
        if (!in_array($key, $base_fields) && method_exists(get_class($field), 'getFieldDefinition')) {
          $build[] = [
            '#markup' => $site_settings_loader->renderField($field),
          ];
        }
      }
    }

    return $build;
  }

}
