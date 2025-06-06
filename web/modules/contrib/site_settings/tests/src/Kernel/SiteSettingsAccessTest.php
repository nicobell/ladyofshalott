<?php

namespace Drupal\Tests\site_settings\Kernel;

use Drupal\Core\Entity\RevisionableStorageInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\site_settings\Entity\SiteSettingEntity;
use Drupal\site_settings\Entity\SiteSettingGroupEntityType;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Tests the access control for SiteSettingEntity.
 *
 * @group site_settings
 */
class SiteSettingsAccessTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
    'system',
    'field',
    'site_settings',
  ];

  /**
   * Test the checkAccess method.
   */
  public function testCheckAccess() {
    // Install necessary schema.
    $this->installSchema('user', ['users_data']);
    $this->installEntitySchema('user');
    $this->installEntitySchema('user_role');
    $this->installEntitySchema('site_setting_entity');

    // Create user 1 as this user bypasses access checks which will throw
    // off any tests.
    $admin = User::create(['name' => 'admin']);
    $admin->save();

    // Create roles and users.
    $viewPublishedOnlyRole = Role::create([
      'id' => 'view_published',
      'label' => 'View Published',
    ]);
    $viewPublishedOnlyRole->grantPermission('view published site setting entities');
    $viewPublishedOnlyRole->save();
    $viewPublishedOnlyUser = User::create(['name' => 'view_published_user']);
    $viewPublishedOnlyUser->addRole($viewPublishedOnlyRole->id());
    $viewPublishedOnlyUser->save();

    $viewUnpublishedRole = Role::create([
      'id' => 'view_unpublished',
      'label' => 'View Unpublished',
    ]);
    $viewUnpublishedRole->grantPermission('view published site setting entities');
    $viewUnpublishedRole->grantPermission('view unpublished site setting entities');
    $viewUnpublishedRole->save();
    $viewUnpublishedUser = User::create(['name' => 'view_unpublished_user']);
    $viewUnpublishedUser->addRole($viewUnpublishedRole->id());
    $viewUnpublishedUser->save();

    $editRole = Role::create([
      'id' => 'edit',
      'label' => 'Edit',
    ]);
    $editRole->grantPermission('edit site setting entities');
    $editRole->save();
    $editUser = User::create(['name' => 'edit_user']);
    $editUser->addRole('edit');
    $editUser->save();

    $revisionRole = Role::create([
      'id' => 'revision_permissions',
      'label' => 'Revision Permissions',
    ]);
    $revisionRole->grantPermission('view published site setting entities');
    $revisionRole->grantPermission('view unpublished site setting entities');
    $revisionRole->grantPermission('view all site setting entity revisions');
    $revisionRole->grantPermission('revert all site setting entity revisions');
    $revisionRole->grantPermission('delete all site setting entity revisions');
    $revisionRole->save();
    $revisionUser = User::create(['name' => 'revision_user']);
    $revisionUser->addRole($revisionRole->id());
    $revisionUser->save();

    $administerRole = Role::create([
      'id' => 'administer_permissions',
      'label' => 'Administer Site Settings Permissions',
    ]);
    $administerRole->grantPermission('administer site setting entities');
    $administerRole->save();
    $administerUser = User::create(['name' => 'administer_user']);
    $administerUser->addRole($administerRole->id());
    $administerUser->save();

    $deleteRole = Role::create([
      'id' => 'delete',
      'label' => 'Delete',
    ]);
    $deleteRole->grantPermission('delete site setting entities');
    $deleteRole->save();
    $deleteUser = User::create(['name' => 'delete_user']);
    $deleteUser->addRole('delete');
    $deleteUser->save();

    SiteSettingGroupEntityType::create([
      'id' => 'other',
      'label' => 'Other',
    ]);

    // Create a published and an unpublished site setting entity.
    $publishedEntity = SiteSettingEntity::create([
      'type' => 'default',
      'status' => 1,
      'group' => 'other',
    ]);
    $publishedEntity->save();
    $unpublishedEntity = SiteSettingEntity::create([
      'type' => 'default',
      'status' => 0,
      'group' => 'other',
    ]);
    $unpublishedEntity->save();

    // Check view access for published entity.
    $this->assertTrue($publishedEntity->access('view', $viewPublishedOnlyUser, TRUE)->isAllowed());
    $this->assertTrue($publishedEntity->access('view', $viewUnpublishedUser, TRUE)->isAllowed());
    $this->assertTrue($publishedEntity->access('view', $administerUser, TRUE)->isAllowed());

    // Check view access for unpublished entity.
    $this->assertFalse($unpublishedEntity->access('view', $viewPublishedOnlyUser, TRUE)->isAllowed());
    $this->assertTrue($unpublishedEntity->access('view', $viewUnpublishedUser, TRUE)->isAllowed());
    $this->assertTrue($unpublishedEntity->access('view', $administerUser, TRUE)->isAllowed());

    // Check edit access.
    $this->assertTrue($publishedEntity->access('update', $editUser, TRUE)->isAllowed());
    $this->assertTrue($unpublishedEntity->access('update', $editUser, TRUE)->isAllowed());
    $this->assertTrue($publishedEntity->access('update', $administerUser, TRUE)->isAllowed());

    // Check delete access.
    $this->assertTrue($publishedEntity->access('delete', $deleteUser, TRUE)->isAllowed());
    $this->assertTrue($unpublishedEntity->access('delete', $deleteUser, TRUE)->isAllowed());
    $this->assertTrue($publishedEntity->access('delete', $administerUser, TRUE)->isAllowed());

    // Check access to view revisions.
    $this->assertFalse($publishedEntity->access('view all revisions', $viewPublishedOnlyUser, TRUE)->isAllowed());
    $this->assertFalse($unpublishedEntity->access('view all revisions', $viewUnpublishedUser, TRUE)->isAllowed());
    $this->assertTrue($unpublishedEntity->access('view all revisions', $revisionUser, TRUE)->isAllowed());
    $this->assertTrue($unpublishedEntity->access('view all revisions', $administerUser, TRUE)->isAllowed());

    // Check access to revert revisions.
    $oldRevisionId = $publishedEntity->getRevisionId();
    $publishedEntity->setNewRevision();
    $publishedEntity->save();
    $storage = \Drupal::entityTypeManager()->getStorage('site_setting_entity');
    $oldRevision = FALSE;
    if ($storage instanceof RevisionableStorageInterface) {
      $oldRevision = $storage->loadRevision($oldRevisionId);
    }

    $this->assertFalse($oldRevision->access('revert', $editUser, TRUE)->isAllowed());
    $this->assertTrue($oldRevision->access('revert', $administerUser, TRUE)->isAllowed());

    // Check access to delete revisions.
    $this->assertFalse($oldRevision->access('delete revision', $deleteUser, TRUE)->isAllowed());
    $this->assertTrue($oldRevision->access('delete revision', $administerUser, TRUE)->isAllowed());
  }

}
