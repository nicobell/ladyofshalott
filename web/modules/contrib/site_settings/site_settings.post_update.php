<?php

/**
 * @file
 * Post update functions for Site Settings.
 */

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\site_settings\Entity\SiteSettingEntity;
use Drupal\user\Entity\Role;

/**
 * Implements hook_post_update_NAME().
 *
 * Add the 'access site settings overview' permission for roles that have the
 * 'edit site setting entities' permission.
 */
function site_settings_post_update_update_permissions() {
  foreach (Role::loadMultiple() as $role) {
    if ($role->hasPermission('edit site setting entities')) {
      $role->grantPermission('access site settings overview')->save();
    }
  }
}

/**
 * Makes site_settings entities revisionable.
 */
function site_settings_post_update_1_make_revisionable(&$sandbox) {
  $definition_update_manager = \Drupal::entityDefinitionUpdateManager();
  /** @var \Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface $last_installed_schema_repository */
  $last_installed_schema_repository = \Drupal::service('entity.last_installed_schema.repository');

  $entity_type = $definition_update_manager->getEntityType('site_setting_entity');
  $field_storage_definitions = $last_installed_schema_repository->getLastInstalledFieldStorageDefinitions('site_setting_entity');

  // Update the entity type definition.
  $entity_keys = $entity_type->getKeys();
  $entity_keys['revision'] = 'revision_id';
  $entity_keys['revision_translation_affected'] = 'revision_translation_affected';
  $entity_type->set('entity_keys', $entity_keys);
  $entity_type->set('revision_table', 'site_setting_entity_revision');
  $entity_type->set('revision_data_table', 'site_setting_entity_field_data_revision');
  $revision_metadata_keys = [
    'revision_default' => 'revision_default',
    'revision_user' => 'revision_user',
    'revision_created' => 'revision_created',
    'revision_log_message' => 'revision_log',
  ];
  $entity_type->set('revision_metadata_keys', $revision_metadata_keys);

  // Update the field storage definitions and add the new ones required by a
  // revisionable entity type.
  if ($field_storage_definitions['langcode']) {
    $field_storage_definitions['langcode']->setRevisionable(TRUE);
  }
  if ($field_storage_definitions['user_id']) {
    $field_storage_definitions['user_id']->setRevisionable(TRUE);
  }
  if ($field_storage_definitions['name']) {
    $field_storage_definitions['name']->setRevisionable(TRUE);
  }
  if (isset($field_storage_definitions['fieldset']) && $field_storage_definitions['fieldset']) {
    $field_storage_definitions['fieldset']->setRevisionable(TRUE);
  }
  if (isset($field_storage_definitions['status']) && $field_storage_definitions['status']) {
    $field_storage_definitions['status']->setRevisionable(TRUE);
  }
  if (isset($field_storage_definitions['group']) && $field_storage_definitions['group']) {
    $field_storage_definitions['group']->setRevisionable(TRUE);
  }
  if (isset($field_storage_definitions['description']) && $field_storage_definitions['description']) {
    $field_storage_definitions['description']->setRevisionable(TRUE);
  }

  $field_storage_definitions['revision_id'] = BaseFieldDefinition::create('integer')
    ->setName('revision_id')
    ->setTargetEntityTypeId('site_setting_entity')
    ->setTargetBundle(NULL)
    ->setLabel(new TranslatableMarkup('Revision ID'))
    ->setReadOnly(TRUE)
    ->setSetting('unsigned', TRUE);

  $field_storage_definitions['revision_default'] = BaseFieldDefinition::create('boolean')
    ->setName('revision_default')
    ->setTargetEntityTypeId('site_setting_entity')
    ->setTargetBundle(NULL)
    ->setLabel(new TranslatableMarkup('Default revision'))
    ->setDescription(new TranslatableMarkup('A flag indicating whether this was a default revision when it was saved.'))
    ->setStorageRequired(TRUE)
    ->setInternal(TRUE)
    ->setTranslatable(FALSE)
    ->setRevisionable(TRUE);

  $field_storage_definitions['revision_translation_affected'] = BaseFieldDefinition::create('boolean')
    ->setName('revision_translation_affected')
    ->setTargetEntityTypeId('site_setting_entity')
    ->setTargetBundle(NULL)
    ->setLabel(new TranslatableMarkup('Revision translation affected'))
    ->setDescription(new TranslatableMarkup('Indicates if the last edit of a translation belongs to current revision.'))
    ->setReadOnly(TRUE)
    ->setRevisionable(TRUE)
    ->setTranslatable(TRUE);

  $field_storage_definitions['revision_created'] = BaseFieldDefinition::create('created')
    ->setName('revision_created')
    ->setTargetEntityTypeId('site_setting_entity')
    ->setTargetBundle(NULL)
    ->setLabel(new TranslatableMarkup('Revision create time'))
    ->setDescription(new TranslatableMarkup('The time that the current revision was created.'))
    ->setRevisionable(TRUE);

  $field_storage_definitions['revision_user'] = BaseFieldDefinition::create('entity_reference')
    ->setName('revision_user')
    ->setTargetEntityTypeId('site_setting_entity')
    ->setTargetBundle(NULL)
    ->setLabel(new TranslatableMarkup('Revision user'))
    ->setDescription(new TranslatableMarkup('The user ID of the author of the current revision.'))
    ->setSetting('target_type', 'user')
    ->setRevisionable(TRUE);

  $field_storage_definitions['revision_log'] = BaseFieldDefinition::create('string_long')
    ->setName('revision_log')
    ->setTargetEntityTypeId('site_setting_entity')
    ->setTargetBundle(NULL)
    ->setLabel(new TranslatableMarkup('Revision log message'))
    ->setDescription(new TranslatableMarkup('Briefly describe the changes you have made.'))
    ->setRevisionable(TRUE)
    ->setDefaultValue('')
    ->setDisplayOptions('form', [
      'type' => 'string_textarea',
      'weight' => 25,
      'settings' => [
        'rows' => 4,
      ],
    ]);
  $definition_update_manager->updateFieldableEntityType($entity_type, $field_storage_definitions, $sandbox);
  return t('Site setting entities have been converted to be revisionable.');
}

