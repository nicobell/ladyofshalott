<?php

namespace Drupal\Tests\site_settings\Functional;

use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the loading of Site Settings.
 *
 * @group SiteSettings
 */
class SiteSettingsUiTest extends BrowserTestBase {
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Module list.
   *
   * @var array
   */
  protected static $modules = [
    'site_settings',
    'site_settings_sample_data',
    'field_ui',
    'user',
    'language',
    'menu_ui',
  ];

  /**
   * Admin user.
   *
   * @var \Drupal\user\Entity\User|false
   */
  private $adminUser;

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function setUp():void {
    parent::setUp();

    // Create the user and login.
    $this->adminUser = $this->createUser([], NULL, TRUE);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Test site settings admin visibility.
   */
  public function testSiteSettingsAdminVisibility() {
    // Open the site settings list page.
    $this->drupalGet('admin/content/site-settings');

    // Get table header, ensuring expected columns exist there.
    $this->assertSession()->elementTextEquals(
      'css',
      'table thead',
      'ID Group Sort descending Type Description Value Operations',
    );

    $session = $this->assertSession();

    // Make sure the groups match.
    $session->responseContains('Images');
    $session->responseContains('Other');

    // Make sure the test plain text is as expected.
    $session->pageTextContains('Test plain text');
    $session->pageTextContains('Test plain text description');

    // Make sure the test textarea is as expected.
    $session->pageTextContains('Test textarea name');

    // Make sure the test multiple entries contents are as expected.
    $session->pageTextContains('Test multiple entries');
    $session->pageTextContains('Test multiple entries name 2');

    // Make sure the test multiple entries and fields contents are as expected.
    $session->pageTextContains('Test multiple entries and fields name 1');
    $session->pageTextContains('Test multiple entries and fields name 2');

    // Make sure the test multiple fields contents are as expected.
    $session->pageTextContains('Test multiple fields name');

    // Make sure the test image is as expected.
    $session->pageTextContains('Test image');
    $session->pageTextContains('Test images 1');
    $session->pageTextContains('Test file');

    // Check that the edit screen contains the expected fields.
    $this->drupalGet('admin/structure/site_setting_entity/1/edit');
    $page = $this->getSession()->getPage();
    $page->hasContent('Description');
    $page->hasContent('Test plain text description');
    $page->hasContent('Group');
    $page->hasContent('Other');
    $page->hasContent('Revision information');

    // Change visibility on field description. This also affects the listing.
    $config = \Drupal::configFactory()->getEditable('site_settings.config');
    $config->set('hide_description', TRUE);
    $config->save();
    drupal_flush_all_caches();
    $this->drupalGet('admin/content/site-settings');
    $session = $this->assertSession();
    $session->pageTextNotContains('Test plain text description');

    // Change visibility on field group.
    $config->set('hide_group', TRUE);
    $config->set('hide_advanced', TRUE);
    $config->save();
    drupal_flush_all_caches();
    $this->drupalGet('admin/structure/site_setting_entity/1/edit');
    $session = $this->assertSession();
    $session->pageTextNotContains('Description');
    $session->pageTextNotContains('Test plain text description');
    $session->pageTextNotContains('Group');
    $session->pageTextNotContains('other');
    $session->pageTextNotContains('Revision information');

    $config->set('simple_summary', TRUE);
    $config->save();
    drupal_flush_all_caches();
    $this->drupalGet('admin/structure/site_setting_entity/2/edit');
    $params = [
      'field_test_textarea[0][value]' => 'This module provides a way to let clients manage settings you define without affecting the configuration of the site (ie, as Content). Here we are testing a string length longer than 150 characters to make sure it gets cropped.',
    ];
    $this->submitForm($params, 'Save');
    $this->drupalGet('admin/content/site-settings');
    $expected_cropped_text = 'This module provides a way to let clients manage settings you define without affecting the configuration of the site (ie, as Content)....';
    $this->assertSession()->pageTextContains($expected_cropped_text);
    $this->assertSession()->pageTextNotContains('longer than 150 characters');
  }

  /**
   * Test site settings add another.
   */
  public function testSiteSettingsAddAnother() {
    // Open the site settings list page.
    $this->drupalGet('admin/content/site-settings');

    // Click add another link.
    $this->click('#site-setting-3 a:contains("Add another")');
    $session = $this->assertSession();

    // Make sure we can see the expected form.
    $session->pageTextContains('Test multiple entries');
    $session->pageTextContains('Testing');
    $params = [
      'field_testing[0][value]' => 'testSiteSettingsAddAnother',
    ];
    $this->submitForm($params, 'Save');
    $session = $this->assertSession();

    // Ensure we saved correctly.
    $session->pageTextContains('Created the Test multiple entries Site Setting.');
    $session->pageTextContains('testSiteSettingsAddAnother');
  }

  /**
   * Test site settings edit existing.
   */
  public function testSiteSettingsEditExisting() {
    // Open the site settings list page.
    $this->drupalGet('admin/content/site-settings');
    $session = $this->assertSession();

    // Click add another link.
    $this->click('#site-setting-1 a:contains("Edit")');
    $session = $this->assertSession();

    // Make sure we can see the expected form.
    $session->pageTextContains('Test plain text');
    $session->pageTextContains('Testing');
    $params = [
      'field_testing[0][value]' => 'testSiteSettingsEditExisting',
    ];
    $this->submitForm($params, 'Save');
    $session = $this->assertSession();

    // Ensure we saved correctly.
    $session->pageTextContains('Saved the Test plain text Site Setting.');
    $session->pageTextContains('testSiteSettingsEditExisting');
  }

  /**
   * Test site settings create new type and add a setting to that.
   */
  public function testSiteSettingsCreateNewTypeAndSetting() {
    // Open the site settings list page.
    $this->drupalGet('admin/structure/site_setting_entity_type/add');

    // Create the new site setting.
    $params = [
      'label' => 'testSiteSettingsCreateNewTypeAndSetting',
      'id' => 'testsitesettingscreatenew',
      'existing_group' => 'other',
    ];
    $this->submitForm($params, 'Save');
    $session = $this->assertSession();

    // Ensure we saved correctly.
    $session->pageTextContains('Created the testSiteSettingsCreateNewTypeAndSetting Site Setting type.');

    // Add field.
    $this->drupalGet('admin/structure/site_setting_entity_type/testsitesettingscreatenew/fields/add-field');
    $params = [
      'new_storage_type' => 'plain_text',
    ];
    $this->submitForm($params, 'Continue');
    $params = [
      'label' => 'testSiteSettingsCreateNewTypeAndSettingLabel',
      'field_name' => 'testing_again',
      'group_field_options_wrapper' => 'string',
    ];
    $this->submitForm($params, 'Continue');

    // Save field settings.
    $params = [];
    $this->submitForm($params, 'Save settings');
    $session = $this->assertSession();

    // Ensure we saved correctly.
    $session->pageTextContains('Saved testSiteSettingsCreateNewTypeAndSettingLabel configuration.');

    // Open the site settings list page.
    $this->drupalGet('admin/content/site-settings');
    $session = $this->assertSession();

    // Click add another link.
    $this->clickLink('Create setting');
    $session->pageTextContains('testSiteSettingsCreateNewTypeAndSettingLabel');
    $params = [
      'field_testing_again[0][value]' => 'testSiteSettingsCreateNewTypeAndSettingValue',
    ];
    $this->submitForm($params, 'Save');
    $session = $this->assertSession();

    // Ensure we saved correctly.
    $session->pageTextContains('Created the testSiteSettingsCreateNewTypeAndSetting Site Setting.');
    $session->pageTextContains('testSiteSettingsCreateNewTypeAndSettingValue');
  }

  /**
   * Test site settings groups.
   */
  public function testSiteSettingsUpdateGroup() {
    // Open the site settings list page.
    $this->drupalGet('admin/content/site-settings');

    // Click on the group type 'Boolean'.
    $this->clickLink('Boolean');

    // Change the group label.
    $params = [
      'label' => 'testSiteSettingsUpdateGroupLabel',
    ];
    $this->submitForm($params, 'Save site settings group entity type');
    $session = $this->assertSession();
    $session->pageTextContains('The site settings group entity type testSiteSettingsUpdateGroupLabel has been updated');

    // Open the site settings list page.
    $this->drupalGet('admin/content/site-settings');

    // Ensure that both groups were updated.
    $session = $this->assertSession();
    $session->elementTextContains('css', '#site-setting-11', 'testSiteSettingsUpdateGroupLabel');
    $session->elementTextContains('css', '#site-setting-12', 'testSiteSettingsUpdateGroupLabel');
  }

  /**
   * Test site settings groups in menu.
   */
  public function testSiteSettingsGroupsInMenu() {

    // Check the menu tree items exists.
    $tree = \Drupal::menuTree()->load('admin', new MenuTreeParameters());
    /** @var \Drupal\Core\Menu\MenuLinkTreeElement $admin */
    $admin = reset($tree);
    /** @var \Drupal\Core\Menu\MenuLinkTreeElement $admin_content */
    $admin_content = $admin->subtree['system.admin_content'];
    /** @var \Drupal\Core\Menu\MenuLinkTreeElement $site_settings_collection */
    $site_settings_collection = $admin_content->subtree['entity.site_setting_entity.collection'];
    $this->assertSame([
      'site_settings.setting_menu_links:entity.site_setting_group.boolean.collection',
      'site_settings.setting_menu_links:entity.site_setting_group.images.collection',
      'site_settings.setting_menu_links:entity.site_setting_group.other.collection',
    ], array_keys($site_settings_collection->subtree));

    // Check that the menu tree item leads to a filtered collection.
    /** @var \Drupal\Core\Menu\MenuLinkTreeElement $boolean_link */
    $boolean_link = $site_settings_collection->subtree['site_settings.setting_menu_links:entity.site_setting_group.boolean.collection'];
    $this->assertStringEndsWith('admin/content/site-settings?group=boolean', $boolean_link->link->getUrlObject()->toString());
    $this->drupalGet('admin/content/site-settings', [
      'query' => [
        'group' => 'boolean',
      ],
    ]);

    // Expect two boolean rows.
    $this->assertSession()->elementsCount('css', 'table tbody tr', 2);
    $this->assertSession()->pageTextContains('Test boolean');

    // Change visibility to hide the Groups.
    $config = \Drupal::configFactory()->getEditable('site_settings.config');
    $config->set('show_groups_in_menu', FALSE);
    $config->save();
    drupal_flush_all_caches();

    // Check the menu tree items exists.
    $tree = \Drupal::menuTree()->load('admin', new MenuTreeParameters());
    /** @var \Drupal\Core\Menu\MenuLinkTreeElement $admin */
    $admin = reset($tree);
    /** @var \Drupal\Core\Menu\MenuLinkTreeElement $admin_content */
    $admin_content = $admin->subtree['system.admin_content'];
    /** @var \Drupal\Core\Menu\MenuLinkTreeElement $site_settings_collection */
    $site_settings_collection = $admin_content->subtree['entity.site_setting_entity.collection'];
    $this->assertSame([], array_keys($site_settings_collection->subtree));
  }

}
