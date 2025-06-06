<?php

namespace Drupal\site_settings;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Url;
use Drupal\views\Views;

/**
 * List site settings entities using views.
 *
 * @see https://www.drupal.org/project/entity/issues/2959628
 */
class SiteSettingEntityListBuilder extends EntityListBuilder {

  /**
   * The View object used to render the list.
   *
   * @var \Drupal\views\ViewExecutable
   */
  protected $view;

  /**
   * {@inheritdoc}
   */
  public function load() {
    $this->getView()->execute();
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return $this->getView()->render();
  }

  /**
   * Returns an executable view to list entities.
   *
   * @return \Drupal\views\ViewExecutable
   *   A view to render a list of entities.
   */
  public function getView() {
    if (empty($this->view)) {
      $this->view = Views::getView('site_settings');
    }
    return $this->view;
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity): array {
    $operations = parent::getOperations($entity);
    $operations['revisions'] = [
      'title' => $this->t('Revisions'),
      'url' => Url::fromRoute("entity.site_setting_entity.version_history", ['site_setting_entity' => $entity->id()]),
    ];
    return $operations;
  }

}
