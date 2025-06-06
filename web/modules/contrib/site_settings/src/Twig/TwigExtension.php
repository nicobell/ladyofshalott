<?php

namespace Drupal\site_settings\Twig;

use Drupal\Component\Uuid\Uuid;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\TranslatableInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\site_settings\Entity\SiteSettingEntity;
use Drupal\site_settings\SiteSettingEntityInterface;
use Drupal\site_settings\SiteSettingsLoaderPluginManager;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig extension to load site settings.
 *
 * This is primarily aimed at the full site settings loader,
 * but can be used by any loader.
 */
class TwigExtension extends AbstractExtension {

  /**
   * Constructs the Site Settings Twig Extension.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected SiteSettingsLoaderPluginManager $pluginManager,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function getFunctions(): array {
    return [
      new TwigFunction('site_setting', $this->siteSetting(...)),
      new TwigFunction('all_site_settings', $this->allSiteSettings(...)),
      new TwigFunction('site_settings_by_group', $this->siteSettingsByGroup(...)),
      new TwigFunction('site_settings_by_name', $this->siteSettingsByName(...)),
      new TwigFunction('site_setting_field', $this->singleSiteSettingsByNameAndField(...)),
      new TwigFunction('site_setting_entity_by_name', $this->siteSettingEntityByName(...)),
    ];
  }

  /**
   * Returns the render array of a site setting entity.
   *
   * Duplicates twig_tweak drupal_entity(), avoiding having
   * twig_tweak as a dependency. This loads the full entity
   * regardless of the chosen site settings loader.
   *
   * @param string $selector
   *   The uuid or id of the entity to load.
   * @param string $view_mode
   *   The view mode to render.
   * @param ?string $langcode
   *   The language code to render the view mode in.
   * @param bool $check_access
   *   Whether to check access.
   *
   * @return array
   *   The render array or an empty array.
   *
   * @see \Drupal\twig_tweak\TwigExtension::drupalEntity()
   */
  public function siteSetting(
    string $selector,
    string $view_mode = 'full',
    ?string $langcode = NULL,
    bool $check_access = TRUE,
  ): array {
    $storage = $this->entityTypeManager->getStorage('site_setting_entity');

    // Load by UUID, falling back to ID.
    if (Uuid::isValid($selector)) {
      $site_settings = $storage->loadByProperties(['uuid' => $selector]);
    }
    else {
      $site_settings = [$storage->load($selector)];
    }
    return $this->renderSiteSettings($site_settings, $view_mode, $langcode, $check_access, FALSE);
  }

  /**
   * Returns a render array of a site setting's entities by machine name.
   *
   * @param string $selector
   *   The uuid or id of the entity to load.
   * @param string $view_mode
   *   The view mode to render.
   * @param ?string $langcode
   *   The language code to render the view mode in.
   * @param bool $check_access
   *   Whether to check access.
   *
   * @return array
   *   The render array or an empty array.
   */
  public function siteSettingsByName(
    string $selector,
    string $view_mode = 'full',
    ?string $langcode = NULL,
    bool $check_access = TRUE,
  ): array {
    $storage = $this->entityTypeManager->getStorage('site_setting_entity');

    // Load by machine name.
    $site_settings = $storage->loadByProperties(['type' => $selector]);
    return $this->renderSiteSettings($site_settings, $view_mode, $langcode, $check_access);
  }

  /**
   * Returns an array of site setting entity render arrays.
   *
   * @param string $view_mode
   *   The view mode to render.
   * @param ?string $langcode
   *   The language code to render the view mode in.
   * @param bool $check_access
   *   Whether to check access.
   *
   * @return array
   *   Render array.
   */
  public function allSiteSettings(
    string $view_mode = 'full',
    ?string $langcode = NULL,
    bool $check_access = TRUE,
  ): array {
    $site_settings = $this->pluginManager
      ->getActiveLoaderPlugin()
      ->loadAll(FALSE, $langcode);
    return $this->renderSiteSettings($site_settings, $view_mode, $langcode, $check_access);
  }

  /**
   * Returns an array of site setting entity render arrays.
   *
   * @param string $group
   *   The site settings group entity to load.
   * @param string $view_mode
   *   The view mode to render.
   * @param ?string $langcode
   *   The language code to render the view mode in.
   * @param bool $check_access
   *   Whether to check access.
   *
   * @return array
   *   Render array.
   */
  public function siteSettingsByGroup(
    string $group,
    string $view_mode = 'full',
    ?string $langcode = NULL,
    bool $check_access = TRUE,
  ): array {
    $site_settings = $this->pluginManager
      ->getActiveLoaderPlugin()
      ->loadByGroup($group, $langcode);
    return $this->renderSiteSettings($site_settings, $view_mode, $langcode, $check_access);
  }

