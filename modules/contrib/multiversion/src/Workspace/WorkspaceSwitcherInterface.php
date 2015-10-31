<?php

/**
 * @file
 * Contains \Drupal\multiversion\Workspace\WorkspaceSwitcherInterface.
 */

namespace Drupal\multiversion\Workspace;

use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;

interface WorkspaceSwitcherInterface {

  /**
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @param \Drupal\Core\Url $url
   * @return array
   */
  public function getWorkspaceSwitchLinks(Request $request, Url $url);

}
