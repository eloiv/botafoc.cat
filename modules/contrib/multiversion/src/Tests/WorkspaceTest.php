<?php

/**
 * @file
 * Contains \Drupal\multiversion\Tests\WorkspaceTest.
 */

namespace Drupal\multiversion\Tests;

use Drupal\multiversion\Entity\WorkspaceInterface;
use Drupal\simpletest\WebTestBase;

/**
 * Test the workspace entity.
 *
 * @group multiversion
 */
class WorkspaceTest extends WebTestBase {

  protected $strictConfigSchema = FALSE;

  public static $modules = array('multiversion');

  public function testOperations() {
    $default = entity_load('workspace', 'default');
    $this->assertTrue(!empty($default), 'Default workspace was created when installing Multiversion module.');
    $id = $this->randomMachineName();
    $entity = entity_create('workspace', array('id' => $id));

    $this->assertTrue($entity instanceof WorkspaceInterface, 'Workspace entity was created.');

    $entity->save();
    $this->assertEqual($entity->id(), $id, 'Workspace entity was saved.');

    $entity = entity_load('workspace', $entity->id());
    $this->assertEqual($entity->id(), $id, 'Workspace entity was loaded by ID.');

    $entity = \Drupal::entityManager()->loadEntityByUuid('workspace', $entity->uuid());
    $this->assertEqual($entity->id(), $id, 'Workspace entity was loaded by UUID.');
    $this->assertEqual($entity->label(), $id, 'Label method returns the workspace name.');

    $created = $entity->getStartTime();
    $this->assertNotNull($created, "The value for 'created' field is not null.");

    $new_created_time = microtime(TRUE) * 1000000;
    $entity->setCreatedTime((int) $new_created_time);
    $this->assertEqual($entity->getStartTime(), $new_created_time, "Correct value for 'created' field.");
  }

}
