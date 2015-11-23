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
  protected $entityTypes = [
    'entity_test' => [],
    'entity_test_rev' => [],
    'entity_test_mul' => [],
    'entity_test_mulrev' => [],
    'node' => [
      'type' => 'article',
      'title' => 'New article',
    ],
    'taxonomy_term' => [
      'name' => 'A term',
      'vid' => 123,
    ],
    'comment' => [
      'entity_type' => 'node',
      'field_name' => 'comment',
      'subject' => 'How much wood would a woodchuck chuck',
      'mail' => 'someone@example.com',
    ],
    'user' => [
      'name' => 'User',
      'mail' => 'user@example.com',
      'status' => 1,
    ],
  ];

  /**
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $factory;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->factory = \Drupal::service('entity.query');
  }

  public function testQuery() {

    foreach ($this->entityTypes as $entity_type_id => $info) {
      $entity_type = $this->entityManager->getDefinition($entity_type_id);
      $entity = $this->entityManager->getStorage($entity_type_id)->create($info);
      $entity->save();

      // For user entity type we expect to have three entities: anonymous, root
      // user and the new created entity (anonymous - 0, admin - 1, new user - 2).
      $expected_results = $entity_type_id == 'user' ? ['0', '1', '2'] : ['1'];
      $results = $this->factory->get($entity_type_id)
        ->execute();
      $this->assertIdentical(array_values($results), $expected_results, "Query without isNotDeleted on existing $entity_type_id returned expected result.");

      $results = $this->factory->get($entity_type_id)
        ->isNotDeleted()
        ->execute();
      $this->assertIdentical(array_values($results), $expected_results, "Query with isNotDeleted on existing $entity_type_id returned expected result.");

      $results = $this->factory->get($entity_type_id)
        ->isDeleted()
        ->execute();
      $this->assertIdentical($results, [], "Query with isDeleted on existing $entity_type_id returned expected result.");

      // For user entity type we have three entities: anonymous, root user and
      // the new created user.
      $revision = $entity_type_id == 'user' ? 3 : 1;
      $results = $this->factory->get($entity_type_id)
        ->condition($entity_type->getKey('revision'), $revision)
        ->execute();
      $this->assertIdentical(count($results), 1, "Revision query on existing $entity_type_id returned expected result.");

      // Now delete the entity.
      $entity->delete();

      // For user entity type we expect to have two entities: anonymous and
      // admin (anonymous - 0, admin - 1). Deleted user's id shouldn't be in the
      // results array.
      $expected_results = $entity_type_id == 'user' ? ['0', '1'] : [];
      $results = $this->factory->get($entity_type_id)
        ->execute();
      $this->assertIdentical(array_values($results), $expected_results, "Query without isNotDeleted on deleted $entity_type_id returned expected result.");

      $results = $this->factory->get($entity_type_id)
        ->isNotDeleted()
        ->execute();
      $this->assertIdentical(array_values($results), $expected_results, "Query with isNotDeleted on deleted $entity_type_id returned expected result.");

      $expected_results = $entity_type_id == 'user' ? ['2'] : ['1'];
      $results = $this->factory->get($entity_type_id)
        ->isDeleted()
        ->execute();
      $this->assertIdentical(array_values($results), $expected_results, "Query with isDeleted on deleted $entity_type_id returned expected result.");

      $results = $this->factory->get($entity_type_id)
        ->condition($entity_type->getKey('revision'), 2)
        ->execute();
      $this->assertIdentical(count($results), 1, "Revision query on deleted $entity_type_id returned expected result.");
    }
  }

}
