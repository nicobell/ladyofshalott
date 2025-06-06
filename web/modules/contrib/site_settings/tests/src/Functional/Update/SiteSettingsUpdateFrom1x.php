<?php

namespace Drupal\site_settings\Tests\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Provides tests for the update from 8.x-1.x to 2.0.x.
 */
class SiteSettingsUpdateFrom1x extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../fixtures/update/drupal.10.3-site-settings-8.x-1.x.php.gz',
    ];
  }

  /**
   * Tests that DESCRIBE YOUR UPDATE HERE.
   */
  public function testUpdateFrom1x() {
    $this->runUpdates();

    // Check that there are no misconfigured entities.
    /** @var \Drupal\Core\Entity\EntityDefinitionUpdateManager $definition_update_manager */
    $definition_update_manager = \Drupal::entityDefinitionUpdateManager();
    $this->assertSame([], $definition_update_manager->getChangeList());
  }

}
