<?php

/**
 * @file
 * Contains \Drupal\multiversion\Tests\EntityStorageTest.
 */

namespace Drupal\multiversion\Tests;

/**
 * Test the content entity storage controller.
 *
 * @group multiversion
 */
class EntityStorageTest extends MultiversionWebTestBase {

  /**
   * The entity types to test.
   *
   * @var array
   */
  protected $entityTypes = array(
    'entity_test' => array(
      'info' => array(),
      'data_table' => 'entity_test',
      'revision_table' => 'entity_test_revision',
      'id' => 'id',
    ),
    'entity_test_rev' => array(
      'info' => array(),
      'data_table' => 'entity_test_rev',
      'revision_table' => 'entity_test_rev_revision',
      'id' => 'id',
    ),
    'entity_test_mul' => array(
      'info' => array(),
      'data_table' => 'entity_test_mul_property_data',
      'revision_table' => 'entity_test_mul_field_revision',
      'id' => 'id',
    ),
    'entity_test_mulrev' => array(
      'info' => array(),
      'data_table' => 'entity_test_mulrev_property_data',
      'revision_table' => 'entity_test_mulrev_property_revision',
      'id' => 'id',
    ),
    'node' => array(
      'info' => array(
        'type' => 'article',
        'title' => 'New article',
      ),
      'data_table' => 'node_field_data',
      'revision_table' => 'node_field_revision',
      'id' => 'nid',
    ),
    'taxonomy_term' => array(
      'info' => array(
        'name' => 'A term',
        'vid' => 123,
      ),
      'data_table' => 'taxonomy_term_field_data',
      'revision_table' => 'taxonomy_term_field_revision',
      'id' => 'tid',
    ),
    'comment' => array(
      'info' => array(
        'entity_type' => 'node',
        'field_name' => 'comment',
        'subject' => 'How much wood would a woodchuck chuck',
        'mail' => 'someone@example.com',
      ),
      'data_table' => 'comment_field_data',
      'revision_table' => 'comment_field_revision',
      'id' => 'cid',
    ),
    'block_content' =>  array(
      'info' => array(
        'info' => 'New block',
        'type' => 'basic',
      ),
      'data_table' => 'block_content_field_data',
      'revision_table' => 'block_content_field_revision',
      'id' => 'id',
    ),
    'menu_link_content' => array(
      'info' => array(
        'menu_name' => 'menu_test',
        'bundle' => 'menu_link_content',
        'link' => [['uri' => 'user-path:/']],
      ),
      'data_table' => 'menu_link_content_data',
      'revision_table' => 'menu_link_content_field_revision',
      'id' => 'id',
    ),
    'user' => array(
      'info' => array(
        'name' => 'User',
        'mail' => 'user@example.com',
        'status' => 1,
      ),
      'data_table' => 'users_field_data',
      'revision_table' => 'user_field_revision',
      'id' => 'uid',
    ),
  );

  public function setUp() {
    parent::setUp();

    // Create menu_test menu.
    $this->createMenu();

    foreach ($this->entityTypes as $entity_type_id => $info) {
      $this->entityTypes[$entity_type_id]['revision_id'] = $entity_type_id == 'node' ? 'vid' : 'revision_id';
      if ($entity_type_id == 'node' || $entity_type_id == 'menu_link_content') {
        $this->entityTypes[$entity_type_id]['name'] = 'title';
      }
      elseif ($entity_type_id == 'block_content') {
        $this->entityTypes[$entity_type_id]['name'] = 'info';
      }
      else {
        $this->entityTypes[$entity_type_id]['name'] = 'name';
      }
    }
  }

