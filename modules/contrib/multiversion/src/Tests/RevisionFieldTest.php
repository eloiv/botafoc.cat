<?php

/**
 * @file
 * Contains \Drupal\multiversion\Tests\RevisionFieldTest.
 */

namespace Drupal\multiversion\Tests;

use Drupal\multiversion\Plugin\Field\FieldType\RevisionItem;

/**
 * Test the creation and operation of the Revision field.
 *
 * @group multiversion
 */
class RevisionFieldTest extends FieldTestBase {

  /**
   * {@inheritdoc}
   */
  protected $fieldName = '_rev';

  /**
   * {@inheritdoc}
   */
  protected $createdEmpty = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $itemClass = '\Drupal\multiversion\Plugin\Field\FieldType\RevisionItem';

  public function testFieldOperations() {
    foreach ($this->entityTypes as $entity_type_id => $info) {
      $storage = $this->entityManager->getStorage($entity_type_id);
      $entity = $storage->create($info);

      // Test normal save operations.

      $this->assertTrue($entity->_rev->new_edit, 'New edit flag is TRUE after creation.');

      $revisions = $entity->_rev->revisions;
      $this->assertTrue((is_array($revisions) && empty($revisions)), 'Revisions property is empty after creation.');
      $this->assertTrue((strpos($entity->_rev->value, '0') === 0), 'Revision index was 0 after creation.');

      $entity->save();
      $first_rev = $entity->_rev->value;
      $this->assertTrue((strpos($first_rev, '1') === 0), 'Revision index was 1 after first save.');

      // Simulate the input from a replication.

      if ($entity_type_id == 'block_content') {
        $info['info'] = $this->randomMachineName();
      }
      if ($entity_type_id == 'user') {
        $info['name'] = $this->randomMachineName();
      }
      $entity = $storage->create($info);
      $sample_rev = RevisionItem::generateSampleValue($entity->_rev->getFieldDefinition());

      $entity->_rev->value = $sample_rev['value'];
      $entity->_rev->new_edit = FALSE;
      $entity->_rev->revisions = [$sample_rev['value']];
      $entity->save();
      // Assert that the revision token did not change.
      $this->assertEqual($entity->_rev->value, $sample_rev['value']);
    }
  }

}
