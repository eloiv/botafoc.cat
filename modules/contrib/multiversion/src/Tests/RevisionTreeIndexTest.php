<?php

/**
 * @file
 * Contains \Drupal\multiversion\Tests\RevisionTreeIndexTest.
 */

namespace Drupal\multiversion\Tests;

/**
 * Test the methods on the RevisionTreeIndex class.
 *
 * @group multiversion
 *
 * @todo: {@link https://www.drupal.org/node/2597486 Test more entity types,
 * like in \Drupal\multiversion\Tests\EntityStorageTest.}
 */
class RevisionTreeIndexTest extends MultiversionWebTestBase {

  public static $modules = array('entity_test', 'multiversion');

  /**
   * @var \Drupal\multiversion\Workspace\WorkspaceManagerInterface
   */
  protected $workspaceManager;

  /**
   * @var \Drupal\multiversion\Entity\Index\RevisionTreeIndex
   */
  protected $tree;

  protected function setUp() {
    parent::setUp();

    $this->tree = $this->container->get('entity.index.rev.tree');
  }

  public function testWithoutDelete() {
    $entity = entity_create('entity_test');
    $uuid = $entity->uuid();

    // Create a conflict scenario to fully test the parsing.

    // Initial revision.
    $entity->save();
    $revs[] = $entity->_rev->value;

    $entity->save();
    $revs[] = $entity->_rev->value;

    $entity->save();
    $revs[] = $leaf_one = $entity->_rev->value;

    $entity = entity_load('entity_test', 1);
    $this->assertEqual($entity->getRevisionId(), 3, 'Default revision has been set correctly.');

    // Create a new branch from the second revision.
    $entity = entity_revision_load('entity_test', 2);
    $entity->save();
    $revs[] = $leaf_two = $entity->_rev->value;

    // We now have two leafs at the tip of the tree.
    $leafs = array($leaf_one, $leaf_two);
    sort($leafs);
    $expected_leaf = array_pop($leafs);
    $entity = entity_load('entity_test', 1);
    $this->assertEqual($entity->_rev->value, $expected_leaf, 'The correct revision won while having two open revisions.');

      // Continue the last branch.
    $entity = entity_revision_load('entity_test', 4);
    $entity->save();
    $revs[] = $entity->_rev->value;

    $entity = entity_load('entity_test', 1);
    $this->assertEqual($entity->getRevisionId(), 5, 'Default revision has been set correctly.');

    // Create a new branch based on the first revision.
    $entity = entity_revision_load('entity_test', 1);
    $entity->save();
    $revs[] = $entity->_rev->value;

    $entity = entity_load('entity_test', 1);
    $this->assertEqual($entity->getRevisionId(), 5, 'Default revision has been set correctly.');

    $expected_tree = array(
      array(
        '#type' => 'rev',
        '#rev' => $revs[0],
        '#rev_info' => array(
          'status' => 'available',
          'default' => FALSE,
          'open_rev' => FALSE,
          'conflict' => FALSE,
        ),
        'children' => array(
          array(
            '#type' => 'rev',
            '#rev' => $revs[1],
            '#rev_info' => array(
              'status' => 'available',
              'default' => FALSE,
              'open_rev' => FALSE,
              'conflict' => FALSE,
            ),
            'children' => array(
              array(
                '#type' => 'rev',
                '#rev' => $revs[2],
                '#rev_info' => array(
                  'status' => 'available',
                  'default' => FALSE,
                  'open_rev' => TRUE,
                  'conflict' => TRUE,
                ),
                'children' => array(),
              ),
              array(
                '#type' => 'rev',
                '#rev' => $revs[3],
                '#rev_info' => array(
                  'status' => 'available',
                  'default' => FALSE,
                  'open_rev' => FALSE,
                  'conflict' => FALSE,
                ),
                'children' => array(
                  array(
                    '#type' => 'rev',
                    '#rev' => $revs[4],
                    '#rev_info' => array(
                      'status' => 'available',
                      'default' => TRUE,
                      'open_rev' => TRUE,
                      'conflict' => FALSE,
                    ),
                    'children' => array(),
                  )
                )
              )
            )
          ),
          array(
            '#type' => 'rev',
            '#rev' => $revs[5],
            '#rev_info' => array(
              'status' => 'available',
              'default' => FALSE,
              'open_rev' => TRUE,
              'conflict' => TRUE,
            ),
            'children' => array(),
          )
        )
      )
    );
    // Sort the expected tree according to the algorithm.
    self::sortRevisionTree($expected_tree);

    $tree = $this->tree->getTree($uuid);
    $this->assertEqual($tree, $expected_tree, 'Tree was correctly parsed.');

    $default_rev = $this->tree->getDefaultRevision($uuid);
    $this->assertEqual($default_rev, $revs[4], 'Default revision is correct.');

    $expected_default_branch = array(
      $revs[0] => 'available',
      $revs[1] => 'available',
      $revs[3] => 'available',
      $revs[4] => 'available',
    );
    $default_branch = $this->tree->getDefaultBranch($uuid);
    $this->assertEqual($default_branch, $expected_default_branch, 'Default branch is correct.');

    $count = $this->tree->countRevs($uuid);
    $this->assertEqual($count, 4, 'Number of revisions is correct.');

    $expected_open_revision = array(
      $revs[2] => 'available',
      $revs[4] => 'available',
      $revs[5] => 'available',
    );
    $open_revisions = $this->tree->getOpenRevisions($uuid);
    $this->assertEqual($open_revisions, $expected_open_revision, 'Open revisions are correct.');

    $expected_conflicts = array(
      $revs[2] => 'available',
      $revs[5] => 'available',
    );
    $conflicts = $this->tree->getConflicts($uuid);
    $this->assertEqual($conflicts, $expected_conflicts, 'Conflicts are correct');
  }

