<?php

/**
 * @file
 * Contains \Drupal\multiversion\Tests\EntityQueryTest.
 */

namespace Drupal\multiversion\Tests;

/**
 * Test the altered entity query functionality.
 *
 * @group multiversion
 */
class EntityQueryTest extends MultiversionWebTestBase {

  /**
   * The entity types to test.
   *
   * @var array
   */
  protected $entityTypes = array(
    'entity_test' => array(),
    'entity_test_rev' => array(),
    'entity_test_mul' => array(),
    'entity_test_mulrev' => array(),
    'node' => array(
      'type' => 'article',
      'title' => 'New article',
    ),
    'taxonomy_term' => array(
      'name' => 'A term',
      'vid' => 123,
    ),
    'comment' => array(
      'entity_type' => 'node',
      'field_name' => 'comment',
      'subject' => 'How much wood would a woodchuck chuck',
      'mail' => 'someone@example.com',
    ),
    'user' => array(
      'name' => 'User',
      'mail' => 'user@example.com',
      'status' => 1,
    ),
  );

  /**
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $factory;

  public function setUp() {
    parent::setUp();

    $this->factory = \Drupal::service('entity.query');
  }

  public function testQuery() {

    foreach ($this->entityTypes as $entity_type_id => $info) {
      $entity_type = $this->entityManager->getDefinition($entity_type_id);

      $entity = entity_create($entity_type_id, $info);
      $entity->save();

      // For user entity type we expect to have three entities: anonymous, root
      // user and the new created entity (anonymous - 0, admin - 1, new user - 3).
      $expected_results = $entity_type_id == 'user' ? array(1 => '0', 2 => '1', 3 => '3') : array(1 => '1');
      $results = $this->factory->get($entity_type_id)
        ->execute();
      $this->assertIdentical($results, $expected_results, "Query without isNotDeleted on existing $entity_type_id returned expected result.");

      $results = $this->factory->get($entity_type_id)
        ->isNotDeleted()
        ->execute();
      $this->assertIdentical($results, $expected_results, "Query with isNotDeleted on existing $entity_type_id returned expected result.");

      $results = $this->factory->get($entity_type_id)
        ->isDeleted()
        ->execute();
      $this->assertIdentical($results, array(), "Query with isDeleted on existing $entity_type_id returned expected result.");

      // For user entity type we have three entities: anonymous, root user and
      // the new created user.
      $revision = $entity_type_id == 'user' ? 3 : 1;
      $expected_results = $entity_type_id == 'user' ? array(3 => '3') : array(1 => '1');
      $results = $this->factory->get($entity_type_id)
        ->condition($entity_type->getKey('revision'), $revision)
        ->execute();
      $this->assertIdentical($results, $expected_results, "Revision query on existing $entity_type_id returned expected result.");

      // Now delete the entity.
      $entity->delete();

      // For user entity type we expect to have two entities: anonymous and
      // admin (anonymous - 0, admin - 1). Deleted user's id shouldn't be in the
      // results array.
      $expected_results = $entity_type_id == 'user' ? array(1 => '0', 2 => '1') : array();
      $results = $this->factory->get($entity_type_id)
        ->execute();
      $this->assertIdentical($results, $expected_results, "Query without isNotDeleted on deleted $entity_type_id returned expected result.");

      $results = $this->factory->get($entity_type_id)
        ->isNotDeleted()
        ->execute();
      $this->assertIdentical($results, $expected_results, "Query with isNotDeleted on deleted $entity_type_id returned expected result.");

      // We expect array(4 => '3') for user entity type: 4 is the revision ID
      // and 3 the entity ID. Revision ID is 4 because we had three users
      // before deletion of the user with id 3.
      $expected_results = $entity_type_id == 'user' ? array(4 => '3') : array(2 => '1');
      $results = $this->factory->get($entity_type_id)
        ->isDeleted()
        ->execute();
      $this->assertIdentical($results, $expected_results, "Query with isDeleted on deleted $entity_type_id returned expected result.");

      $results = $this->factory->get($entity_type_id)
        ->condition($entity_type->getKey('revision'), 2)
        ->execute();
      $this->assertIdentical($results, array(2 => '1'), "Revision query on deleted $entity_type_id returned expected result.");
    }
  }

}
