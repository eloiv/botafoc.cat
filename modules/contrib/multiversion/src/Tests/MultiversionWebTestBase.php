<?php

/**
 * @file
 * Contains \Drupal\multiversion\Tests\MultiversionWebTestBase.
 */

namespace Drupal\multiversion\Tests;

use Drupal\comment\Tests\CommentTestTrait;
use Drupal\Component\Utility\Unicode;
use Drupal\simpletest\WebTestBase;

/**
 * @todo: {@link https://www.drupal.org/node/2597492 Change to extending
 * DrupalUnitTestBase to increase performance.}
 */
abstract class MultiversionWebTestBase extends WebTestBase {

  use CommentTestTrait;

  protected $strictConfigSchema = FALSE;

  /**
   * @var \Drupal\multiversion\Entity\Index\UuidIndexInterface;
   */
  protected $uuidIndex;

  /**
   * @var \Drupal\multiversion\Entity\Index\RevisionIndexInterface;
   */
  protected $revIndex;

  /**
   * @var \Drupal\multiversion\Entity\Index\RevisionTreeIndexInterface;
   */
  protected $revTree;

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The multiversion manager.
   *
   * @var \Drupal\multiversion\MultiversionManagerInterface
   */
  protected $multiversionManager;

  /**
   * The workspace manager.
   *
   * @var \Drupal\multiversion\Workspace\WorkspaceManagerInterface
   */
  protected $workspaceManager;

  /**
   * The entity definition update manager.
   *
   * @var \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface
   */
  protected $entityDefinitionUpdateManager;

  public static $modules = array(
    'entity_test',
    'multiversion',
    'node',
    'taxonomy',
    'comment',
    'block_content',
    'menu_link_content',
    'file',
  );

  protected function setUp() {
    parent::setUp();

    $this->uuidIndex = $this->container->get('entity.index.uuid');
    $this->revIndex = $this->container->get('entity.index.rev');
    $this->revTree = $this->container->get('entity.index.rev.tree');

    $this->multiversionManager = $this->container->get('multiversion.manager');
    $this->workspaceManager = $this->container->get('workspace.manager');
    $this->entityManager = $this->container->get('entity.manager');
    $this->entityDefinitionUpdateManager = $this->container->get('entity.definition_update_manager');

    // Create Basic page and Article node types.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType(array('type' => 'article', 'name' => 'Article'));
    }
    // Create comment field on article.
    $this->addDefaultCommentField('node', 'article');
  }

  /**
   * Returns a new vocabulary with random properties.
   */
  function createVocabulary() {
    // Create a vocabulary.
    $vocabulary = entity_create('taxonomy_vocabulary', array(
      'name' => $this->randomMachineName(),
      'vid' => Unicode::strtolower($this->randomMachineName()),
    ));
    $vocabulary->save();
    return $vocabulary;
  }

  /**
   * Returns a new menu with random properties.
   */
  function createMenu() {
    // Create a menu.
    $menu = entity_create('menu', array(
      'id' => 'menu_test',
      'label' => 'Test menu',
      'description' => 'Description text',
    ));
    $menu->save();
    return $menu;
  }

}
