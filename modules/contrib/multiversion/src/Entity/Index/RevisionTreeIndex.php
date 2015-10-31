<?php

namespace Drupal\multiversion\Entity\Index;

use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\multiversion\Workspace\WorkspaceManagerInterface;

/**
 * @todo: {@link https://www.drupal.org/node/2597444 Consider caching once/if
 * rev and rev tree indices are merged.}
 */
class RevisionTreeIndex implements RevisionTreeIndexInterface {

  /**
   * @var \Drupal\Core\KeyValueStore\KeyValueFactoryInterface
   */
  protected $keyValueFactory;

  /**
   * @var \Drupal\multiversion\Workspace\WorkspaceManagerInterface
   */
  protected $workspaceManager;

  /**
   * @var \Drupal\multiversion\Entity\Index\RevisionIndexInterface
   */
  protected $revIndex;

  /**
   * @var string
   */
  protected $workspaceId;

  /**
   * @var array
   */
  protected $cache = array();

  /**
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $key_value_factory
   * @param \Drupal\multiversion\Workspace\WorkspaceManagerInterface $workspace_manager
   * @param \Drupal\multiversion\Entity\Index\RevisionIndexInterface $rev_index
   */
  function __construct(KeyValueFactoryInterface $key_value_factory, WorkspaceManagerInterface $workspace_manager, RevisionIndexInterface $rev_index) {
    $this->keyValueFactory = $key_value_factory;
    $this->workspaceManager = $workspace_manager;
    $this->revIndex = $rev_index;
  }

  /**
   * {@inheritdoc}
   */
  public function useWorkspace($id) {
    $this->workspaceId = $id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTree($uuid) {
    $values = $this->buildTree($uuid);
    return $values['tree'];
  }

  /**
   * {@inheritdoc}
   */
  public function updateTree($uuid, array $branch = array()) {
    $this->keyValueStore($uuid)->setMultiple($branch);
  }

  /**
   * {@inheritdoc}
   *
   * @todo: {@link https://www.drupal.org/node/2597422 The revision tree also
   * contain missing revisions. We need a better way to count.}
   */
  public function countRevs($uuid) {
    $values = $this->buildTree($uuid);
    return count($values['default_branch']);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultRevision($uuid) {
    $values = $this->buildTree($uuid);
    return $values['default_rev'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultBranch($uuid) {
    $values = $this->buildTree($uuid);
    return $values['default_branch'];
  }

  /**
   * {@inheritdoc}
   */
  public function getOpenRevisions($uuid) {
    $values = $this->buildTree($uuid);
    return $values['open_revs'];
  }

  /**
   * {@inheritdoc}
   */
  public function getConflicts($uuid) {
    $values = $this->buildTree($uuid);
    return $values['conflicts'];
  }

  /**
   * @param string $uuid
   * @return \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected function keyValueStore($uuid) {
    $workspace_id = $this->workspaceId ?: $this->workspaceManager->getActiveWorkspace()->id();
    return $this->keyValueFactory->get("entity.index.rev.tree.$workspace_id.$uuid");
  }

  /**
   * Helper method to build the revision tree.
   */
  protected function buildTree($uuid) {
    $revs = $this->keyValueStore($uuid)->getAll();
    $revs_info = $this->revIndex->getMultiple(array_keys($revs));
    return self::doBuildTree($revs, $revs_info);
  }

  /**
   * Recursive helper method to build the revision tree.
   *
   * @return array
   *   Returns an array containing the built tree, open revisions, default
   *   revision, default branch and conflicts.
   *
   * @todo: {@link https://www.drupal.org/node/2597430 Implement
   * 'deleted_conflicts'.}
   */
  protected static function doBuildTree($revs, $revs_info, $parse = 0, &$tree = array(), &$open_revs = array(), &$conflicts = array()) {
    foreach ($revs as $rev => $parent_rev) {
      if ($rev == 0) {
        continue;
      }

      if ($parent_rev == $parse) {

        // Avoid bad data to cause endless loops.
        // @todo: {@link https://www.drupal.org/node/2597434 Needs test.}
        if ($rev == $parse) {
          throw new \InvalidArgumentException('Child and parent revision can not be the same value.');
        }

        // Build an element structure compatible with Drupal's Render API.
        $i = count($tree);
        $tree[$i] = array(
          '#type' => 'rev',
          '#rev' => $rev,
          '#rev_info' => array(
            'status' => isset($revs_info[$rev]['status']) ? $revs_info[$rev]['status'] : 'missing',
            'default' => FALSE,
            'open_rev' => FALSE,
            'conflict' => FALSE,
          ),
          'children' => array(),
        );

        // Recurse down through the children.
        self::doBuildTree($revs, $revs_info, $rev, $tree[$i]['children'], $open_revs, $conflicts);

        // Sort all tree elements from low to high.
        usort($tree, function($a, $b) {
          return ($a['#rev'] < $b['#rev']) ? -1 : 1;
        });

        if (empty($tree[$i]['children'])) {
          $tree[$i]['#rev_info']['open_rev'] = TRUE;
          $open_revs[$rev] = $revs_info[$rev]['status'];
          // All open revisions, except deleted and default revisions, are
          // conflicts by definition. We will revert the conflict flag when we
          // find the default revision later on.
          if ($tree[$i]['#rev_info']['status'] != 'deleted') {
            $tree[$i]['#rev_info']['conflict'] = TRUE;
            $conflicts[$rev] = $revs_info[$rev]['status'];
          }
        }
      }
    }

    // Now when the full tree is built we'll find the default revision and
    // its branch.
    if ($parse == 0) {
      $default_rev = 0;
      // Sort from high to low and find the default revision.
      krsort($open_revs);
      foreach ($open_revs as $open_rev => $status) {
        if ($status != 'missing') {
          $default_rev = $open_rev;
          break;
        }
      }
      // Update the default revision in the tree array and remove it from the
      // conflicts array.
      unset($conflicts[$default_rev]);
      self::updateDefaultRevision($tree, $default_rev);

      // Find the branch of the default revision.
      $default_branch = array();
      $rev = $default_rev;
      while ($rev != 0) {
        if (isset($revs_info[$rev])) {
          $default_branch[$rev] = $revs_info[$rev]['status'];
        }
        $rev = $revs[$rev];
      }
      return array(
        'tree' => $tree,
        'default_rev' => $default_rev,
        'default_branch' => array_reverse($default_branch),
        'open_revs' => $open_revs,
        'conflicts' => $conflicts,
      );
    }
  }

  /**
   * Helper method to update the default revision.
   */
  protected static function updateDefaultRevision(&$tree, $default_rev) {
    // @todo: {@link https://www.drupal.org/node/2597442 We can temporarily
    // flip the sort to find the default rev earlier.}
    foreach ($tree as &$element) {
      if (isset($element['#rev']) && $element['#rev'] == $default_rev) {
        $element['#rev_info']['default'] = TRUE;
        $element['#rev_info']['conflict'] = FALSE;
        break;
      }
      if (!empty($element['children'])) {
        self::updateDefaultRevision($element['children'], $default_rev);
      }
    }
  }

}
