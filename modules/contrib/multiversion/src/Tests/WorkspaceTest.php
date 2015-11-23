<?php

/**
 * @file
 * Contains \Drupal\multiversion\Tests\WorkspaceTest.
 */

namespace Drupal\multiversion\Tests;

use Drupal\KernelTests\KernelTestBase;
use Drupal\multiversion\Entity\Workspace;
use Drupal\multiversion\Entity\WorkspaceInterface;

/**
 * Test the workspace entity.
 *
 * @group multiversion
 */
class WorkspaceTest extends KernelTestBase {

  protected $strictConfigSchema = FALSE;

  public static $modules = ['multiversion', 'key_value', 'serialization'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['multiversion']);
  }

  public function testOperations() {
    $default = Workspace::load('default');
    $this->assertTrue(!empty($default), 'Default workspace was created when installing Multiversion module.');
    $id = $this->randomMachineName();
    $entity = Workspace::create(['id' => $id]);

    $this->assertTrue($entity instanceof WorkspaceInterface, 'Workspace entity was created.');

    $entity->save();
    $this->assertEquals($id, $entity->id(), 'Workspace entity was saved.');

    $entity = Workspace::load($entity->id());
    $this->assertEquals($id, $entity->id(), 'Workspace entity was loaded by ID.');

    $entity = \Drupal::entityManager()->loadEntityByUuid('workspace', $entity->uuid());
    $this->assertEquals($id, $entity->id(), 'Workspace entity was loaded by UUID.');
    $this->assertEquals($id, $entity->label(), 'Label method returns the workspace name.');

    $created = $entity->getStartTime();
    $this->assertNotNull($created, "The value for 'created' field is not null.");

    $new_created_time = microtime(TRUE) * 1000000;
    $entity->setCreatedTime((int) $new_created_time);
    $this->assertEquals($new_created_time, $entity->getStartTime(), "Correct value for 'created' field.");
  }

}
