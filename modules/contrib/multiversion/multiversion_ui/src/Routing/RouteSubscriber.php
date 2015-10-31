<?php

/**
 * @file
 * Contains \Drupal\multiversion_ui\Routing\RouteSubscriber.
 */

namespace Drupal\multiversion_ui\Routing;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for Multiversion UI routes.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a new RouteSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity type manager.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    foreach ($this->entityManager->getDefinitions() as $entity_type_id => $entity_type) {

      if ($entity_type->hasLinkTemplate('version-history')) {

        $options = array(
          '_admin_route' => TRUE,
          '_entity_type_id' => $entity_type_id,
          'parameters' => array(
            $entity_type_id => array(
              'type' => 'entity:' . $entity_type_id,
            ),
            $entity_type_id . '_revision' => array(
              'type' => 'entity_revision:' . $entity_type_id,
            )
          ),
        );

        if ($link_template = $entity_type->getLinkTemplate('version-history')) {
          $route = new Route(
            $link_template,
            array(
              '_controller' => '\Drupal\multiversion_ui\Controller\RevisionsController::revisions',
              '_title' => 'Revisions',
            ),
            // @todo: {@link https://www.drupal.org/node/2596783 Provide more
            // granular permissions.}
            array('_permission' => 'administer multiversion revisions'),
            $options
          );

          // This will create new routes (and override the version_history
          // route for entity types that already has one).
          $collection->add("entity.$entity_type_id.version_history", $route);
        }

        if ($link_template = $entity_type->getLinkTemplate('revision')) {
          $route = new Route(
            $link_template,
            array(
              '_controller' => '\Drupal\multiversion_ui\Controller\RevisionController::view',
              '_title_callback' => '\Drupal\multiversion_ui\Controller\RevisionController::viewTitle',
            ),
            // @todo: {@link https://www.drupal.org/node/2596783 Provide more
            // granular permissions.}
            array('_permission' => 'administer multiversion revisions'),
            $options
          );

          // This will create new routes (and override the revision
          // route for entity types that already has one).
          $collection->add("entity.$entity_type_id.revision", $route);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = parent::getSubscribedEvents();
    $events[RoutingEvents::ALTER] = array('onAlterRoutes', 100);
    return $events;
  }

}
