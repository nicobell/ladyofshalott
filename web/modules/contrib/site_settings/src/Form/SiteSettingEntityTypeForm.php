<?php

namespace Drupal\site_settings\Form;

use Drupal\Component\Transliteration\TransliterationInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\site_settings\SiteSettingsLoaderPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The form to create new site setting types.
 *
 * @package Drupal\site_settings\Form
 */
class SiteSettingEntityTypeForm extends EntityForm {

  /**
   * The site settings loader plugin manager.
   *
   * @var \Drupal\site_settings\SiteSettingsLoaderPluginManager
   */
  protected $pluginManager;

  /**
   * The transliteration service.
   *
   * @var \Drupal\Component\Transliteration\TransliterationInterface
   */
  protected TransliterationInterface $transliteration;

  /**
   * Drupal\Core\Language\LanguageManagerInterface definition.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected LanguageManagerInterface $language;

  /**
   * The create new group value.
   *
   * @var string
   */
  protected string $createNewGroupValue = '-- create new group --';

  /**
   * Constructs a ContentEntityForm object.
   *
   * @param \Drupal\site_settings\SiteSettingsLoaderPluginManager $plugin_manager
   *   The site settings loader plugin manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Component\Transliteration\TransliterationInterface $transliteration
   *   The transliteration service.
   */
  public function __construct(
    SiteSettingsLoaderPluginManager $plugin_manager,
    ModuleHandlerInterface $module_handler,
    TransliterationInterface $transliteration,
  ) {
    $this->pluginManager = $plugin_manager;
    $this->moduleHandler = $module_handler;
    $this->transliteration = $transliteration;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.site_settings_loader'),
      $container->get('module_handler'),
      $container->get('transliteration')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\site_settings\Entity\SiteSettingEntityType $site_setting_entity_type */
    $site_setting_entity_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $site_setting_entity_type->label(),
      '#description' => $this->t("The label for the particular setting."),
      '#required' => TRUE,
    ];

    // Gets chosen group for the current Site Settings Entity.
    $group = $site_setting_entity_type->get('group') ?? NULL;

    // Gets all created Site Settings Groups.
    $groups = $this->getGroups();

    if ($groups) {
      // Add create new group to the beginning of the options.
      $groups = [$this->createNewGroupValue => $this->createNewGroupValue] + $groups;

      // Select from existing groups.
      $form['existing_group'] = [
        '#type' => 'select',
        '#title' => $this->t('Choose existing "Group" label'),
        '#options' => $groups,
        '#default_value' => $group,
        '#description' => $this->t("The group this particular setting is in."),
        '#required' => TRUE,
        '#empty_option' => '-- select one --',
        '#empty_value' => '',
      ];
    }
    $form['new_group'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'site_setting_group_entity_type',
      '#title' => $this->t('Create a new "Group" label'),
      '#maxlength' => 255,
      '#selection_handler' => 'default',
      '#description' => $this->t("Create a new group for this site setting."),
      '#required' => FALSE,
      '#autocreate' => [
        'bundle' => 'site_setting_group_entity_type',
        'uid' => $this->currentUser()->id(),
      ],
    ];

    if ($groups) {
      $form['new_group']['#states'] = [
        'visible' => [
          ':input[name="existing_group"]' => ['value' => $this->createNewGroupValue],
        ],
        'required' => [
          ':input[name="existing_group"]' => ['value' => $this->createNewGroupValue],
        ],
      ];
    }

    $form['group'] = [
      '#type' => 'hidden',
      '#default_value' => $site_setting_entity_type->group,
    ];

    $form['multiple'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Multiple'),
      '#default_value' => $site_setting_entity_type->multiple,
      '#description' => $this->t("Whether or not to allow multiple entries for this same setting."),
    ];

    $form['instructions'] = [
      '#markup' => '<p>' . $this->t('Please be diligent to reuse existing fields via the "Manage Fields" tab when creating new Site Settings to avoid performance issues.') . '</p>',
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $site_setting_entity_type->id(),
      '#maxlength' => 32,
      '#machine_name' => [
        'exists' => '\Drupal\site_settings\Entity\SiteSettingEntityType::load',
      ],
      '#disabled' => !$site_setting_entity_type->isNew(),
    ];

    return $form;
  }

  /**
   * Get a list of groups that already exist.
   *
   * @return array
   *   The groups.
   */
  private function getGroups(): array {
    $groups = [];
    $bundles = $this->entityTypeManager
      ->getStorage('site_setting_group_entity_type')
      ->loadMultiple();
    if ($bundles) {
      foreach ($bundles as $bundle) {
        $groups[$bundle->id()] = $bundle->label();
      }
    }

    return $groups;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    // Ensure that either a new group or existing group is set.
    if (
      !$form_state->getValue('existing_group')
      && !$form_state->getValue('new_group')
      && $form_state->getValue('existing_group') != $this->createNewGroupValue
    ) {
      $message = $this->t('Each site setting type must belong to a group (new or existing).');
      if (isset($form['existing_group'])) {
        $form_state->setErrorByName('existing_group', $message);
      }
      else {
        $form_state->setErrorByName('new_group', $message);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): void {
    $values = $form_state->getValues();
    if (!isset($values['existing_group']) || $values['existing_group'] == $this->createNewGroupValue) {
      /** @var \Drupal\site_settings\SiteSettingGroupEntityTypeInterface $new_group */
      $new_group = reset($values['new_group']);
      $new_group->set('id', $this->generateMachineName($new_group->label()));
      $new_group->set('description', '');

      if ($new_group->isNew()) {
        $new_group->save();
      }

      $this->entity->group = $new_group->id();
    }
    else {
      $this->entity->group = $values['existing_group'];
    }
    $this->entity->multiple = $values['multiple'];

    /** @var \Drupal\site_settings\Entity\SiteSettingEntityType $site_setting_entity_type */
    $site_setting_entity_type = $this->entity;
    $status = $site_setting_entity_type->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label Site Setting type.', [
          '%label' => $site_setting_entity_type->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label Site Setting type.', [
          '%label' => $site_setting_entity_type->label(),
        ]));
    }

    // Rebuild the site settings cache.
    $this->pluginManager->getActiveLoaderPlugin()->clearCache();

    $route_name = 'entity.site_setting_entity_type.collection';
    $route_parameters = [];
    if ($this->moduleHandler->moduleExists('field_ui')) {
      // Redirect the user to the add fields screen for this new entity type.
      $route_name = 'entity.site_setting_entity.field_ui_fields';
      $route_parameters = [
        'site_setting_entity_type' => $site_setting_entity_type->id(),
      ];
    }
    $form_state->setRedirect($route_name, $route_parameters);
  }

  /**
   * Generates the human-readable machine name.
   *
   * @param string $value
   *   The value to be transformed.
   *
   * @return string
   *   Generated machine name.
   */
  private function generateMachineName(string $value): string {
    $new_value = $this->transliteration->transliterate($value, LanguageInterface::LANGCODE_DEFAULT, '_');
    $new_value = strtolower($new_value);
    $new_value = preg_replace('/[^a-z0-9_]+/', '_', $new_value);
    return preg_replace('/_+/', '_', $new_value);
  }

}
