<?php

/**
 * @file
 * Contains \Drupal\multiversion\Tests\MigratePasswordTest.
 */

namespace Drupal\multiversion\Tests;

/**
 * Test password hashing on migration.
 *
 * @group multiversion
 */
class MigratePasswordTest extends MultiversionWebTestBase {

  public function testPasswordMigration() {
    $password_migrate = \Drupal::service('password_migrate');
    $password = $this->randomMachineName();
    $migrated_password = $password_migrate->hash($password);
    $this->assertNotEqual($migrated_password, $password, 'Migrated password was hashed.');

    $password_migrate->disablePasswordHashing();
    $password = $this->randomMachineName();
    $migrated_password = $password_migrate->hash($password);
    $this->assertEqual($migrated_password, $password, 'Migrated password was not hashed.');
  }

}
