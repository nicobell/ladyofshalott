<?php

/**
 * @file
 * Hooks related to the Site Settings module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Override the string that tokens return when there is no setting defined.
 *
 * @param string $site_settings_no_setting_token
 *   The string to override.
 */
function hook_site_settings_no_setting_token_alter(&$site_settings_no_setting_token): void {
  // Remove default no setting token string.
  if ($site_settings_no_setting_token == 'Setting not found') {
    $site_settings_no_setting_token = NULL;
  }
}

/**
 * @} End of "addtogroup hooks".
 */
