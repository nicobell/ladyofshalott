<?php

namespace Drupal\site_settings\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\site_settings\Entity\SiteSettingEntityType;
use Drupal\site_settings\SiteSettingsLoaderPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for Site Setting edit forms.
 *
 * @ingroup site_settings
 */
class SiteSettingEntityForm extends ContentEntityForm {

  /**
   * The site settings loader plugin manager.
   *
   * @var \Drupal\site_settings\SiteSettingsLoaderPluginManager
   */
  protected $pluginManager;

  /**
   * Constructs a ContentEntityForm object.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\site_settings\SiteSettingsLoaderPluginManager $plugin_manager
   *   The site settings loader plugin manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(
    EntityRepositoryInterface $entity_repository,
    SiteSettingsLoaderPluginManager $plugin_manager,
    ?EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL,
    ?TimeInterface $time = NULL,
  ) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);

    $this->pluginManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('plugin.manager.site_settings_loader'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    /** @var \Drupal\site_settings\Entity\SiteSettingEntity $entity */
    $form = parent::buildForm($form, $form_state);
    $site_settings_entity_type = SiteSettingEntityType::load($this->entity->getType());

    $form['heading1'] = [
      '#markup' => '<h2>' . $site_settings_entity_type->get('label') . '</h2>',
      '#weight' => -100,
    ];

    // Set entity title and group to match the bundle.
    $form['name']['widget'][0]['value']['#value'] = $site_settings_entity_type->get('label');
    $groupId = $site_settings_entity_type->get('group');
    $group = $this->entityRepository->getActive('site_setting_group_entity_type', $groupId);
    $form['group']['widget'][0]['target_id']['#value'] = $group->label();

    // Hide fields.
    hide($form['name']);
    hide($form['user_id']);
    $form['group']['#disabled'] = TRUE;
    if (isset($form['multiple'])) {
      hide($form['multiple']);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    $entity = $this->entity;
    $entity_bundle = $entity->bundle();
    $entity_type = $entity->getEntityType()->getBundleEntityType();

    // Get existing entities in this settings bundle.
    $query = $this->entityTypeManager->getStorage('site_setting_entity')->getQuery();
    $query->accessCheck(TRUE);
    $query->condition('type', $entity_bundle);
    $existing = $query->execute();
    $bundle = $this->entityTypeManager->getStorage($entity_type)->load($entity_bundle);

    if (!$bundle->multiple) {
      if (count($existing) > 0 && $entity->id() != reset($existing)) {
        $form_state->setErrorByName('name', $this->t('There can only be one of this setting.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): void {
    // Save the form.
    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label Site Setting.', [
          '%label' => $this->entity->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label Site Setting.', [
          '%label' => $this->entity->label(),
        ]));
    }

    // Clear the site settings cache.
    $this->pluginManager->getActiveLoaderPlugin()->clearCache();

    $form_state->setRedirect('entity.site_setting_entity.collection');
  }

}
