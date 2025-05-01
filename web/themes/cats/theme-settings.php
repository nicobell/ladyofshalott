<?php

declare(strict_types=1);

/**
 * @file
 * Theme settings form for Cats theme.
 */

use Drupal\Core\Form\FormState;

/**
 * Implements hook_form_system_theme_settings_alter().
 */
function cats_form_system_theme_settings_alter(array &$form, FormState $form_state): void {

  $form['cats'] = [
    '#type' => 'details',
    '#title' => t('Cats'),
    '#open' => TRUE,
  ];

  $form['cats']['example'] = [
    '#type' => 'textfield',
    '#title' => t('Example'),
    '#default_value' => theme_get_setting('example'),
  ];

}
