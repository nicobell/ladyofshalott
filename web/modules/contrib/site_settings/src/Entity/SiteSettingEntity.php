<?php

namespace Drupal\site_settings\Entity;

use Drupal\Core\Entity\EditorialContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\site_settings\SiteSettingEntityInterface;
use Drupal\site_settings\SiteSettingGroupEntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Site Setting entity.
 *
 * @ingroup site_settings
 *
 * @ContentEntityType(
 *   id = "site_setting_entity",
 *   label = @Translation("Site Setting"),
 *   bundle_label = @Translation("Site Setting type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\site_settings\SiteSettingEntityListBuilder",
 *     "views_data" = "Drupal\site_settings\Entity\SiteSettingEntityViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\site_settings\Form\SiteSettingEntityForm",
 *       "add" = "Drupal\site_settings\Form\SiteSettingEntityForm",
 *       "edit" = "Drupal\site_settings\Form\SiteSettingEntityForm",
 *       "delete" = "Drupal\site_settings\Form\SiteSettingEntityDeleteForm",
 *       "revision-delete" = \Drupal\Core\Entity\Form\RevisionDeleteForm::class,
 *       "revision-revert" = \Drupal\Core\Entity\Form\RevisionRevertForm::class,
 *     },
 *     "access" = "Drupal\site_settings\SiteSettingEntityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\site_settings\SiteSettingEntityHtmlRouteProvider",
 *       "revision" = \Drupal\Core\Entity\Routing\RevisionHtmlRouteProvider::class,
 *     },
 *   },
 *   base_table = "site_setting_entity",
 *   revision_table = "site_setting_entity_revision",
 *   data_table = "site_setting_entity_field_data",
 *   revision_data_table = "site_setting_entity_field_data_revision",
 *   show_revision_ui = TRUE,
 *   admin_permission = "administer site setting entities",
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "revision_id",
 *     "bundle" = "type",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "group" = "group",
 *     "langcode" = "langcode",
 *     "published" = "status",
 *   },
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_user",
 *     "revision_created" = "revision_created",
 *     "revision_log_message" = "revision_log"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/site_setting_entity/{site_setting_entity}",
 *     "add-form" = "/admin/structure/site_setting_entity/add/{site_setting_entity_type}",
 *     "edit-form" = "/admin/structure/site_setting_entity/{site_setting_entity}/edit",
 *     "delete-form" = "/admin/structure/site_setting_entity/{site_setting_entity}/delete",
 *     "collection" = "/admin/content/site-settings",
 *     "revision" = "/admin/structure/site_setting_entity/{site_setting_entity}/revision/{site_setting_entity_revision}/view",
 *     "revision-delete-form" = "/admin/structure/site_setting_entity/{site_setting_entity}/revision/{site_setting_entity_revision}/delete",
 *     "revision-revert-form" = "/admin/structure/site_setting_entity/{site_setting_entity}/revision/{site_setting_entity_revision}/revert",
 *     "version-history" = "/admin/structure/site_setting_entity/{site_setting_entity}/revisions",
 *   },
 *   bundle_entity_type = "site_setting_entity_type",
 *   field_ui_base_route = "entity.site_setting_entity_type.edit_form"
 * )
 */
class SiteSettingEntity extends EditorialContentEntityBase implements SiteSettingEntityInterface {

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values): void {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getType(): string {
    return $this->bundle();
  }

  /**
   * {@inheritdoc}
   */
  public function setType(string $type): SiteSettingEntityInterface {
    $this->set('type', $type);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getName(): string {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name): SiteSettingEntityInterface {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroup(): string {
    if ($this->get('group')->entity instanceof SiteSettingGroupEntityTypeInterface) {
      return $this->get('group')->entity->id();
    }
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function setGroup($group): SiteSettingEntityInterface {
    $this->set('group', $group);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime(): int {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime(int $timestamp): SiteSettingEntityInterface {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner(): UserInterface {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId(): int {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid): SiteSettingEntityInterface {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account): SiteSettingEntityInterface {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): string {
    return $this->get('description')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description): SiteSettingEntityInterface {
    $this->set('description', $description);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    /** @var \Drupal\Core\Field\BaseFieldDefinition[] $fields */
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Site Setting entity.'))
      ->setReadOnly(TRUE);

    $fields['type'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Type'))
      ->setDescription(t('The Site Setting type/bundle.'))
      ->setSetting('target_type', 'site_setting_entity_type')
      ->setRequired(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Site Setting entity.'))
      ->setReadOnly(TRUE);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Site Setting entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setTranslatable(TRUE)
      ->setDefaultValueCallback(static::class . '::getDefaultUserId')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Site Setting entity.'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setRevisionable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['group'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Group'))
      ->setDescription(t('The Site Setting Group type.'))
      ->setSetting('target_type', 'site_setting_group_entity_type')
      ->setRevisionable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Override parent field definition for status field.
    $fields['status']->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Site Setting is published.'));

    // Override parent field definition for langcode field.
    $fields['langcode']->setLabel(t('Language code'))
      ->setDescription(t('The language code for the Site Setting entity.'))
      ->setDisplayOptions('form', [
        'type' => 'language_select',
        'weight' => 10,
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['description'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Description'))
      ->setDescription(t('Administrative description of the Site Setting(s). This description is not shown to the visitor.'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setRevisionable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

  /**
   * Default value callback for 'user_id' base field definition.
   *
   * @return array
   *   An array of default values.
   */
  public static function getDefaultUserId(): array {
    return [\Drupal::currentUser()->id()];
  }

}
