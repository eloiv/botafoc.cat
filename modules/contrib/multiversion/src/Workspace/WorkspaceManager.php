<?php

/**
 * @file
 * Contains \Drupal\multiversion\Workspace\WorkspaceManager.
 */

namespace Drupal\multiversion\Workspace;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Url;
use Drupal\multiversion\Entity\WorkspaceInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class WorkspaceManager implements WorkspaceManagerInterface {

  /**
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * @var array
   */
  protected $negotiators = array();

  /**
   * @var array
   */
  protected $sortedNegotiators;

  /**
   * @var \Drupal\multiversion\Entity\WorkspaceInterface
   */
  protected $activeWorkspace;

  /**
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   */
  public function __construct(RequestStack $request_stack, EntityManagerInterface $entity_manager) {
    $this->requestStack = $request_stack;
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function addNegotiator(WorkspaceNegotiatorInterface $negotiator, $priority) {
    $this->negotiators[$priority][] = $negotiator;
    $this->sortedNegotiators = NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function load($workspace_id) {
    return $this->entityManager->getStorage('workspace')->load($workspace_id);
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultiple(array $workspace_ids = NULL) {
    return $this->entityManager->getStorage('workspace')->loadMultiple($workspace_ids);
  }

  /**
   * {@inheritdoc}
   *
   * @todo {@link https://www.drupal.org/node/2600382 Access check.}
   */
  public function getActiveWorkspace() {
    if (!isset($this->activeWorkspace)) {
      $request = $this->requestStack->getCurrentRequest();
      foreach ($this->getSortedNegotiators() as $negotiator) {
        if ($negotiator->applies($request)) {
          if ($workspace_id = $negotiator->getWorkspaceId($request)) {
            /** @var \Drupal\multiversion\Entity\WorkspaceInterface $workspace */
            if ($workspace = $this->entityManager->getStorage('workspace')->load($workspace_id)) {
              $negotiator->persist($workspace);
              $this->activeWorkspace = $workspace;
              break;
            }
          }
        }
      }
    }
    return $this->activeWorkspace;
  }

  /**
   * {@inheritdoc}
   */
  public function setActiveWorkspace(WorkspaceInterface $workspace) {
    $this->activeWorkspace = $workspace;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getWorkspaceSwitchLinks(Url $url) {
    $request = $this->requestStack->getCurrentRequest();
    foreach ($this->getSortedNegotiators() as $negotiator) {
      if ($negotiator instanceof WorkspaceSwitcherInterface && $negotiator->applies($request)) {
        if ($links = $negotiator->getWorkspaceSwitchLinks($request, $url)) {
          return $links;
        }
      }
    }
  }

  /**
   * @return \Drupal\multiversion\Workspace\WorkspaceNegotiatorInterface[]
   */
  protected function getSortedNegotiators() {
    if (!isset($this->sortedNegotiators)) {
      // Sort the negotiators according to priority.
      krsort($this->negotiators);
      // Merge nested negotiators from $this->negotiators into
      // $this->sortedNegotiators.
      $this->sortedNegotiators = array();
      foreach ($this->negotiators as $builders) {
        $this->sortedNegotiators = array_merge($this->sortedNegotiators, $builders);
      }
    }
    return $this->sortedNegotiators;
  }

}
