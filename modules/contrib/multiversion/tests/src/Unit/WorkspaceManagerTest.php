<?php

/**
 * @file
 * Contains Drupal\Tests\multiversion\Unit\WorkspaceManagerTest.
 */

namespace Drupal\Tests\multiversion\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Url;
use Drupal\Tests\UnitTestCase;
use Drupal\multiversion\Workspace\WorkspaceManager;
use Symfony\Component\HttpFoundation\Request;

/**
 * @coversDefaultClass \Drupal\multiversion\Workspace\WorkspaceManager
 * @group multiversion
 */
class WorkspaceManagerTest extends UnitTestCase {

  /**
   * The entities under test.
   *
   * @var array
   */
  protected $entities;

  /**
   * The entities values.
   *
   * @var array
   */
  protected $values;

  /**
   * The dependency injection container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $container;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityManager;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $requestStack;

  /**
   * The cache render.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $cacheRender;

  /**
   * The ID of the type of the entity under test.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * The entity type used for testing.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityType;

  /**
   * The workspace negotiators.
   *
   * @var array
   */
  protected $workspaceNegotiators;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->entityTypeId = 'workspace';
    $first_id = $this->randomMachineName();
    $second_id = $this->randomMachineName();
    $this->values = array(
      array(
        'id' => $first_id,
        'label' => $first_id,
        'created' => (int) microtime(TRUE) * 1000000,
      ),
      array(
        'id' => $second_id,
        'label' => $second_id,
        'created' => (int) microtime(TRUE) * 1000000,
      ),
    );

    $this->entityType = $this->getMock('Drupal\multiversion\Entity\WorkspaceInterface');
    $this->entityManager = $this->getMock('Drupal\Core\Entity\EntityManagerInterface');
    $this->cacheRender = $this->getMock('Drupal\Core\Cache\CacheBackendInterface');
    $this->entityManager->expects($this->any())
      ->method('getDefinition')
      ->with($this->entityTypeId)
      ->will($this->returnValue($this->entityType));
    $this->requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');

    $container = new ContainerBuilder();
    $container->set('entity.manager', $this->entityManager);
    $container->set('request_stack', $this->requestStack);
    $container->set('cache.render', $this->cacheRender);
    \Drupal::setContainer($container);

    foreach ($this->values as $value) {
      $this->entities[] = $this->getMock('Drupal\multiversion\Entity\Workspace', array(), array($value, $this->entityTypeId));
    }

