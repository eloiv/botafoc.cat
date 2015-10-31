<?php

/**
 * @file
 * Contains \Drupal\trash\Routing\RouteSubscriber.
 */

namespace Drupal\trash\Routing;

use Drupal\multiversion\MultiversionManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for Multiversion UI routes.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * The multiversion manager service.
   *
   * @var \Drupal\multiversion\MultiversionManagerInterface
   */
  protected $multiversionManager;
  
  /**
   * Constructs a TrashLocalTasks object.
   *
   * @param \Drupal\multiversion\MultiversionManagerInterface $entity_manager
   *   The entity type manager.
   */
  public function __construct(multiversionManagerInterface $multiversion_manager) {
    $this->multiversionManager = $multiversion_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    foreach ($this->multiversionManager->getSupportedEntityTypes() as $entity_type_id => $entity_type) {
      $route = $collection->get("entity.$entity_type_id.delete_form");
      if (!empty($route)) {
        $defaults = $route->getDefaults();
        unset($defaults['_entity_form']);
        $defaults['_controller'] = '\Drupal\trash\Controller\TrashDeleteController::entityDelete';
        $route->setDefaults($defaults);
        //TODO: Get the right permission
        $route->setRequirements(['_permission' => 'access unpublished content']);
      }
    }
  }

}
