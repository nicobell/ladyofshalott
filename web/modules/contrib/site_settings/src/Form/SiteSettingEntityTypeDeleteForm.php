<?php

namespace Drupal\site_settings\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\site_settings\SiteSettingsLoaderPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the form to delete Site Setting type entities.
 */
class SiteSettingEntityTypeDeleteForm extends EntityConfirmFormBase {

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
    return new static($container->get('plugin.manager.site_settings_loader'));
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion(): TranslatableMarkup {
    return $this->t('Are you sure you want to delete %name?', ['%name' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    return new Url('entity.site_setting_entity_type.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText(): TranslatableMarkup {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if the entity type doesn't exist.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the storage handler couldn't be loaded.
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {

    // Get site settings of this type.
    $entities = $this->entityTypeManager
      ->getStorage('site_setting_entity')
      ->loadByProperties(['type' => $this->entity->id()]);

    if (!empty($entities)) {

      // Delete site settings of this type.
      $controller = $this->entityTypeManager->getStorage('site_setting_entity');
      $controller->delete($entities);
    }

    // Delete the site setting entity type.
    $this->entity->delete();

    $this->messenger()->addMessage($this->t('Successfully deleted the "@label" site setting.', [
      '@label' => $this->entity->label(),
    ]));

    // Rebuild the site settings cache.
    $this->pluginManager->getActiveLoaderPlugin()->clearCache();

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
