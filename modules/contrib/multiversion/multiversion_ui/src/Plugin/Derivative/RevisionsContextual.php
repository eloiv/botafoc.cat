<?php

/**
 * @file
 * Contains \Drupal\multiversion_ui\Plugin\Derivative\RevisionsContextual.
 */

namespace Drupal\multiversion_ui\Plugin\Derivative;

use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;

/**
 * Provides contextual link definitions for all entity bundles.
 */
class RevisionsContextual extends RevisionsLocalTask implements ContainerDeriverInterface {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = array();

    foreach ($this->entityManager->getDefinitions() as $entity_type_id => $entity_type) {
      if ($entity_type->hasLinkTemplate('version-history')) {
        $this->derivatives[$entity_type_id] = array(
          'route_name' => "entity.$entity_type_id.version_history",
          'title' => $this->t('Revisions'),
          'group' => $entity_type_id,
          'weight' => 20,
        );
      }
    }

    foreach ($this->derivatives as &$derivative) {
      $derivative += $base_plugin_definition;
    }

    return $this->derivatives;
  }

}
