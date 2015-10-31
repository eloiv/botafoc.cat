<?php

/**
 * @file
 * Contains \Drupal\multiversion\Entity\Storage\Sql\NodeStorage.
 */

namespace Drupal\multiversion\Entity\Storage\Sql;

use Drupal\multiversion\Entity\Storage\ContentEntityStorageInterface;
use Drupal\multiversion\Entity\Storage\ContentEntityStorageTrait;
use Drupal\node\NodeStorage as CoreNodeStorage;

/**
 * Defines the controller class for nodes.
 *
 * @todo: {@link https://www.drupal.org/node/2597534 Remove this, as it's not
 * needed anymore.}
 */
class NodeStorage extends CoreNodeStorage implements ContentEntityStorageInterface {

  use ContentEntityStorageTrait {
    // @todo Rename to doDelete for consistency with other storage handlers.
    delete as deleteEntities;
  }

  /**
   * {@inheritdoc}
   */
  public function delete(array $entities) {
    // Delete all comments before deleting the nodes.
    $comment_storage = \Drupal::entityManager()->getStorage('comment');
    foreach ($entities as $entity) {
      if ($entity->comment) {
        $comments = $comment_storage->loadThread($entity, 'comment', 1);
        $comment_storage->delete($comments);
      }
    }
    $this->deleteEntities($entities);
  }

}
