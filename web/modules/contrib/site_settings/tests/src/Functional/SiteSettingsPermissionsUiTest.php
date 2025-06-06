<?php

namespace Drupal\Tests\site_settings\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\field_ui\Traits\FieldUiTestTrait;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Tests the Site Settings permissions.
 *
 * @group SiteSettings
 */
class SiteSettingsPermissionsUiTest extends BrowserTestBase {

  use FieldUiTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'site_settings',
    'site_settings_sample_data',
    'field_ui',
    'user',
    'system',
    'filter',
    'field',
  ];

  /**
   * Tests site settings all except administer permission.
   */
  public function testEditorPermissions() {

    // Create the user with specific permissions and login.
    $permissions = [
      'access content',
      'add site setting entities',
      'access site settings overview',
      'delete site setting entities',
      'edit site setting entities',
      'view published site setting entities',
      'view unpublished site setting entities',
      'view all site setting entity revisions',
      'revert all site setting entity revisions',
      'delete all site setting entity revisions',
    ];
    $user = $this->createUser($permissions);
    $this->drupalLogin($user);
    $session = $this->assertSession();

    // Test accessing the overview page.
    $this->drupalGet('admin/content/site-settings');
    $session->statusCodeEquals(200);

    // Test accessing the edit page of a site setting entity.
    $this->drupalGet('admin/structure/site_setting_entity/1/edit');
    $session->statusCodeEquals(200);

    // Test accessing the delete page of a site setting entity.
    $this->drupalGet('admin/structure/site_setting_entity/1/delete');
    $session->statusCodeEquals(200);

    // Test accessing the revisions page of a site setting entity.
    $this->drupalGet('admin/structure/site_setting_entity/1/revisions');
    $session->statusCodeEquals(200);

    // Create two revisions.
    $this->drupalGet('admin/structure/site_setting_entity/1/edit');
    $this->submitForm([
      'field_testing[0][value]' => 'Test revision',
      'revision' => TRUE,
    ], 'Save');
    $this->drupalGet('admin/structure/site_setting_entity/1/edit');
    $this->submitForm([
      'field_testing[0][value]' => 'Test revision 2',
      'revision' => TRUE,
    ], 'Save');

    // Test accessing the revert revision page of a site setting entity.
    $this->drupalGet('admin/structure/site_setting_entity/1/revisions');
    $this->drupalGet('admin/structure/site_setting_entity/1/revision/1/revert');
    $session->statusCodeEquals(200);

    // Test accessing the delete revision page of a site setting entity.
    $this->drupalGet('admin/structure/site_setting_entity/1/revision/13/delete');
    $session->statusCodeEquals(200);

    // Test creating a new site setting entity.
    $this->drupalGet('admin/structure/site_setting_entity/add');
    $session->statusCodeEquals(200);

    // Check the render output.
    $this->drupalGet('site_settings_sample_data/test_full_site_settings_loader_twig_extension');
    $session->elementTextContains(
      'css',
      '#render-5',
      'Test multiple entries and fields name 1'
    );

    // Test accessing the overview page without permissions.
    $this->drupalLogout();
    $this->drupalGet('admin/content/site-settings');
    $session->statusCodeEquals(403);

    // No view access permission by default for anonymous users.
    $this->drupalGet('site_settings_sample_data/test_full_site_settings_loader_twig_extension');
    $session->elementTextNotContains(
      'css',
      '#render-5',
      'Test multiple entries content 1'
    );
  }

  /**
   * Tests site settings no revision permission.
   */
  public function testEditorNoRevisionsPermissions() {

    // Create the user with specific permissions and login.
    $permissions = [
      'add site setting entities',
      'access site settings overview',
      'delete site setting entities',
      'edit site setting entities',
      'view published site setting entities',
    ];
    $user = $this->createUser($permissions);
    $this->drupalLogin($user);
    $session = $this->assertSession();

    // Test accessing the overview page.
    $this->drupalGet('admin/content/site-settings');
    $session->statusCodeEquals(200);

    // Test accessing the edit page of a site setting entity.
    $this->drupalGet('admin/structure/site_setting_entity/1/edit');
    $session->statusCodeEquals(200);

    // Test accessing the delete page of a site setting entity.
    $this->drupalGet('admin/structure/site_setting_entity/1/delete');
    $session->statusCodeEquals(200);

    // Test accessing the revisions page of a site setting entity.
    $this->drupalGet('admin/structure/site_setting_entity/1/revisions');
    $session->statusCodeEquals(403);

    // Create two revisions.
    $this->drupalGet('admin/structure/site_setting_entity/1/edit');
    $this->submitForm([
      'field_testing[0][value]' => 'Test revision',
      'revision' => TRUE,
    ], 'Save');
    $this->drupalGet('admin/structure/site_setting_entity/1/edit');
    $this->submitForm([
      'field_testing[0][value]' => 'Test revision 2',
      'revision' => TRUE,
    ], 'Save');

    // Test accessing the revert revision page of a site setting entity.
    $this->drupalGet('admin/structure/site_setting_entity/1/revisions');
    $this->drupalGet('admin/structure/site_setting_entity/1/revision/1/revert');
    $session->statusCodeEquals(403);

    // Test accessing the delete revision page of a site setting entity.
    $this->drupalGet('admin/structure/site_setting_entity/1/revision/13/delete');
    $session->statusCodeEquals(403);

    // Test creating a new site setting entity.
    $this->drupalGet('admin/structure/site_setting_entity/add');
    $session->statusCodeEquals(200);

    // Check the render output.
    $this->drupalGet('site_settings_sample_data/test_full_site_settings_loader_twig_extension');
    $session->elementTextContains(
      'css',
      '#render-5',
      'Test multiple entries and fields name 1'
    );

    // Test accessing the overview page without permissions.
    $this->drupalLogout();
    $this->drupalGet('admin/content/site-settings');
    $session->statusCodeEquals(403);
  }

  /**
   * Tests site settings without add or delete permission.
   */
  public function testEditorNoAddOrDeletePermissions() {

    // Create the user with specific permissions and login.
    $permissions = [
      'access site settings overview',
      'edit site setting entities',
      'view published site setting entities',
    ];
    $user = $this->createUser($permissions);
    $this->drupalLogin($user);
    $session = $this->assertSession();

    // Test accessing the overview page.
    $this->drupalGet('admin/content/site-settings');
    $session->statusCodeEquals(200);

    // Test accessing the edit page of a site setting entity.
    $this->drupalGet('admin/structure/site_setting_entity/1/edit');
    $session->statusCodeEquals(200);

    // Test accessing the delete page of a site setting entity.
    $this->drupalGet('admin/structure/site_setting_entity/1/delete');
    $session->statusCodeEquals(403);

    // Test creating a new site setting entity.
    $this->drupalGet('admin/structure/site_setting_entity/add');
    $session->statusCodeEquals(403);

    // Check the render output.
    $this->drupalGet('site_settings_sample_data/test_full_site_settings_loader_twig_extension');
    $session->elementTextContains(
      'css',
      '#render-5',
      'Test multiple entries and fields name 1'
    );

    // Test accessing the overview page without permissions.
    $this->drupalLogout();
    $this->drupalGet('admin/content/site-settings');
    $session->statusCodeEquals(403);
  }

  /**
   * Tests site settings without view permission.
   */
  public function testEditorNoViewPublished() {

    // Create the user with specific permissions and login.
    $permissions = [
      'access site settings overview',
    ];
    $user = $this->createUser($permissions);
    $this->drupalLogin($user);
    $session = $this->assertSession();

    // Test accessing the overview page.
    $this->drupalGet('admin/content/site-settings');
    $session->statusCodeEquals(200);

    // Check the render output. Since authenticated and anonymous users
    // should get the view permission by default, access should still be
    // granted.
    $this->drupalGet('site_settings_sample_data/test_full_site_settings_loader_twig_extension');
    $session->elementTextContains(
      'css',
      '#render-5',
      'Test multiple entries and fields name 1'
    );

    // Revoke the permissions.
    $authenticated = Role::load(RoleInterface::AUTHENTICATED_ID);
    $authenticated->revokePermission('view published site setting entities');
    $authenticated->save();
    $anonymous = Role::load(RoleInterface::ANONYMOUS_ID);
    $anonymous->revokePermission('view published site setting entities');
    $anonymous->save();

    // Expect it to no longer be visible.
    $this->drupalGet('site_settings_sample_data/test_full_site_settings_loader_twig_extension');
    $session->elementTextNotContains(
      'css',
      '#render-5',
      'Test multiple entries and fields name 1'
    );
  }

}
