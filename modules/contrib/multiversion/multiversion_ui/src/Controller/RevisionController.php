<?php

/**
 * @file
 * Contains \Drupal\multiversion_ui\Controller\RevisionController.
 */

namespace Drupal\multiversion_ui\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RouteMatchInterface;

class RevisionController extends ControllerBase {

  /**
   * Title callback for view routes.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *    A RouteMatch object.
   *
   * @return array
   *    Array of page elements to render.
   */
  public function viewTitle(RouteMatchInterface $route_match) {
    $output = array();
    return $output;
  }

  /**
   * Renders an entity revision.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *    A RouteMatch object.
   *
   * @return array
   *    Array of page elements to render.
   */
  public function view(RouteMatchInterface $route_match) {
    $output = array('#markup' => 'Foo');
    return $output;
  }

}
