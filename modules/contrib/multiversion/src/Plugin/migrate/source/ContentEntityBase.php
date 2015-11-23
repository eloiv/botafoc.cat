<?php

/**
 * @file
 * Contains \Drupal\multiversion\Plugin\Migrate\source\ContentEntityBase.
 */

namespace Drupal\multiversion\Plugin\migrate\source;

/**
 * Migration source class for content entities.
 *
 * @MigrateSource(
 *   id = "multiversion"
 * )
 */
class ContentEntityBase extends SourcePluginBase {

  /**
   * {@inheritdoc}
   */
  protected function initializeIterator() {
    // At this point Multiversion is obviously installed and the new storage
    // handler is already active. But since the new schema isn't applied yet
    // and the new handler doesn't know how to load from the old schema, we have
    // to initialize the previously installed storage handler and use that to
    // load the entities.
    $last_definition = $this->entityManager->getLastInstalledDefinition($this->entityTypeId);
    $last_storage = $this->entityManager->createHandlerInstance($last_definition->getStorageClass(), $last_definition);
    $entities = $last_storage->loadMultiple();

    $result = array();
    foreach ($entities as $entity_id => $entity) {
      foreach ($entity as $field_name => $field) {
        $value = NULL;
        /** @var \Drupal\Core\Field\FieldItemListInterface $field */
        /** @var \Drupal\Core\Field\FieldItemInterface $item */
        if ($field->count() == 1) {
          $item = $field->first();
          $value = $item->get($item->mainPropertyName())->getValue();
        }
        elseif ($field->count() > 1) {
          $value = array();
          foreach ($field as $item) {
            $value[] = $item->get($item->mainPropertyName())->getValue();
          }
        }
        $result[$entity_id][$field_name] = $value;
      }
    }

    return new \ArrayIterator(array_values($result));
  }

}
