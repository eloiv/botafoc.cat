<?php

/**
 * @file
 * Contains \Drupal\multiversion\Tests\MigratePasswordTest.
 */

namespace Drupal\multiversion\Tests;

use Drupal\KernelTests\KernelTestBase;

/**
 * Test password hashing on migration.
 *
 * @group multiversion
 */
class MigratePasswordTest extends KernelTestBase {

  protected $strictConfigSchema = FALSE;

  public static $modules = [
    'multiversion',
    'key_value',
    'serialization',
    'user',
  ];

  public function testPasswordMigration() {
    $password_migrate = \Drupal::service('password_migrate');
    $password = $this->randomMachineName();
    $migrated_password = $password_migrate->hash($password);
    $this->assertNotEquals($password, $migrated_password, 'Migrated password was hashed.');

    $password_migrate->disablePasswordHashing();
    $password = $this->randomMachineName();
    $migrated_password = $password_migrate->hash($password);
    $this->assertEquals($password, $migrated_password, 'Migrated password was not hashed.');
  }

}
