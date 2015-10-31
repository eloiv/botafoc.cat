<?php

namespace Drupal\multiversion\Entity\Storage;

use Drupal\Core\Entity\EntityStorageInterface;

interface ContentEntityStorageInterface extends EntityStorageInterface {

  /**
   * @param integer $id
   * @return \Drupal\Core\Entity\ContentEntityInterface|null
   */
  public function loadDeleted($id);

  /**
   * @param array $ids
   * @return \Drupal\Core\Entity\ContentEntityInterface[]
   */
  public function loadMultipleDeleted(array $ids = NULL);

  /**
   * @param array $entities
   */
  public function purge(array $entities);
}
