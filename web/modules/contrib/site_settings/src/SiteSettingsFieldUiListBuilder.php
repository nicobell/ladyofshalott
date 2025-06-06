<?php

namespace Drupal\site_settings;

use Drupal\Core\Entity\EntityInterface;
use Drupal\field_ui\FieldConfigListBuilder;

/**
 * Provides lists of field config entities.
 */
class SiteSettingsFieldUiListBuilder extends FieldConfigListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = parent::buildHeader();
    if ($this->targetEntityTypeId !== 'site_setting_entity') {
      return $header;
    }

    // Keep operations at the end.
    $operations = FALSE;
    if (isset($header['operations'])) {
      $operations = $header['operations'];
      unset($header['operations']);
    }

    $header['twig_helper'] = $this->t('Twig function');

    if ($operations) {
      $header['operations'] = $operations;
    }
    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $field_config) {
    $row = parent::buildRow($field_config);
    if ($this->targetEntityTypeId !== 'site_setting_entity') {
      return $row;
    }

    // Keep operations at the end.
    $operations = FALSE;
    if (isset($row['data']['operations'])) {
      $operations = $row['data']['operations'];
      unset($row['data']['operations']);
    }

    $row['data']['twig_helper'] = $this->t('<code>{{ site_setting_field(@type, @field) }}</code>', [
      '@type' => "'" . $field_config->toArray()['bundle'] . "'",
      '@field' => "'" . $field_config->getName() . "'",
    ]);

    if ($operations) {
      $row['data']['operations'] = $operations;
    }
    return $row;
  }

}
