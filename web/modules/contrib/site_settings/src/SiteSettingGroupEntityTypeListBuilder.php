<?php

namespace Drupal\site_settings;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of site settings group entities.
 *
 * @see \Drupal\site_settings\Entity\SiteSettingsGroupEntityType
 */
final class SiteSettingGroupEntityTypeListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['label'] = $this->t('Label');
    $header['twig_helper'] = $this->t('Twig function');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $row['label'] = $entity->label();
    $row['twig_helper'] = $this->t('<code>{{ site_settings_by_group(@label) }}</code>', [
      '@label' => "'" . $entity->label() . "'",
    ]);
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render(): array {
    $build = parent::render();

    $build['table']['#empty'] = $this->t(
      'No site settings group entity types available. <a href=":link">Add site settings group entity type</a>.',
      [':link' => Url::fromRoute('entity.site_setting_group_entity_type.add_form')->toString()],
    );

    return $build;
  }

}