  public function testSaveAndLoad() {
    foreach ($this->entityTypes as $entity_type_id => $info) {
      $ids = array();
      $entity = entity_create($entity_type_id, $info['info']);
      $return = $entity->save();
      // @todo: {@link https://www.drupal.org/node/2597496 Remove this check
      // now that https://www.drupal.org/node/2462265 is committed.}
      if ($entity_type_id != 'user') {
        $this->assertEqual($return, SAVED_NEW, "$entity_type_id was saved.");
      }

      $ids[] = $entity->id();
      $loaded = entity_load($entity_type_id, $ids[0]);
      $this->assertEqual($ids[0], $loaded->id(), "Single $entity_type_id was loaded.");

      // @todo: {@link https://www.drupal.org/node/2597508 Test
      // loadEntityByUuid.}
      // Update and save a new revision.
      $entity->{$info['name']} = $this->randomMachineName();
      $entity->save();
      // For user entity type we should have three entities: anonymous, root
      // user and the new created user. For other entity types we should have
      // just the new created entity.
      $revision_id = $entity_type_id == 'user' ? 3 : 1;
      /** @var \Drupal\Core\Entity\ContentEntityInterface $revision */
      $revision = entity_revision_load($entity_type_id, $revision_id);
      $this->assertTrue(($revision->getRevisionId() == $revision_id && !$revision->isDefaultRevision()), "Old revision of $entity_type_id was loaded.");

      if ($entity_type_id == 'block_content') {
        $info['info']['info'] = $this->randomMachineName();
      }
      $entity = entity_create($entity_type_id, $info['info']);
      $entity->save();
      $ids[] = $entity->id();

      $entities = entity_load_multiple($entity_type_id, $ids);
      $this->assertEqual(count($entities), 2, "Multiple $entity_type_id was loaded.");
    }
  }

  public function testDelete() {
    foreach ($this->entityTypes as $entity_type_id => $info) {
      $entity = entity_create($entity_type_id, $info['info']);
      $entity->save();
      $id = $entity->id();
      $revision_id = $entity->getRevisionId();
      entity_delete_multiple($entity_type_id, array($id));

      // For user entity type we should have revision ID 4 because we have three
      // users: anonymous, root user and the new user (it was deleted).
      $revision = $entity_type_id == 'user' ? 4 : 2;
      $record = db_select($info['revision_table'], 'e')
        ->fields('e')
        ->condition('e.' . $info['id'], $id)
        ->condition('e.' . $info['revision_id'], $revision)
        ->execute()
        ->fetchObject();

      $this->assertEqual($record->_deleted, 1, "Deleted $entity_type_id is still stored but flagged as deleted");
      $entity = entity_load($entity_type_id, $id);
      $this->assertTrue(empty($entity), "Deleted $entity_type_id did not load with entity_load() function.");

      $entity = entity_load_deleted($entity_type_id, $id);
      $this->assertTrue(!empty($entity), "Deleted $entity_type_id loaded with entity_load_deleted() function.");
      $this->assertNotEqual($revision_id, $entity->getRevisionId(), "New revision was generated when deleting $entity_type_id.");

      $entities = entity_load_multiple_deleted($entity_type_id, array($id));
      $this->assertTrue(!empty($entities), "Deleted $entity_type_id loaded with entity_load_multiple_deleted() function.");
    }
  }

  public function testRevisions() {
    foreach ($this->entityTypes as $entity_type_id => $info) {
      $entity = entity_create($entity_type_id, $info['info']);
      $entity->save();
      $id = $entity->id();
      $revision_id = $entity->getRevisionId();
      $revision = entity_revision_load($entity_type_id, $revision_id);

      $this->assertEqual($revision_id, $revision->getRevisionId(), "$entity_type_id revision was loaded");

      entity_delete_multiple($entity_type_id, array($id));
      $new_revision_id = ($revision_id + 1);
      $revision = entity_revision_load($entity_type_id, $new_revision_id);
      $this->assertTrue(($revision->_deleted->value == TRUE && $revision->getRevisionId() == $new_revision_id), "Deleted $entity_type_id was loaded.");
    }
  }

