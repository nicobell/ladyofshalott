<?php

namespace Drupal\site_settings;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the Site Setting Group Entity Type.
 *
 * @see \Drupal\site_settings\Entity\SiteSettingGroupEntityType.
 */
class SiteSettingGroupEntityTypeAccessControlHandler extends SiteSettingEntityAccessControlHandler implements EntityHandlerInterface {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResultInterface {
    if ($operation === 'view') {
      return AccessResult::allowedIfHasPermission($account, 'access site settings overview');
    }

    return AccessResult::allowedIfHasPermission($account, 'administer site setting entities');
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResultInterface {
    return AccessResult::allowedIfHasPermission($account, 'administer site setting entities');
  }

}