  public function testWithDelete() {
    $entity = entity_create('entity_test');
    $uuid = $entity->uuid();

    // Create a conflict scenario to fully test the parsing.

    // Initial revision.
    $entity->save();
    $revs[] = $entity->_rev->value;

    $entity->delete();
    $revs[] = $entity->_rev->value;

    $default_branch = $this->revTree->getDefaultBranch($uuid);
    $expected_default_branch = array(
      $revs[0] => 'available',
      $revs[1] => 'deleted',
    );
    $this->assertEqual($default_branch, $expected_default_branch, 'Default branch is corrected when default revision is deleted.');

    $entity->_deleted->value = FALSE;
    $entity->save();
    $revs[] = $leaf_one = $entity->_rev->value;

    $default_branch = $this->revTree->getDefaultBranch($uuid);
    $expected_default_branch = array(
      $revs[0] => 'available',
      $revs[1] => 'deleted',
      $revs[2] => 'available',
    );
    $this->assertEqual($default_branch, $expected_default_branch, 'Default branch is corrected when un-deleting the previous default revision which was deleted.');

    $entity = entity_load('entity_test', 1);
    $this->assertEqual($entity->getRevisionId(), 3, 'Default revision has been set correctly.');

    // Create a new branch from the second revision.
    $entity = entity_revision_load('entity_test', 2);
    $entity->delete();
    $revs[] = $leaf_two = $entity->_rev->value;

    // We now have two leafs at the tip of the tree.
    $leafs = array($leaf_one, $leaf_two);
    sort($leafs);
    $expected_leaf = array_pop($leafs);
    // In this test we actually don't know which revision that became default.
    $entity = entity_load('entity_test', 1) ?: entity_load_deleted('entity_test', 1);
    $this->assertEqual($entity->_rev->value, $expected_leaf, 'The correct revision won while having two open revisions.');

    // Continue the last branch.
    $entity = entity_revision_load('entity_test', 4);
    $entity->save();
    $revs[] = $entity->_rev->value;

    $entity = entity_load_deleted('entity_test', 1);
    $this->assertEqual($entity->getRevisionId(), 5, 'Default revision has been set correctly.');

    // Create a new branch based on the first revision.
    $entity = entity_revision_load('entity_test', 1);
    $entity->save();
    $revs[] = $entity->_rev->value;

    $entity = entity_load_deleted('entity_test', 1);
    $this->assertEqual($entity->getRevisionId(), 5, 'Default revision has been set correctly.');

    $expected_tree = array(
      array(
        '#type' => 'rev',
        '#rev' => $revs[0],
        '#rev_info' => array(
          'status' => 'available',
          'default' => FALSE,
          'open_rev' => FALSE,
          'conflict' => FALSE,
        ),
        'children' => array(
          array(
            '#type' => 'rev',
            '#rev' => $revs[1],
            '#rev_info' => array(
              'status' => 'deleted',
              'default' => FALSE,
              'open_rev' => FALSE,
              'conflict' => FALSE,
            ),
            'children' => array(
              array(
                '#type' => 'rev',
                '#rev' => $revs[2],
                '#rev_info' => array(
                  'status' => 'available',
                  'default' => FALSE,
                  'open_rev' => TRUE,
                  'conflict' => TRUE,
                ),
                'children' => array(),
              ),
              array(
                '#type' => 'rev',
                '#rev' => $revs[3],
                '#rev_info' => array(
                  'status' => 'deleted',
                  'default' => FALSE,
                  'open_rev' => FALSE,
                  'conflict' => FALSE,
                ),
                'children' => array(
                  array(
                    '#type' => 'rev',
                    '#rev' => $revs[4],
                    '#rev_info' => array(
                      'status' => 'deleted',
                      'default' => TRUE,
                      'open_rev' => TRUE,
                      'conflict' => FALSE,
                    ),
                    'children' => array(),
                  )
                )
              )
            )
          ),
          array(
            '#type' => 'rev',
            '#rev' => $revs[5],
            '#rev_info' => array(
              'status' => 'available',
              'default' => FALSE,
              'open_rev' => TRUE,
              'conflict' => TRUE,
            ),
            'children' => array(),
          )
        )
      )
    );
    // Sort the expected tree according to the algorithm.
    self::sortRevisionTree($expected_tree);

    $tree = $this->tree->getTree($uuid);
    $this->assertEqual($tree, $expected_tree, 'Tree was correctly parsed.');

    $default_rev = $this->tree->getDefaultRevision($uuid);
    $this->assertEqual($default_rev, $revs[4], 'Default revision is correct.');

    $expected_default_branch = array(
      $revs[0] => 'available',
      $revs[1] => 'deleted',
      $revs[3] => 'deleted',
      $revs[4] => 'deleted',
    );
    $default_branch = $this->tree->getDefaultBranch($uuid);
    $this->assertEqual($default_branch, $expected_default_branch, 'Default branch is correct.');

    $count = $this->tree->countRevs($uuid);
    $this->assertEqual($count, 4, 'Number of revisions is correct.');

    $expected_open_revision = array(
      $revs[2] => 'available',
      $revs[4] => 'deleted',
      $revs[5] => 'available',
    );
    $open_revisions = $this->tree->getOpenRevisions($uuid);
    $this->assertEqual($open_revisions, $expected_open_revision, 'Open revisions are correct.');

    $expected_conflicts = array(
      $revs[2] => 'available',
      $revs[5] => 'available',
    );
    $conflicts = $this->tree->getConflicts($uuid);
    $this->assertEqual($conflicts, $expected_conflicts, 'Conflicts are correct');
  }

  /**
   * Helper method that sorts a tree according to the revision tree algorithm.
   */
  protected static function sortRevisionTree(&$tree) {
    usort($tree, function($a, $b) {
      return ($a['#rev'] < $b['#rev']) ? -1 : 1;
    });
    foreach ($tree as &$element) {
      if (!empty($element['children'])) {
        self::sortRevisionTree($element['children']);
      }
    }
  }

}