  public function testExceptions() {
    foreach ($this->entityTypes as $entity_type_id => $info) {
      $entity_type = $this->entityManager->getDefinition($entity_type_id);
      $id_key = $entity_type->getKey('id');
      // Test with exception upon first save.
      if ($entity_type_id == 'block_content') {
        $info['info']['info'] = $this->randomMachineName();
      }
      $entity = entity_create($entity_type_id, $info['info']);
      $uuid = $entity->uuid->value;
      try {
        // Trigger an error by setting the ID too large.
        $entity->{$id_key}->value = PHP_INT_MAX;
        $entity->save();
        $this->fail('Exception was not generated.');
      }
      catch(\Exception $e) {
        $first_rev = $entity->_rev->value;
        $rev_info = $this->revIndex->get($first_rev);
        $this->assertEqual($rev_info['status'], 'indexed', 'First revision was indexed after exception on first save.');
      }
      // Re-save the same entity with a valid ID.
      $entity->{$id_key}->value = NULL;
      $entity->save();
      $second_rev = $entity->_rev->value;
      $this->assertEqual($first_rev, $second_rev, 'New revision was not generated after first re-save.');

      $rev_info = $this->revIndex->get($first_rev);
      $this->assertEqual($rev_info['status'], 'available', 'First revision is available after first re-save.');
      $default_branch = $this->revTree->getDefaultBranch($uuid);
      $expected_default_branch = array(
        $first_rev => 'available',
      );
      $this->assertEqual($default_branch, $expected_default_branch, 'Default branch was built after exception on first save followed by re-save.');

      // Test with exception upon second save.
      if ($entity_type_id == 'block_content') {
        $info['info']['info'] = $this->randomMachineName();
      }
      if ($entity_type_id == 'user') {
        $info['info']['name'] = $this->randomMachineName();
      }
      $entity = entity_create($entity_type_id, $info['info']);
      $uuid = $entity->uuid->value;
      $entity->save();
      $first_id = $entity->id();
      $first_rev = $entity->_rev->value;
      try {
        // Temporary solution.
        // @todo: {@link https://www.drupal.org/node/2597516 Remove now that
        // https://www.drupal.org/node/2453153 is fixed.}
        $entity->original = clone $entity;

        // Trigger an error by setting the ID too large.
        $entity->{$id_key}->value = PHP_INT_MAX;
        $entity->save();
        $this->fail('Exception was not generated.');
      }
      catch(\Exception $e) {
        $second_rev = $entity->_rev->value;
        $rev_info = $this->revIndex->get($second_rev);
        $this->assertEqual($rev_info['status'], 'indexed', 'Second revision was indexed after exception on second save.');
      }
      // Re-save the same entity with a valid ID.
      $entity->{$id_key}->value = $first_id;
      $entity->save();
      $third_rev = $entity->_rev->value;
      $this->assertEqual($second_rev, $third_rev, 'New revision was not generated after second re-save.');

      $rev_info = $this->revIndex->get($second_rev);
      $this->assertEqual($rev_info['status'], 'available', 'Third revision is available after second re-save.');
      $default_branch = $this->revTree->getDefaultBranch($uuid);
      $expected_default_branch = array(
        $first_rev => 'available',
        $second_rev => 'available',
      );
      $this->assertEqual($default_branch, $expected_default_branch, 'Default branch was built after exception on second save followed by re-save.');
    }
  }

