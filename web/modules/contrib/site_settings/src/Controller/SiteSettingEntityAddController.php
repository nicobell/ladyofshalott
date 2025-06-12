<?php

namespace Drupal\site_settings\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;

/**
 * The site settings content creation controller.
 *
 * @package Drupal\site_settings\Controller
 */
class SiteSettingEntityAddController extends ControllerBase {

  /**
   * Displays add links for available bundles/types.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   *
   * @return array
   *   A render array for a list of the site_setting_entity bundles/types that
   *   can be added or if there is only one type/bundle defined for the site,
   *   the function returns the add page for that bundle/type.
   */
  public function add(Request $request):array {
    $types = $this->entityTypeManager()
      ->getStorage('site_setting_entity_type')
      ->loadMultiple();
    if ($types && count($types) == 1) {
      $type = reset($types);
      return $this->addForm($type, $request);
    }
    if (count($types) === 0) {
      return [
        '#markup' => $this->t('You have not created any %bundle types yet. @link to add a new type.', [
          '%bundle' => 'Site Setting',
          '@link' => Link::fromTextAndUrl($this->t('Go to the type creation page'), Url::fromRoute('entity.site_setting_entity_type.add_form'))->toString(),
        ]),
      ];
    }
    return [
      '#theme' => 'site_setting_entity_content_add_list',
      '#content' => $types,
    ];
  }

  /**
   * Presents the creation form for entities of given bundle/type.
   *
   * @param \Drupal\Core\Entity\EntityInterface $site_setting_entity_type
   *   The custom bundle to add.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   *
   * @return array
   *   A form array as expected by drupal_render().
   */
  public function addForm(EntityInterface $site_setting_entity_type, Request $request):array {
    $entity = $this->entityTypeManager()
      ->getStorage('site_setting_entity')
      ->create(['type' => $site_setting_entity_type->id()]);
    return $this->entityFormBuilder()->getForm($entity);
  }

  /**
   * Provides the page title for this controller.
   *
   * @param \Drupal\Core\Entity\EntityInterface $site_setting_entity_type
   *   The custom bundle/type being added.
   *
   * @return string
   *   The page title.
   */
  public function getAddFormTitle(EntityInterface $site_setting_entity_type):string {
    return $this->t('@label',
      ['@label' => $site_setting_entity_type->label()]
    );
  }

}
