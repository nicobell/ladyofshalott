<?php

namespace Drupal\site_settings;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Provides a listing of Site Setting type entities.
 */
class SiteSettingEntityTypeListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['label'] = $this->t('Site Setting type');
    $header['twig_helper'] = $this->t('Twig function');
    $header['id'] = $this->t('Machine name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $row['label'] = $entity->label();
    $row['twig_helper'] = $this->t('<code>{{ site_settings_by_name(@label) }}</code>', [
      '@label' => "'" . $entity->id() . "'",
    ]);
    $row['id'] = $entity->id();
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity): array {
    $operations = parent::getOperations($entity);
    $operations['replicate'] = [
      'title' => $this->t('Replicate'),
      'url' => Url::fromRoute("site_settings.site_setting_replicate_form", ['setting' => $entity->id()]),
      'weight' => 200,
    ];
    return $operations;
  }

}