  public function testWorkspace() {
    foreach ($this->entityTypes as $entity_type_id => $info) {
      $entity = entity_create($entity_type_id, $info['info']);
      $entity->save();
      $entity_id = $entity->id();
      $this->assertEqual($entity->workspace->target_id, 'default', "The workspace reference was saved for $entity_type_id.");
      $record = db_select($info['data_table'], 'e')
        ->fields('e')
        ->condition('e.' . $info['id'], $entity->id())
        ->condition('e.' . $info['revision_id'], $entity->getRevisionId())
        ->execute()
        ->fetchObject();
      $this->assertEqual($record->workspace, 'default', "The workspace reference was stored for saved $entity_type_id.");

      $entity = entity_load($entity_type_id, $entity_id);
      $this->assertEqual($entity->workspace->target_id, 'default', "The workspace reference is retained for loaded $entity_type_id.");
      $record = db_select($info['data_table'], 'e')
        ->fields('e')
        ->condition('e.' . $info['id'], $entity->id())
        ->condition('e.' . $info['revision_id'], $entity->getRevisionId())
        ->execute()
        ->fetchObject();
      $this->assertEqual($record->workspace, 'default', "The workspace reference was stored for loaded $entity_type_id.");

      entity_delete_multiple($entity_type_id, array($entity_id));
      $entity = entity_load_deleted($entity_type_id, $entity_id);
      $this->assertEqual($entity->workspace->target_id, 'default', "The workspace reference is retained for deleted $entity_type_id.");
      $record = db_select($info['data_table'], 'e')
        ->fields('e')
        ->condition('e.' . $info['id'], $entity->id())
        ->condition('e.' . $info['revision_id'], $entity->getRevisionId())
        ->execute()
        ->fetchObject();
      $this->assertEqual($record->workspace, 'default', "The workspace reference was stored for deleted $entity_type_id.");
    }

    // Create a new workspace and switch to it.
    $workspace = entity_create('workspace', array('id' => $this->randomMachineName()));
    $this->workspaceManager->setActiveWorkspace($workspace);

    foreach ($this->entityTypes as $entity_type_id => $info) {
      if ($entity_type_id == 'block_content') {
        $info['info']['info'] = $this->randomMachineName();
      }
      if ($entity_type_id == 'user') {
        $info['info']['name'] = $this->randomMachineName();
      }
      $entity = entity_create($entity_type_id, $info['info']);
      $entity->save();
      $this->assertEqual($entity->workspace->target_id, $workspace->id(), "$entity_type_id was saved in new workspace.");
    }

    $uuids = array();
    $ids = array();
    foreach ($this->entityTypes as $entity_type_id => $info) {
      if ($entity_type_id == 'block_content') {
        $info['info']['info'] = $this->randomMachineName();
      }
      if ($entity_type_id == 'user') {
        $info['info']['name'] = $this->randomMachineName();
      }
      $entity = entity_create($entity_type_id, $info['info']);
      $entity->save();
      $uuids[$entity_type_id] = $entity->uuid();
      $ids[$entity_type_id] = $entity->id();

      $entity = entity_load($entity_type_id, $ids[$entity_type_id]);
      $this->assertTrue(!empty($entity), "$entity_type_id was loaded in the workspace it belongs to.");
      $entity = $this->entityManager->loadEntityByUuid($entity_type_id, $uuids[$entity_type_id]);
      $this->assertTrue(!empty($entity), "$entity_type_id was loaded by UUID in the workspace it belongs to.");
    }

    $this->multiversionManager->setActiveWorkspaceId('default');

    foreach ($this->entityTypes as $entity_type_id => $info) {
      $entity = entity_load($entity_type_id, $ids[$entity_type_id]);
      if ($entity_type_id == 'user') {
        $this->assertFalse(empty($entity), "$entity_type_id was loaded in a workspace it does not belongs to.");
      }
      else {
        $this->assertTrue(empty($entity), "$entity_type_id was not loaded in a workspace it does not belongs to.");
      }
      $entity = $this->entityManager->loadEntityByUuid($entity_type_id, $uuids[$entity_type_id]);
      if ($entity_type_id == 'user') {
        $this->assertFalse(empty($entity), "$entity_type_id was loaded by UUID in a workspace it does not belong to.");
      }
      else {
        $this->assertTrue(empty($entity), "$entity_type_id was not loaded by UUID in a workspace it does not belong to.");
      }

    }
  }

}