/**
 * Add default revision values.
 */
function site_settings_post_update_2_make_revisionable(&$sandbox): void {
  $storage = \Drupal::entityTypeManager()->getStorage('site_setting_entity');
  if (!isset($sandbox['max'])) {
    $sandbox['progress'] = 0;
    $sandbox['current_id'] = 0;
    $sandbox['max'] = $storage->getQuery()->accessCheck(FALSE)->count()->execute();
  }
  $limit = 50;
  $site_setting_ids = $storage->getQuery()
    ->condition('id', $sandbox['current_id'], '>')
    ->sort('id')
    ->range(0, $limit)
    ->accessCheck(FALSE)
    ->execute();
  $site_settings = $storage->loadMultiple($site_setting_ids);
  foreach ($site_settings as $site_setting) {
    if ($site_setting instanceof SiteSettingEntity) {
      $site_setting->setRevisionUser($site_setting->getOwner());
      $site_setting->setRevisionCreationTime($site_setting->getCreatedTime());
      $site_setting->save();
    }

    $sandbox['progress']++;
    $sandbox['current_id'] = $site_setting->id();
  }
  if ($sandbox['progress'] < $sandbox['max']) {
    $sandbox['#finished'] = $sandbox['progress'] / $sandbox['max'];
  }
  else {
    $sandbox['#finished'] = 1;
  }
}

/**
 * Re-run set the default site settings loader to flattened if upgrading.
 */
function site_settings_post_update_check_loader() {
  // Re-run the site settings loader setting. This only happens
  // if it has not yet been set. In 99% of cases this does nothing.
  // @see
  \Drupal::moduleHandler()->loadInclude('site_settings', 'install');
  site_settings_update_10004();
}

/**
 * Add missing status index if not existing.
 */
