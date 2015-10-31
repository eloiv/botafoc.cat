<?php

/**
 * @file
 * Contains \Drupal\multiversion\Workspace\WorkspaceManagerInterface.
 */

namespace Drupal\multiversion\Workspace;

use Drupal\Core\Url;
use Drupal\multiversion\Entity\WorkspaceInterface;

interface WorkspaceManagerInterface {

  /**
   * @param \Drupal\multiversion\Workspace\WorkspaceNegotiatorInterface $negotiator
   * @param int $priority
   */
  public function addNegotiator(WorkspaceNegotiatorInterface $negotiator, $priority);

  /**
   * @param string $workspace_id
   */
  public function load($workspace_id);

  /**
   * @param array|null $workspace_ids
   */
  public function loadMultiple(array $workspace_ids = NULL);

  /**
   * @return \Drupal\multiversion\Entity\WorkspaceInterface
   */
  public function getActiveWorkspace();

  /**
   * @param \Drupal\multiversion\Entity\WorkspaceInterface $workspace
   * @return \Drupal\multiversion\Workspace\WorkspaceManagerInterface
   */
  public function setActiveWorkspace(WorkspaceInterface $workspace);

  /**
   * @param \Drupal\Core\Url $url
   * @return array
   */
  public function getWorkspaceSwitchLinks(Url $url);

}
