<?php

namespace Drupal\site_settings\Annotation;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Defines a Site Settings Loader annotation object.
 *
 * @Annotation
 */
class SiteSettingsLoader extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public string $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public Translation $label;

  /**
   * A boolean stating whether to autoload by default.
   *
   * When autoload by default is on, user's can still opt
   * to disable autoload.
   *
   * @var bool
   */
  public bool $autoload_by_default = FALSE;

}
