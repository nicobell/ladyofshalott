<?php

namespace Drupal\site_settings\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\site_settings\SiteSettingEntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'RenderedSiteSettingsBlock' block.
 */
#[Block(
  id: "single_rendered_site_settings_block",
  admin_label: new TranslatableMarkup("Rendered site settings block")
)]
class RenderedSiteSettingsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Construct.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Drupal\Core\Entity\EntityTypeManagerInterface.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $displayRepository
   *   Drupal\Core\Entity\EntityDisplayRepositoryInterface.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected RendererInterface $renderer,
    protected EntityDisplayRepositoryInterface $displayRepository,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
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
      $container->get('renderer'),
      $container->get('entity_display.repository'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'setting' => NULL,
      'label_display' => FALSE,
      'view_mode' => NULL,
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
      '#ajax' => [
        'callback' => [$this, 'viewModeAjaxCallback'],
        'event' => 'autocompleteclose change',
        'wrapper' => 'edit-view-mode',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Loading...'),
        ],
      ],
    ];
    if (isset($this->configuration['setting']) && !empty($this->configuration['setting'])) {
      $setting_entity_type = $this->entityTypeManager
        ->getStorage('site_setting_entity_type')
        ->load($this->configuration['setting']);
      $form['setting']['#default_value'] = $setting_entity_type;

      // Load the view_mode element if the site setting entity type
      // still exists.
      $view_mode_default = $this->configuration['view_mode'];
      if ($setting_entity_type instanceof SiteSettingEntityTypeInterface) {
        $form['view_mode'] = $this->viewModeFormElement($setting_entity_type, $view_mode_default);
      }
    }

    if (!isset($form['view_mode'])) {
      $form['view_mode'] = $this->viewModeFormElement();
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    $this->configuration['setting'] = $form_state->getValue('setting');
    $this->configuration['view_mode'] = $form_state->getValue('view_mode');
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if the entity type doesn't exist.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the storage handler couldn't be loaded.
   */
  public function build(): array {
    $build = [
      '#cache' => [
        'tags' => ['site_setting_entity_list'],
      ],
    ];

    $entities = $this->entityTypeManager
      ->getStorage('site_setting_entity')
      ->loadByProperties(['type' => $this->configuration['setting']]);

    if (empty($entities)) {
      return $build;
    }

    $view_builder = $this->entityTypeManager->getViewBuilder('site_setting_entity');
    $view_mode = $this->configuration['view_mode'] ?: 'default';

    // Loop through the entities and their fields.
    foreach ($entities as $entity) {
      $pre_render = $view_builder->view($entity, $view_mode);
      $render_output = $this->renderer->render($pre_render);

      $build[] = [
        '#markup' => $render_output,
      ];
    }

    return $build;
  }

  /**
   * Utility to get view_modes from $entity_type.
   *
   * @param \Drupal\site_settings\SiteSettingEntityTypeInterface $entity_type
   *   The site settings entity type.
   *
   * @return array
   *   The view modes available as an options array.
   */
  public function getViewModes(SiteSettingEntityTypeInterface $entity_type): array {
    return $this->displayRepository->getViewModeOptionsByBundle('site_setting_entity', $entity_type->id());
  }

  /**
   * Ajax callback to load available view modes for the site setting.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The form element.
   */
  public function viewModeAjaxCallback(array &$form, FormStateInterface $form_state): array {
    $entity_id = $form_state->getValue('settings')['setting'];
    if (!$entity_id) {
      return $form['settings']['view_mode'];
    }
    $entity_type = $this->entityTypeManager->getStorage('site_setting_entity_type')->load($entity_id);
    if ($entity_type == NULL) {
      $form['settings']['view_mode']['#options'] = ['default' => $this->t('Default')];
    }
    else {
      $form['settings']['view_mode']['#options'] = $this->getViewModes($entity_type);
    }
    return $form['settings']['view_mode'];
  }

  /**
   * Returns view_mode element.
   *
   * @param \Drupal\site_settings\SiteSettingEntityTypeInterface $entity_type
   *   The site settings entity type.
   * @param string $default
   *   The default value to select.
   *
   * @return array
   *   The view mode form api render array.
   */
  public function viewModeFormElement(?SiteSettingEntityTypeInterface $entity_type = NULL, ?string $default = NULL) {
    $options = NULL;
    $element = [
      '#type' => 'select',
      '#title' => $this->t('Select View Mode'),
      '#weight' => 21,
      '#prefix' => '<div id="edit-view-mode">',
      '#suffix' => '</div>',
      '#required' => TRUE,
    ];
    if ($entity_type) {
      $options = $this->getViewModes($entity_type);
    }
    if (is_array($options) && count($options) > 0) {
      $element['#options'] = $options;
      if ($default !== NULL) {
        $element['#default_value'] = $default;
      }
    }
    else {
      $element['#options'] = ['default' => $this->t('Default')];
    }
    return $element;
  }

}
