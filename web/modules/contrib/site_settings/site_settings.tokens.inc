<?php

/**
 * @file
 * Builds placeholder replacement tokens for node-related data.
 */

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function site_settings_token_info(): array {

  $types = [];
  $types['site_settings'] = [
    'name' => t("Site settings and labels"),
    'description' => t("Tokens for Site Settings and Labels."),
  ];
  // Use the multiple 'site_settings_entity' otherwise tokens are auto-generated
  // based on the entity.
  $types['site_settings_entity'] = [
    'name' => t("Site settings and labels (entities)"),
    'description' => t("Entity tokens for Site Settings and Labels."),
  ];

  $site_settings = [];

  // Dynamically generate the tokens for all available settings.
  /** @var \Drupal\site_settings\SiteSettingsLoaderPluginManager $plugin_manager */
  $plugin_manager = \Drupal::service('plugin.manager.site_settings_loader');
  $site_settings_loader = $plugin_manager->getActiveLoaderPlugin();
  $settings = $site_settings_loader->loadAll();
  if (empty($settings)) {
    return [];
  }

  if ($settings) {
    foreach ($settings as $group => $fieldtypes) {
      foreach ($fieldtypes as $fieldtype => $values) {
        $site_settings = _site_settings_build_token($site_settings, $group, $fieldtype, $values);
      }
    }
  }

  // Provide the full entities as tokens.
  $entity_tokens = [];
  $storage = \Drupal::entityTypeManager()->getStorage('site_setting_entity');
  $type_storage = \Drupal::entityTypeManager()->getStorage('site_setting_entity_type');
  $entity_types = $type_storage->getQuery()->accessCheck()->execute();

  foreach ($entity_types as $entity_type) {
    $ids = $storage->getQuery()
      ->condition('type', $entity_type)
      ->sort('id')
      ->accessCheck()
      ->execute();
    $entities = $storage->loadMultiple($ids);

    if (count($entities) === 1) {
      $entity = reset($entities);
      $entity_tokens[$entity->bundle()] = [
        'name' => $entity->label(),
        'description' => t('Field values from the site settings entity.'),
        'type' => 'site_setting_entity',
      ];
    }
    else {
      foreach (array_values($entities) as $i => $entity) {
        $entity_tokens[sprintf('%s-%d', $entity->bundle(), $i)] = [
          'name' => sprintf('%s (%d)', $entity->label(), $i),
          'description' => t('Field values from the site settings entity.'),
          'type' => 'site_setting_entity',
        ];
      }
    }
  }
  return [
    'types' => $types,
    'tokens' => [
      'site_settings' => $site_settings,
      'site_settings_entity' => $entity_tokens,
    ],
  ];
}

/**
 * Build the tokens from the site settings.
 *
 * @param array $site_settings
 *   The site settings.
 * @param string $group
 *   The group.
 * @param string $fieldtype
 *   The field type.
 * @param mixed $values
 *   A single string value or array of values.
 * @param bool|string $suffix
 *   A suffix to add to the token to handle array values.
 *
 * @return array
 *   The array of tokens.
 */
function _site_settings_build_token(array $site_settings, string $group, string $fieldtype, mixed $values, bool|string $suffix = FALSE): array {
  if (is_array($values)) {

    // Recursively get all values.
    foreach ($values as $key => $values) {
      $this_suffix = ($suffix !== FALSE ? $suffix . '-' : '') . $key;
      $site_settings = _site_settings_build_token($site_settings, $group, $fieldtype, $values, $this_suffix);
    }

  }
  else {

    // Set description based on whether flat or array of data.
    if ($suffix !== FALSE) {
      $description = t('One of the values of "@type" within "@group"', [
        '@type' => $fieldtype,
        '@group' => $group,
      ]);
    }
    else {
      $description = t('The value of "@type" within "@group"', [
        '@type' => $fieldtype,
        '@group' => $group,
      ]);
    }

    // Store each value or subvalue as a token option.
    $key = $group . '--' . $fieldtype . ($suffix !== FALSE ? '--' . $suffix : '');
    $label = _site_settings_token_label($key);
    $site_settings[$key] = [
      'name' => t("@label", ['@label' => $label]),
      'description' => $description,
    ];
  }
  return $site_settings;
}

