<?php

/**
 * @file
 * Contains \Drupal\multiversion\Tests\UuidIndexTest.
 */

namespace Drupal\multiversion\Tests;

/**
 * Test the methods on the UuidIndex class.
 *
 * @group multiversion
 */
class UuidIndexTest extends MultiversionWebTestBase {

  public function testMethods() {
    $entity = entity_create('entity_test');
    $uuid = $entity->uuid();

    $this->uuidIndex->add($entity);
    $entry = $this->uuidIndex->get($uuid);
    $expected = array(
      'entity_type_id' => 'entity_test',
      'entity_id' => 0,
      'revision_id' => 0,
      'uuid' => $uuid,
      'rev' => $entity->_rev->value,
      'status' => 'indexed',
    );
    $this->assertEqual($entry, $expected, 'Single entry is correct for an entity that was not yet saved.');

    $entity->save();
    $this->uuidIndex->add($entity);
    $entry = $this->uuidIndex->get($uuid);
    $expected = array(
      'entity_type_id' => 'entity_test',
      'entity_id' => 1,
      'revision_id' => 1,
      'uuid' => $uuid,
      'rev' => $entity->_rev->value,
      'status' => 'available',
    );
    $this->assertEqual($entry, $expected, 'Single entry is correct for an entity that was saved.');

    $entities = array();
    $uuid = array();
    $rev = array();

    $entity = $entities[] = entity_create('entity_test');
    $uuid[] = $entity->uuid();
    $rev[] = $entity->_rev->value;

    $entity = $entities[] = entity_create('entity_test');
    $uuid[] = $entity->uuid();
    $rev[] = $entity->_rev->value;

    $this->uuidIndex->addMultiple($entities);
    $expected = array(
      $uuid[0] => array(
        'entity_type_id' => 'entity_test',
        'entity_id' => 0,
        'revision_id' => 0,
        'rev' => $rev[0],
        'uuid' => $uuid[0],
        'status' => 'indexed',
      ),
      $uuid[1] => array(
        'entity_type_id' => 'entity_test',
        'entity_id' => 0,
        'revision_id' => 0,
        'rev' => $rev[1],
        'uuid' => $uuid[1],
        'status' => 'indexed',
      ),
    );
    $entries = $this->uuidIndex->getMultiple(array($uuid[0], $uuid[1]));
    $this->assertEqual($entries, $expected, 'Multiple entries are correct.');

    // Create a new workspaces and query those.
    $ws1 = $this->randomMachineName();
    entity_create('workspace', array('id' => $ws1));
    $ws2 = $this->randomMachineName();
    entity_create('workspace', array('id' => $ws2));

    $entity = entity_create('entity_test');
    $uuid = $entity->uuid();
    $rev = $entity->_rev->value;

    $this->uuidIndex->useWorkspace($ws1)->add($entity);
    $entry = $this->uuidIndex
      ->useWorkspace($ws2)
      ->get($uuid);
    $this->assertTrue(empty($entry), 'New workspace is empty');

    $this->uuidIndex
      ->useWorkspace($ws2)
      ->add($entity);

    $entry = $this->uuidIndex
      ->useWorkspace($ws2)
      ->get($uuid);

    $expected = array(
      'entity_type_id' => 'entity_test',
      'entity_id' => 0,
      'revision_id' => 0,
      'rev' => $rev,
      'uuid' => $uuid,
      'status' => 'indexed',
    );
    $this->assertEqual($entry, $expected, 'Entry was added and fetched from new workspace.');
  }

}
