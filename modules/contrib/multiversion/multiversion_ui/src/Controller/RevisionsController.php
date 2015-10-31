<?php

/**
 * @file
 * Contains \Drupal\multiversion_ui\Controller\RevisionsController.
 */

namespace Drupal\multiversion_ui\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Routing\RouteMatchInterface;

class RevisionsController extends ControllerBase {

  /**
   * Prints the revision tree of the current entity.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *    A RouteMatch object.
   *
   * @return array
   *    Array of page elements to render.
   */
  public function revisions(RouteMatchInterface $route_match) {
    $output = array();

    $parameter_name = $route_match->getRouteObject()->getOption('_entity_type_id');
    $entity = $route_match->getParameter($parameter_name);

    if ($entity && $entity instanceof EntityInterface) {
      $tree = \Drupal::service('entity.index.rev.tree')->getTree($entity->uuid());
      $output = array(
        '#theme' => 'item_list',
        '#attributes' => array('class' => array('multiversion')),
        '#attached' => array('library' => array('multiversion_ui/drupal.multiversion_ui.admin')),
        '#items' => $tree,
        '#list_type' => 'ul',
      );
    }
    return $output;
  }

}
