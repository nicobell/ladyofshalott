<?php

namespace Drupal\Tests\site_settings_type_permissions\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\field_ui\Traits\FieldUiTestTrait;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Tests the Site Settings type permissions.
 *
 * @group SiteSettings
 */
class SiteSettingTypePermissionsUiTest extends BrowserTestBase {

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
    'site_settings_type_permissions',
    'field_ui',
    'user',
  ];

  /**
   * Tests site settings type permissions for editor users.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testSiteSettingsTypePermissions() {
    // Revoke the permissions to view from anonymous and
    // authenticated.
    $authenticated = Role::load(RoleInterface::AUTHENTICATED_ID);
    $authenticated->revokePermission('view published site setting entities');
    $authenticated->save();
    $anonymous = Role::load(RoleInterface::ANONYMOUS_ID);
    $anonymous->revokePermission('view published site setting entities');
    $anonymous->save();

    // Create an editor user for test.
    $editor = $this->createUser([
      'administer site configuration',
      'access site settings overview',
      'view published test_multiple_entries_and_fields site setting entities',
      'view unpublished test_multiple_entries_and_fields site setting entities',
      'create test_multiple_entries_and_fields site setting',
      'edit test_multiple_entries_and_fields site setting',
      'delete test_multiple_entries_and_fields site setting',
      'view published test_plain_text site setting entities',
      'view unpublished test_plain_text site setting entities',
      'create test_plain_text site setting',
      'edit test_plain_text site setting',
      'delete test_plain_text site setting',
    ]);

    $this->drupalLogin($editor);

    // Open the site settings list page.
    $this->drupalGet('admin/content/site-settings');

    // Make sure the groups match.
    $this->assertSession()->responseContains('Other');
    $this->assertSession()->responseNotContains('Images');

    // Make sure the test plain text is as expected.
    $this->assertSession()->pageTextContains('Test plain text');

    // Make sure the test multiple entries and fields contents are as expected.
    $this->assertSession()->pageTextContains('Test multiple entries and fields name 1');
    $this->assertSession()->pageTextContains('Test multiple entries and fields name 2');

    $this->drupalLogout();

    // Create an edit only user for test.
    $edit_only = $this->drupalCreateUser([
      'administer site configuration',
      'access site settings overview',
      'edit test_multiple_entries_and_fields site setting',
      'edit test_plain_text site setting',
      'view published test_multiple_entries_and_fields site setting entities',
      'view published test_plain_text site setting entities',
    ]);

    $this->drupalLogin($edit_only);

    // Open the site settings list page.
    $this->drupalGet('admin/content/site-settings');

    // Make sure the groups match.
    $this->assertSession()->responseContains('Other');
    $this->assertSession()->responseNotContains('Images');

    // Make sure the test plain text is as expected.
    $this->assertSession()->pageTextContains('Test plain text');

    // Make sure the test multiple entries and fields contents are as expected.
    $this->assertSession()->pageTextContains('Test multiple entries and fields name 1');
    $this->assertSession()->pageTextContains('Test multiple entries and fields name 2');

    // Click edit link.
    $this->click('#site-setting-6 a:contains("Edit")');
    $this->assertSession()->pageTextContains('Edit Test multiple entries and fields name 2');

    // Open the site settings list page.
    $this->drupalGet('admin/content/site-settings');

    // Click edit link.
    $this->click('#site-setting-5 a:contains("Edit")');
    $this->assertSession()->pageTextContains('Edit Test multiple entries and fields name 1');

    $this->drupalLogout();

    // Create a creator user for test.
    $creator = $this->drupalCreateUser([
      'administer site configuration',
      'access site settings overview',
      'create test_multiple_entries_and_fields site setting',
      'create test_plain_text site setting',
      'view published test_multiple_entries_and_fields site setting entities',
    ]);

    $this->drupalLogin($creator);

    // Open the site settings list page.
    $this->drupalGet('admin/content/site-settings');

    // Make sure the test plain text is not expected.
    $this->assertSession()->pageTextNotContains('Test plain text');

    // Make sure the test multiple entries and fields contents are as expected.
    $this->assertSession()->pageTextContains('Test multiple entries and fields');

    // Click add link.
    $this->click('#site-setting-6 a:contains("Add another")');

    // Make sure the test multiple entries and fields create page is as
    // expected.
    $this->assertSession()->pageTextContains('Test multiple entries and fields');

    // Open the site settings list page.
    $this->drupalGet('admin/structure/site_setting_entity/add/test_plain_text');
    $this->assertSession()->pageTextContains('Access denied');

    $this->drupalLogout();

    // Create a remover user for test.
    $remover = $this->drupalCreateUser([
      'administer site configuration',
      'access site settings overview',
      'view published test_multiple_entries_and_fields site setting entities',
      'view unpublished test_multiple_entries_and_fields site setting entities',
      'view published test_plain_text site setting entities',
      'view unpublished test_plain_text site setting entities',
      'delete test_multiple_entries_and_fields site setting',
      'delete test_plain_text site setting',
    ]);

    $this->drupalLogin($remover);

    // Open the site settings list page.
    $this->drupalGet('admin/content/site-settings');

    // Make sure the test plain text is expected.
    $this->assertSession()->pageTextContains('Test plain text');

    // Make sure the test multiple entries and fields contents are as expected.
    $this->assertSession()->pageTextContains('Test multiple entries and fields');

    // Click delete link.
    $this->click('#site-setting-6 a:contains("Delete")');

    // Make sure the test plain text delete page is visible.
    $this->assertSession()->pageTextContains('This action cannot be undone.');
    $this->drupalGet($this->getUrl());

    // Click delete link.
    $this->submitForm([], 'Delete');

    // Make sure the Test multiple entries and fields name 2 is not expected,
    // but Test multiple entries and fields name 1 still is there.
    $this->assertSession()->elementTextNotContains('css', 'table', 'Test multiple entries and fields name 2');
    $this->assertSession()->elementTextContains('css', 'table', 'Test multiple entries and fields name 1');

    $this->drupalLogout();

    // Login creator:
    $this->drupalLogin($creator);

    // Open the site settings list page.
    $this->drupalGet('admin/content/site-settings');

    // Make sure the test multiple entries and fields is expected.
    $this->assertSession()->pageTextContains('Test multiple entries and fields name 1');
  }

}