  /**
   * Returns a render array of a site setting's entities by machine name.
   *
   * @param string $name
   *   The site setting machine name of the entity to load.
   * @param string $field_name
   *   The field name to get the single site setting from.
   * @param string $view_mode
   *   The view mode to render.
   * @param ?string $langcode
   *   The language code to render the view mode in.
   * @param bool $check_access
   *   Whether to check access.
   *
   * @return array
   *   The render array or an empty array.
   */
  public function singleSiteSettingsByNameAndField(
    string $name,
    string $field_name,
    string $view_mode = 'full',
    ?string $langcode = NULL,
    bool $check_access = TRUE,
  ): array {
    $storage = $this->entityTypeManager->getStorage('site_setting_entity');

    // Load by machine name.
    $site_settings = $storage->loadByProperties(['type' => $name]);
    if (!empty($site_settings) && reset($site_settings) instanceof SiteSettingEntity) {

      // Render the entities in the specified view mode and
      // language code.
      /** @var \Drupal\site_settings\SiteSettingEntityInterface $site_setting */
      $site_setting = reset($site_settings);
      if ($site_setting->hasField($field_name)) {
        if ($langcode && $site_setting->hasTranslation($langcode)) {
          $site_setting = $site_setting->getTranslation($langcode);
        }
        if ($check_access && !$site_setting->access('view', NULL, FALSE)) {
          return [];
        }
        $field = $site_setting->get($field_name);
        if ($field instanceof FieldItemListInterface) {
          $build = $this->entityTypeManager
            ->getViewBuilder('site_setting_entity')
            ->viewField($field, $view_mode);

          // Add the site setting to the cache tags.
          $cacheable_metadata = CacheableMetadata::createFromRenderArray($build);
          foreach ($site_settings as $site_setting) {
            $cacheable_metadata->addCacheableDependency($site_setting);
          }
          $cacheable_metadata->applyTo($build);
          return $build;
        }
      }
    }

    return [];
  }

  /**
   * Returns the site setting entity object by name.
   *
   * Note that if there are multiple only the first is returned. This extension
   * is therefore aimed at getting a single site setting object.
   *
   * @param string $selector
   *   The site settings type to load.
   * @param ?string $langcode
   *   The language code to render the view mode in.
   * @param bool $check_access
   *   Whether to check access.
   *
   * @return \Drupal\site_settings\SiteSettingEntityInterface
   *   The site settings entity object.
   */
  public function siteSettingEntityByName(
    string $selector,
    ?string $langcode = NULL,
    bool $check_access = TRUE,
  ): ?SiteSettingEntityInterface {
    $storage = $this->entityTypeManager->getStorage('site_setting_entity');
    $entities = $storage->loadByProperties(['type' => $selector]);
    $entity = reset($entities);
    if ($entity) {

      // Check access on the entity.
      if ($check_access) {
        $access = $entity->access('view', NULL, TRUE);
      }
      else {
        $access = AccessResult::allowed();
      }
      if ($access->isAllowed()) {

        // Return the entity in the specified language code if exists and
        // allowed.
        if (
          $langcode
          && $entity instanceof TranslatableInterface
          && $entity->language()->getId() !== $langcode
          && $entity->hasTranslation($langcode)
        ) {
          $translated_entity = $entity->getTranslation($langcode);
          if (
            $translated_entity
            && ($check_access || $translated_entity->access('view', NULL, FALSE))
          ) {
            return $translated_entity;
          }
        }

        // Return the original entity.
        return $entity;
      }
    }

    return NULL;
  }

  /**
   * Check access helper.
   *
   * @param bool $check_access
   *   Whether to check access.
   * @param array $site_settings
   *   The site settings to check access for.
   *
   * @return array
   *   The updated list of site settings.
   */
  protected function checkAccess(bool $check_access, array $site_settings): array {
    if ($check_access) {
      foreach ($site_settings as $key => $site_setting) {
        /** @var \Drupal\site_settings\SiteSettingEntityInterface $site_setting */
        if (!$site_setting->access('view', NULL, FALSE)) {
          var_dump('no access');
          unset($site_settings[$key]);
        }
      }
    }
    return $site_settings ?: [];
  }

  /**
   * Helper function to render an array of site settings.
   *
   * @param array $site_settings
   *   The site settings.
   * @param string $view_mode
   *   The view mode.
   * @param ?string $langcode
   *   The language code.
   * @param bool $check_access
   *   Whether to do an access check.
   * @param bool $multiple
   *   View multiple or single.
   *
   * @return array
   *   The build render array.
   */
  protected function renderSiteSettings(
    array $site_settings,
    string $view_mode,
    ?string $langcode,
    bool $check_access,
    bool $multiple = TRUE,
  ): array {
    if (!empty($site_settings) && reset($site_settings) instanceof EntityInterface) {
      $site_settings = $this->checkAccess($check_access, $site_settings);
      if (empty($site_settings)) {
        return [];
      }

      // Render the entities in the specified view mode and
      // language code.
      $view_builder = $this->entityTypeManager->getViewBuilder('site_setting_entity');
      if ($multiple) {

        // Render multiple.
        $build = $view_builder->viewMultiple($site_settings, $view_mode, $langcode);
      }
      else {

        // Ensure only the first site setting is used if single.
        $site_setting = reset($site_settings);
        $site_settings = [$site_setting];
        $build = $view_builder->view($site_setting, $view_mode, $langcode);
      }

      // Add only the site settings within the group to the cache tags.
      $cacheable_metadata = CacheableMetadata::createFromRenderArray($build);
      foreach ($site_settings as $site_setting) {
        $cacheable_metadata->addCacheableDependency($site_setting);
      }
      if ($check_access) {
        $cacheable_metadata->addCacheableDependency(AccessResult::allowed());
      }
      $cacheable_metadata->applyTo($build);
      return $build;
    }

    return [];
  }

}