function site_settings_post_update_add_status_type_index(&$sandbox) {
  $schema = \Drupal::database()->schema();
  $index_exists = $schema->indexExists('site_setting_entity_field_data', 'site_setting_entity__status_type');
  if (!$index_exists) {
    $index = [
      'status',
      'type',
      'id',
    ];
    $spec = [
      'fields' => [
        'status' => ['type' => 'int'],
        'type' => ['type' => 'varchar', 'length' => 191],
        'id' => ['type' => 'int'],
      ],
    ];
    $schema->addIndex('site_setting_entity_field_data', 'site_setting_entity__status_type', $index, $spec);

    // Update the schema entry for the index to ensure it is there as well.
    $key_value = \Drupal::keyValue('entity.storage_schema.sql');
    $key_name = 'site_setting_entity.entity_schema_data';
    $storage_schema = $key_value->get($key_name);
    if (isset($storage_schema['site_setting_entity_field_data'])) {
      $storage_schema['site_setting_entity_field_data']['indexes']['site_setting_entity__status_type'] = [
        'status',
        'type',
        'id',
      ];
      $key_value->set($key_name, $storage_schema);
    }
  }
}

/**
 * Fix mismatched entity and/or field definitions.
 *
 * This new post_update hook fixes situation created by editing
 * already existing post_update hook.
 *
 * Root cause: some users applied the original version
 * of the site_settings_post_update_1_make_revisionable(), but then it was
 * updated in https://www.drupal.org/i/3463953, however the same post_update
 * hook will never be called multiple times causing mismatched entity and/or
 * field definitions issue.
 *
 * @see https://www.drupal.org/i/3463953
 */
function site_settings_post_update_add_missing_index_and_revisioning(&$sandbox) {
  /** @var \Drupal\Core\Entity\EntityLastInstalledSchemaRepository $last_installed_schema_repository */
  $last_installed_schema_repository = \Drupal::service('entity.last_installed_schema.repository');

  /** @var \Drupal\Core\Field\FieldStorageDefinitionInterface[] $field_storage_definitions */
  $field_storage_definitions = $last_installed_schema_repository->getLastInstalledFieldStorageDefinitions('site_setting_entity');

  // Assume that everything is OK.
  $needs_update = FALSE;

  // Status field might not be revisionable, as the original post_update
  // hook did not contain it.
  if (!$field_storage_definitions['status']->isRevisionable()) {
    // Make the status field revisionable.
    $field_storage_definitions['status']->setRevisionable(TRUE);
    $needs_update = TRUE;
  }

  // Field revision_log_message was added first, but then renamed
  // to revision_log.
  if (array_key_exists('revision_log_message', $field_storage_definitions)) {
    // Remove revision_log_message field.
    unset($field_storage_definitions['revision_log_message']);
    $needs_update = TRUE;
  }

  if (!array_key_exists('revision_log', $field_storage_definitions)) {
    // Create the revision_log field.
    $field_storage_definitions['revision_log'] = BaseFieldDefinition::create('string_long')
      ->setName('revision_log')
      ->setTargetEntityTypeId('site_setting_entity')
      ->setTargetBundle(NULL)
      ->setLabel(new TranslatableMarkup('Revision log message'))
      ->setDescription(new TranslatableMarkup('Briefly describe the changes you have made.'))
      ->setRevisionable(TRUE)
      ->setDefaultValue('')
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 25,
        'settings' => [
          'rows' => 4,
        ],
      ]);
    $needs_update = TRUE;
  }

  if ($needs_update) {
    $definition_update_manager = \Drupal::entityDefinitionUpdateManager();

    // The entity type definition.
    $entity_type = $definition_update_manager->getEntityType('site_setting_entity');

    // Update the entity type definitions.
    $definition_update_manager->updateFieldableEntityType($entity_type, $field_storage_definitions, $sandbox);
  }

  // Re-run the previous update, which will add the DB index correctly, as
  // status field is revisionable now.
  site_settings_post_update_add_status_type_index($sandbox);
}
