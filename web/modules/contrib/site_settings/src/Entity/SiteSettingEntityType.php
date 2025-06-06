<?php

namespace Drupal\site_settings\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\site_settings\SiteSettingEntityTypeInterface;

/**
 * Defines the Site Setting type entity.
 *
 * @ConfigEntityType(
 *   id = "site_setting_entity_type",
 *   label = @Translation("Site Setting type"),
 *   handlers = {
 *     "list_builder" = "Drupal\site_settings\SiteSettingEntityTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\site_settings\Form\SiteSettingEntityTypeForm",
 *       "edit" = "Drupal\site_settings\Form\SiteSettingEntityTypeForm",
 *       "delete" = "Drupal\site_settings\Form\SiteSettingEntityTypeDeleteForm"
 *     },
 *     "access" = "Drupal\site_settings\SiteSettingEntityTypeAccessControlHandler",
 *   },
 *   config_prefix = "site_setting_entity_type",
 *   config_export = {
 *     "id",
 *     "label",
 *     "group",
 *     "multiple",
 *   },
 *   admin_permission = "administer site configuration",
 *   bundle_of = "site_setting_entity",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "group" = "group",
 *     "multiple" = "multiple",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/site_setting_entity_type/add",
 *     "edit-form" = "/admin/structure/site_setting_entity_type/{site_setting_entity_type}",
 *     "delete-form" = "/admin/structure/site_setting_entity_type/{site_setting_entity_type}/delete",
 *     "collection" = "/admin/structure/site_setting_entity_type"
 *   }
 * )
 */
class SiteSettingEntityType extends ConfigEntityBundleBase implements SiteSettingEntityTypeInterface {

  /**
   * The Site Setting type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Site Setting type label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Site Setting type group.
   *
   * @var string
   */
  public $group;

  /**
   * The Site Setting type multiple.
   *
   * @var bool
   */
  public $multiple;

  /**
   * {@inheritDoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();
    if (!empty($this->group)) {
      $this->addDependency('config', 'site_settings.site_setting_group_entity_type.' . $this->group);
    }
    return $this;
  }

}
