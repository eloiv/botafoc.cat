<?php

/**
 * @file
 * Contains \Drupal\multiversion\Tests\FieldTestBase.
 */

namespace Drupal\multiversion\Tests;

abstract class FieldTestBase extends MultiversionWebTestBase {

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
    'node' =>[
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
    'block_content' => [
      'info' => 'New block',
      'type' => 'basic',
    ],
    'menu_link_content' => [
      'menu_name' => 'menu_test',
      'bundle' => 'menu_link_content',
      'link' => [['uri' => 'user-path:/']],
    ],
    'user' => [
      'name' => 'User',
      'mail' => 'user@example.com',
      'status' => 1,
    ],
  ];

  /**
   * @var string
   */
  protected $fieldName;

  /**
   * @var mixed
   */
  protected $defaultValue;

  /**
   * @var string
   */
  protected $itemListClass = '\Drupal\Core\Field\FieldItemList';

  /**
   * @var bool
   */
  protected $createdEmpty = TRUE;

  /**
   * @var string
   */
  protected $itemClass;

  public function testFieldBasics() {
    foreach ($this->entityTypes as $entity_type_id => $info) {
      $storage = $this->entityManager->getStorage($entity_type_id);
      $entity = $storage->create($info);
      $this->assertTrue(is_a($entity->{$this->fieldName}, $this->itemListClass), "Field item list implements correct interface on created $entity_type_id.");
      $count = $entity->{$this->fieldName}->count();
      $this->assertTrue($this->createdEmpty ? empty($count) : !empty($count), "Field is created with no field items for $entity_type_id.");

      $entity->save();
      $entity_id = $entity->id();
      $entity =  $storage->load($entity_id);

      $this->assertFalse($entity->{$this->fieldName}->isEmpty(), "Field was attached on loaded $entity_type_id.");

      $storage->loadMultiple([$entity_id]);
      $entity = $storage->loadDeleted($entity_id);

      $this->assertFalse($entity->{$this->fieldName}->isEmpty(), "Field was attached on deleted $entity_type_id.");
    }
  }

}
