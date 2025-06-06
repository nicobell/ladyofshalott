<?php

/**
 * @file
 * Provide views data.
 */

/**
 * Implements hook_views_data().
 */
function site_settings_views_data() {
  $data['views']['not_yet_created_site_setting_entities'] = [
    'title' => t('Not yet created site settings'),
    'help' => t('Shows a list of site settings entities that have not yet been created along with create buttons.'),
    'area' => [
      'id' => 'not_yet_created_site_settings',
    ],
  ];
  return $data;
}
