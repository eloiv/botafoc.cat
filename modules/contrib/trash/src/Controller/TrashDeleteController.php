<?php

/**
 * @file
 * Contains \Drupal\trash\Controller\TrashController.
 */

namespace Drupal\trash\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\multiversion\MultiversionManagerInterface;
use Drupal\Core\Url;
use Drupal\Core\Routing\TrustedRedirectResponse;

class TrashDeleteController extends ControllerBase {

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  public function __construct(RouteMatchInterface $route_match) {
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match')
    );
  }

  public function entityDelete() {
    $parameters = $this->routeMatch->getParameters();
    foreach ($parameters as $entity_type_id => $entity_id) {
      $entity = entity_load($entity_type_id, $entity_id);
      if (empty($entity)) {
        drupal_set_message(t('Unable to load entity.'), 'error');
        return $this->redirect('<front>');
      }
      drupal_set_message(t('The @entity %label has been moved to the trash.', ['@entity' => $entity->getEntityType()->get('label'), '%label' => $entity->label()]));
      $entity->delete();
    }
    return $this->redirect($this->getRedirectUrl($entity)->getRouteName(),$this->getRedirectUrl($entity)->getRouteParameters());
  }

  protected function getRedirectUrl($entity) {
    if ($entity->hasLinkTemplate('collection')) {
      // If available, return the collection URL.
      return $entity->urlInfo('collection');
    }
    else {
      // Otherwise fall back to the front page.
      return Url::fromRoute('<front>');
    }
  }
}