    $this->workspaceNegotiators[] = array($this->getMock('Drupal\multiversion\Workspace\DefaultWorkspaceNegotiator'));
    $this->workspaceNegotiators[] = array($this->getMock('Drupal\multiversion\Workspace\SessionWorkspaceNegotiator'));
  }

  /**
   * Tests the addNegotiator() method.
   */
  public function testAddNegotiator() {
    $workspace_manager = new WorkspaceManager($this->requestStack, $this->entityManager, $this->cacheRender);
    $workspace_manager->addNegotiator($this->workspaceNegotiators[0][0], 0);
    $workspace_manager->addNegotiator($this->workspaceNegotiators[1][0], 1);

    $property = new \ReflectionProperty('Drupal\multiversion\Workspace\WorkspaceManager', 'negotiators');
    $property->setAccessible(TRUE);

    $this->assertSame($this->workspaceNegotiators, $property->getValue($workspace_manager));
  }

  /**
   * Tests the load() method.
   */
  public function testLoad() {
    $workspace_id = $this->values[0]['id'];
    $storage = $this->getMock('Drupal\Core\Entity\EntityStorageInterface');
    $storage->expects($this->once())
      ->method('load')
      ->with($workspace_id)
      ->will($this->returnValue($this->entities[0]));

    $this->entityManager->expects($this->once())
      ->method('getStorage')
      ->with($this->entityTypeId)
      ->will($this->returnValue($storage));

    $workspace_manager = new WorkspaceManager($this->requestStack, $this->entityManager, $this->cacheRender);
    $entity = $workspace_manager->load($workspace_id);

    $this->assertSame($this->entities[0], $entity);
  }

  /**
   * Tests the loadMultiple() method.
   */
  public function testLoadMultiple() {
    $ids = array($this->values[0]['id'], $this->values[1]['id']);
    $storage = $this->getMock('Drupal\Core\Entity\EntityStorageInterface');
    $storage->expects($this->once())
      ->method('loadMultiple')
      ->with($ids)
      ->will($this->returnValue($this->entities));

    $this->entityManager->expects($this->once())
      ->method('getStorage')
      ->with($this->entityTypeId)
      ->will($this->returnValue($storage));

    $workspace_manager = new WorkspaceManager($this->requestStack, $this->entityManager, $this->cacheRender);
    $entities = $workspace_manager->loadMultiple($ids);

    $this->assertSame($this->entities, $entities);
  }

  /**
   * Tests the setActiveWorkspace() and getActiveWorkspace() methods.
   */
  public function testSetActiveWorkspace() {
    $workspace_manager = new WorkspaceManager($this->requestStack, $this->entityManager, $this->cacheRender);
    $workspace_manager->setActiveWorkspace($this->entities[0]);
    $this->assertSame($this->entities[0], $workspace_manager->getActiveWorkspace());
  }

  /**
   * Tests the getWorkspaceSwitchLinks() method.
   */
  public function testGetWorkspaceSwitchLinks() {
    $path = '<front>';
    $request = Request::create($path);
    $query = array();
    $url = Url::fromRoute('<front>');
    $expected_links = array(
      $this->values[0]['id'] => array(
        'href' => $url,
        'title' => $this->values[0]['id'],
        'query' => $query,
      ),
    );

    $this->requestStack->expects($this->once())
      ->method('getCurrentRequest')
      ->will($this->returnValue($request));

    $workspace_manager = new WorkspaceManager($this->requestStack, $this->entityManager, $this->cacheRender);
    $workspace_manager->addNegotiator($this->workspaceNegotiators[1][0], 1);

    $this->workspaceNegotiators[1][0]->expects($this->any())
      ->method('applies')
      ->with($request)
      ->will($this->returnValue(TRUE));
    $this->workspaceNegotiators[1][0]->expects($this->once())
      ->method('getWorkspaceSwitchLinks')
      ->with($request, $url)
      ->will($this->returnValue($expected_links));

    $result_links = $workspace_manager->getWorkspaceSwitchLinks($url);
    $this->assertSame($expected_links, $result_links);
  }

  /**
   * Tests the getSortedNegotiators() method.
   */
  public function testGetSortedNegotiators() {
    $workspace_manager = new WorkspaceManager($this->requestStack, $this->entityManager, $this->cacheRender);
    $workspace_manager->addNegotiator($this->workspaceNegotiators[0][0], 1);
    $workspace_manager->addNegotiator($this->workspaceNegotiators[1][0], 3);

    $method = new \ReflectionMethod('Drupal\multiversion\Workspace\WorkspaceManager', 'getSortedNegotiators');
    $method->setAccessible(TRUE);

    $sorted_negotiators = new \ReflectionProperty('Drupal\multiversion\Workspace\WorkspaceManager', 'sortedNegotiators');
    $sorted_negotiators->setAccessible(TRUE);
    $sorted_negotiators_value = $sorted_negotiators->getValue($workspace_manager);

    $negotiators = new \ReflectionProperty('Drupal\multiversion\Workspace\WorkspaceManager', 'negotiators');
    $negotiators->setAccessible(TRUE);
    $negotiators_value = $negotiators->getValue($workspace_manager);

    if (!isset($sorted_negotiators_value)) {
      // Sort the negotiators according to priority.
      krsort($negotiators_value);
      // Merge nested negotiators from $negotiators_value into
      // $sorted_negotiators_value.
      $sorted_negotiators_value = array();
      foreach ($negotiators_value as $builders) {
        $sorted_negotiators_value = array_merge($sorted_negotiators_value, $builders);
      }
    }
    $this->assertSame($sorted_negotiators_value, $method->invoke($workspace_manager));
  }

}
