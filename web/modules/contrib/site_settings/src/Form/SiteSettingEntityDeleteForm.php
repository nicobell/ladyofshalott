<?php

namespace Drupal\site_settings\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\site_settings\SiteSettingsLoaderPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for deleting Site Setting entities.
 *
 * @ingroup site_settings
 */
class SiteSettingEntityDeleteForm extends ContentEntityDeleteForm {

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
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\site_settings\SiteSettingsLoaderPluginManager $plugin_manager
   *   The site settings loader plugin manager.
   */
  public function __construct(
    EntityRepositoryInterface $entity_repository,
    EntityTypeBundleInfoInterface $entity_type_bundle_info,
    TimeInterface $time,
    SiteSettingsLoaderPluginManager $plugin_manager,
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
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('plugin.manager.site_settings_loader')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {

    // Clear the site settings cache.
    $this->pluginManager->getActiveLoaderPlugin()->clearCache();

    // Submit the parent form.
    parent::submitForm($form, $form_state);
  }

}
