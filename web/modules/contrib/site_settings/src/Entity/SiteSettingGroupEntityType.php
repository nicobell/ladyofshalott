<?php

namespace Drupal\site_settings\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\site_settings\SiteSettingGroupEntityTypeInterface;

/**
 * Defines the Site Settings Group Entity type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "site_setting_group_entity_type",
 *   label = @Translation("Site Setting Group Entity type"),
 *   label_collection = @Translation("Site Setting Group Entity types"),
 *   label_singular = @Translation("Site setting group entity type"),
 *   label_plural = @Translation("Site setting group entities types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count site setting group entities type",
 *     plural = "@count site setting group entities types",
 *   ),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\site_settings\Form\SiteSettingGroupEntityTypeForm",
 *       "edit" = "Drupal\site_settings\Form\SiteSettingGroupEntityTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\site_settings\SiteSettingGroupEntityTypeListBuilder",
 *     "access" = "Drupal\site_settings\SiteSettingGroupEntityTypeAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   admin_permission = "access site settings overview",
 *   config_prefix = "site_setting_group_entity_type",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/site_setting_group_entity_types/add",
 *     "edit-form" = "/admin/structure/site_setting_group_entity_types/manage/{site_setting_group_entity_type}",
 *     "delete-form" = "/admin/structure/site_setting_group_entity_types/manage/{site_setting_group_entity_type}/delete",
 *     "collection" = "/admin/structure/site_setting_group_entity_types",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "uuid",
 *   },
 * )
 */
class SiteSettingGroupEntityType extends ConfigEntityBase implements SiteSettingGroupEntityTypeInterface {

  /**
   * The machine name of this site settings group entity type.
   */
  protected string $id;

  /**
   * The human-readable name of the site settings group entity type.
   */
  protected string $label;

}
