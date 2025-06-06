<?php

namespace Drupal\site_settings\Plugin\SiteSettingsLoader;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Transliteration\TransliterationInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\site_settings\Entity\SiteSettingEntity;
use Drupal\site_settings\SiteSettingsLoaderBase;
use Drupal\site_settings\SiteSettingsLoaderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Load the site settings in a flattened way.
 *
 * This is useful for simple site settings like footer copyright text,
 * social media links, and other simple elements. It efficiently
 * loads many site settings and makes them easily available in all
 * twig templates. It is not suitable for more complex cases as it
 * does not follow Drupal's standard rendering patterns - it flattens
 * site settings into compact data for quick and performant retrieval
 * and loses details and benefits of full objects and full render
 * arrays as a result.
 *
 * @SiteSettingsLoader(
 *   id = "flattened",
 *   label = @Translation("Flattened Site Settings Loader"),
 *   autoload_by_default = TRUE
 * )
 *
 * @package Drupal\site_settings
 */
class FlattenedSiteSettingsLoader extends SiteSettingsLoaderBase implements SiteSettingsLoaderInterface {
  use StringTranslationTrait;

  /**
   * Default image width.
   *
   * @var int
   */
  protected $defaultImageWidth = 25;

  /**
   * Default image height.
   *
   * @var int
   */
  protected $defaultImageHeight = 25;

  /**
   * Cache BIN for settings.
   *
   * @var string
   */
  protected const SITE_SETTINGS_CACHE_BIN = 'site_settings';

  /**
   * Cache CID for settings.
   *
   * @var string
   */
  protected const SITE_SETTINGS_CACHE_CID = 'settings';

