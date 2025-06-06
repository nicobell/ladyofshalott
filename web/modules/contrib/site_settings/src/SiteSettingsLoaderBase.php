<?php

namespace Drupal\site_settings;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The default service to load the site settings.
 *
 * @package Drupal\site_settings
 */
abstract class SiteSettingsLoaderBase extends PluginBase implements SiteSettingsLoaderInterface, ContainerFactoryPluginInterface {

  /**
   * Constructor.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeRepositoryInterface $entityTypeRepository
   *   The entity type repository.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cacheTagsInvalidator
   *   The cache tags invalidator.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityTypeRepositoryInterface $entityTypeRepository,
    protected LanguageManagerInterface $languageManager,
    protected CacheTagsInvalidatorInterface $cacheTagsInvalidator,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_type.repository'),
      $container->get('language_manager'),
      $container->get('cache_tags.invalidator')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function allowAutoload(): bool {
    return TRUE;
  }

  /**
   * {@inheritDoc}
   */
  public function getGroups(): array {
    return $this->entityTypeManager
      ->getStorage('site_setting_group_entity_type')
      ->loadMultiple();
  }

  /**
   * {@inheritdoc}
   */
  public function loadByEntityBundleClass(string $class, ?string $langcode = NULL): array {
    $storage = $this->entityTypeManager->getStorage('site_setting_entity');
    $type = $storage->getBundleFromClass($class);
    $values = [
      'type' => $type,
    ];
    if ($langcode) {
      $values['langcode'] = $langcode;
    }
    return $storage->loadByProperties($values);
  }

}