/**
 * Implements hook_tokens().
 */
function site_settings_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata): array {
  $replacements = [];

  if ($type == 'site_settings_entity') {
    $storage = \Drupal::entityTypeManager()->getStorage('site_setting_entity');

    foreach ($tokens as $name => $original) {
      $parts = explode(':', $name);
      $site_setting_name = array_shift($parts);
      $has_index = preg_match('#^(?<site_setting_name>.+)-(?<index>\d+)$#', $site_setting_name, $matches);
      $index = FALSE;
      if ($has_index) {
        $index = (int) $matches['index'];
        $site_setting_name = $matches['site_setting_name'];
      }

      $ids = $storage->getQuery()
        ->condition('type', $site_setting_name)
        ->sort('id')
        ->accessCheck()
        ->execute();
      $site_setting_entities = $storage->loadMultiple($ids);
      if ($has_index && $index !== FALSE) {
        $site_setting_entity = array_values($site_setting_entities)[$index];
      }
      else {
        $site_setting_entity = reset($site_setting_entities);
      }

      // Remove the index from the token.
      unset($tokens[$name]);
      $key = implode(':', array_merge([$site_setting_name], $parts));
      $tokens[$key] = $original;
      if ($entity_tokens = \Drupal::token()->findWithPrefix($tokens, $site_setting_name)) {
        $replacements += \Drupal::token()->generate(
          'site_setting_entity',
          $entity_tokens,
          ['site_setting_entity' => $site_setting_entity],
          $options,
          $bubbleable_metadata
        );
      }
    }
  }

  if ($type == 'site_settings') {

    // Allow developers to pass the settings to token replace so they can
    // modify them first potentially. If not passed, just make the defaults
    // available.
    if (!empty($data['site_settings'])) {
      $settings = $data['site_settings'];
    }
    else {
      /** @var \Drupal\site_settings\SiteSettingsLoaderPluginManager $plugin_manager */
      $plugin_manager = \Drupal::service('plugin.manager.site_settings_loader');
      $site_settings = $plugin_manager->getActiveLoaderPlugin();
      $settings = $site_settings->loadAll();
    }

    foreach ($tokens as $name => $original) {
      $value = FALSE;

      // Break apart name.
      $name_parts = explode('--', $name);
      $group = $name_parts[0];
      $type = $name_parts[1] ?? NULL;

      // We may be deep into a multidimensional array.
      if (isset($name_parts[2]) && is_string($name_parts[2]) && strlen($name_parts[2])) {

        // Break apart the on our separator to get the series of array keys we
        // need to navigate down.
        $additional_depth = $name_parts[2];
        $keys = explode('-', $additional_depth);
        if (isset($settings[$group][$type])) {
          $setting = $settings[$group][$type];
          $value = _site_settings_retrieve_deep_value($setting, $keys);
        }
      }
      elseif (isset($settings[$group][$type])) {

        // Flat setting value.
        $value = $settings[$group][$type];
      }
      $replacements[$original] = $value;
    }
  }
  return $replacements;
}

/**
 * Retrieve a deep value from our multidimensional array.
 *
 * @param array|string $settings
 *   The settings data to be accessed.
 * @param array $keys
 *   A series of keys, the first one for the top level, the second one for the
 *   second level, etc.
 *
 * @return string
 *   The final value.
 */
function _site_settings_retrieve_deep_value(array|string $settings, array $keys): string {
  if ($keys) {
    $next_key = array_shift($keys);
    if (isset($settings[$next_key]) && is_array($keys)) {
      return _site_settings_retrieve_deep_value($settings[$next_key], $keys);
    }
    else {
      $site_settings_no_setting_token = t('Setting not found');
      // Allow modules to alter this string or remove it if they don't need it.
      \Drupal::moduleHandler()->alter('site_settings_no_setting_token', $site_settings_no_setting_token);
      return $site_settings_no_setting_token;
    }
  }
  return $settings;
}

/**
 * Convert the token key into a more readable label.
 *
 * @param string $key
 *   The token key.
 *
 * @return string
 *   A more readable label.
 */
function _site_settings_token_label(string $key): string {
  $label = str_replace(['--', '-', '_'], ' ', $key);
  $label = rtrim($label);
  $label = ucwords($label);
  return $label;
}