  /**
   * Variable to store the loaded settings.
   *
   * @var array
   */
  protected $settings;

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
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Component\Transliteration\TransliterationInterface $transliteration
   *   The transliteration service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityTypeRepositoryInterface $entityTypeRepository,
    protected LanguageManagerInterface $languageManager,
    protected CacheTagsInvalidatorInterface $cacheTagsInvalidator,
    protected RendererInterface $renderer,
    protected TransliterationInterface $transliteration,
  ) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $entityTypeManager,
      $this->entityTypeRepository,
      $languageManager,
      $cacheTagsInvalidator,
    );
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
      $container->get('cache_tags.invalidator'),
      $container->get('renderer'),
      $container->get('transliteration')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function loadByGroup(string $group, ?string $langcode = NULL): array {
    $this->loadAll(FALSE, $langcode);
    $group = $this->groupKey($group);
    return $this->settings[$group] ?? [];
  }

  /**
   * {@inheritDoc}
   */
  public function loadAll(bool $rebuild_cache = FALSE, ?string $langcode = NULL): array {
    $langcode = $langcode ?? $this->languageManager->getCurrentLanguage()->getId();
    // @phpstan-ignore-next-line
    if (!$rebuild_cache && $cache = \Drupal::cache(self::SITE_SETTINGS_CACHE_BIN)->get(self::SITE_SETTINGS_CACHE_CID . ':' . $langcode)) {
      $this->settings = $cache->data;
    }
    else {
      $this->rebuildCache($langcode);
    }
    return $this->settings;
  }

  /**
   * {@inheritDoc}
   */
  public function rebuildCache($langcode): void {
    $this->buildSettings($langcode);
    // @phpstan-ignore-next-line
    \Drupal::cache(self::SITE_SETTINGS_CACHE_BIN)->set(self::SITE_SETTINGS_CACHE_CID . ':' . $langcode, $this->settings);
  }

  /**
   * {@inheritDoc}
   */
  public function clearCache(): void {
    // @phpstan-ignore-next-line
    \Drupal::cache(self::SITE_SETTINGS_CACHE_BIN)->deleteAll();
  }

  /**
   * Build the settings array.
   */
  private function buildSettings($langcode): void {

    // Clear the existing settings to avoid empty keys.
    $this->settings = [];

    // Get all site settings.
    $setting_entities = SiteSettingEntity::loadMultiple();

    // Get entity type configurations at once for performance.
    $entities = [];
    $entity_type = $this->entityTypeManager->getStorage('site_setting_entity_type');
    if ($entity_type) {
      $entities = $entity_type->loadMultiple();
    }

    foreach ($setting_entities as $entity) {
      /** @var \Drupal\site_settings\Entity\SiteSettingEntity $entity */
      if (method_exists($entity, 'hasTranslation') && $entity->hasTranslation($langcode)) {
        $entity = $entity->getTranslation($langcode);
      }

      // Get data.
      $group = $entity->get('group')->getString();
      $type = $entity->get('type')->getString();
      $multiple = (isset($entities[$type]) ? $entities[$type]->multiple : FALSE);

      // If we have multiple, set as array of entities.
      if ($multiple) {
        if (!isset($this->settings[$group][$type])) {
          $this->settings[$group][$type] = [];
        }
        $this->settings[$group][$type][] = $this->getValues($entity);
      }
      else {
        $this->settings[$group][$type] = $this->getValues($entity);
      }
    }

    // Get all possibilities and fill with empty values.
    $bundles = $this->entityTypeManager
      ->getStorage('site_setting_entity_type')
      ->loadMultiple();
    foreach ($bundles as $bundle) {
      if (!is_string($bundle->group)) {
        continue;
      }
      $group = $this->groupKey($bundle->group);
      $id = $bundle->id();

      // Only fill if not yet set.
      if (!isset($this->settings[$group][$id])) {
        $this->settings[$group][$id] = '';
      }
    }
  }

  /**
   * Get the values from the entity and return in as simple an array possible.
   *
   * @param object $entity
   *   Field Entity.
   *
   * @return mixed
   *   The values.
   */
  private function getValues(EntityInterface $entity): mixed {
    $build = [];
    $fields = $entity->getFields();
    foreach ($fields as $key => $field) {
      /** @var \Drupal\Core\Field\FieldItemInterface $field */
      /** @var \Drupal\Core\Field\FieldDefinitionInterface $field_definition */
      $field_definition = $field->getFieldDefinition();

      // Exclude fields on the object that are base config.
      if (!method_exists(get_class($field_definition), 'isBaseField') || !$field_definition->isBaseField()) {

        $value = $this->getValue($field);
        if ($value || $field_definition->getType() == 'boolean') {
          $build[$key] = $value;

          // Add supplementary data to some field types.
          switch ($field_definition->getType()) {
            case 'link':
              $build[$key] = $this->addSupplementaryLinkData($build[$key], $field);
              break;

            case 'image':
            case 'file':
            case 'svg_image_field':
              $build[$key] = $this->addSupplementaryImageData($build[$key], $field);
              break;
          }
        }
      }
    }

    // Flatten array as much as possible.
    if (count($build) == 1) {

      // Pass back single value.
      return reset($build);
    }
    elseif (count($build) == 2 && isset($build['user_id'])) {

      // If site setting is translated, remove meta user_id field.
      unset($build['user_id']);
      return reset($build);
    }
    else {

      // Unable to flatten further, return for array.
      return $build;
    }
  }

  /**
   * Get the value for the particular field item.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $field
   *   The field object.
   *
   * @return mixed
   *   The value or false.
   */
  private function getValue(FieldItemListInterface $field): mixed {
    if ($value = $field->getValue()) {

      // Store the values in as flat a way as possible based on what is set.
      if (count($value) <= 1) {
        $item = reset($value);
        if (count($item) <= 1) {
          return reset($item);
        }
        else {
          return $item;
        }
      }
      else {
        return $value;
      }
    }
    return FALSE;
  }

  /**
   * Add supplementary link data to the site settings.
   *
   * @param array $data
   *   The existing data.
   * @param object $field
   *   The field object.
   *
   * @return array
   *   The data with the new supplementary information included.
   */
  private function addSupplementaryLinkData(array $data, object $field): array {
    if (isset($field->uri) && $url = Url::fromUri($field->uri)) {
      $data = array_merge($data, [
        'url' => $url,
      ]);
    }
    return $data;
  }

  /**
   * Add supplementary image data to the site settings.
   *
   * @param array $data
   *   The existing data.
   * @param object $field
   *   The field object.
   *
   * @return array
   *   The data with the new supplementary information included.
   */
  private function addSupplementaryImageData(array $data, object $field): array {
    if ($entities = $field->referencedEntities()) {
      if (count($entities) > 1) {

        // If multiple images add data to each.
        foreach ($data as $key => $sub_image_data) {
          /** @var \Drupal\file\FileInterface $entity */
          $entity = $entities[$key];
          $data[$key]['filename'] = $entity->getFilename();
          $data[$key]['uri'] = $entity->getFileUri();
          $data[$key]['mime_type'] = $entity->getMimeType();
          $data[$key]['size'] = $entity->getSize();
          $data[$key]['is_permanent'] = $entity->isPermanent();
        }
      }
      else {

        // Add the entity to the image.
        /** @var \Drupal\file\FileInterface $entity */
        $entity = reset($entities);
        $data['filename'] = $entity->getFilename();
        $data['uri'] = $entity->getFileUri();
        $data['mime_type'] = $entity->getMimeType();
        $data['size'] = $entity->getSize();
        $data['is_permanent'] = $entity->isPermanent();
      }
    }
    return $data;
  }

  /**
   * Create a lowercase key with no spaces from the group label.
   *
   * @param string $group
   *   The group key.
   *
   * @return string
   *   Updated group key.
   */
  private function groupKey(string $group): string {
    return strtolower(str_replace(' ', '_', $group));
  }

  /**
   * Set default image size output.
   *
   * @param int $width
   *   The max image width in pixels.
   * @param int $height
   *   The max image height in pixels.
   */
  public function setDefaultImageSizeOutput($width, $height): void {
    $this->defaultImageWidth = $width;
    $this->defaultImageHeight = $height;
  }

  /**
   * Render the value of the added fields.
   *
   * @param object $field
   *   The field to render.
   *
   * @return \Drupal\Component\Render\MarkupInterface|string
   *   The rendered html markup.
   */
  public function renderField(object $field): MarkupInterface|string {

    // Get information about the field.
    $definition = $field->getFieldDefinition();
    $field_type = $definition->getType();

    // Depending on the type of field, decide how to render.
    switch ($field_type) {
      case 'image':
        return $this->renderImage($field);

      default:
        return $this->renderDefault($field, $field_type);

    }
  }

  /**
   * Render a small version of the image.
   *
   * @param object $field
   *   The field to render.
   *
   * @return string
   *   The rendered html markup.
   */
  protected function renderImage(object $field): string {
    if (is_object($field) && isset($field->entity)) {
      $build = [
        '#theme' => 'image_style',
        '#width' => $this->defaultImageWidth,
        '#height' => $this->defaultImageHeight,
        '#style_name' => 'thumbnail',
        '#uri' => $field->entity->getFileUri(),
      ];
    }
    else {
      $build['#plain_text'] = $this->t('(none)');
    }
    return $this->renderer->render($build);
  }

  /**
   * Render a normal text value.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $field
   *   The field to render.
   * @param string $field_type
   *   The field type to render.
   *
   * @return \Drupal\Component\Render\MarkupInterface
   *   The rendered html markup.
   */
  protected function renderDefault(FieldItemListInterface $field, string $field_type): MarkupInterface {
    $view_builder = $this->entityTypeManager->getViewBuilder('site_setting_entity');
    $build = $view_builder->viewField($field, [
      'type' => $field_type,
      'label' => 'hidden',
    ]);
    return $this->renderer->render($build);
  }

  /**
   * {@inheritdoc}
   */
  public function loadByEntityBundleClass(string $class, ?string $langcode = NULL): array {
    $entities = parent::loadByEntityBundleClass($class, $langcode);
    $results = [];
    if ($entities) {
      $settings = $this->loadAll(FALSE, $langcode);
      foreach ($entities as $entity) {
        /** @var \Drupal\site_settings\SiteSettingEntityInterface $entity */
        if (isset($settings[$entity->getGroup()][$entity->getType()])) {
          $results[$entity->getGroup()][$entity->getType()] = $settings[$entity->getGroup()][$entity->getType()];
        }
      }
    }
    return $results;
  }

}
