<?php

namespace Drupal\multiversion\Entity\Index;

use Drupal\Core\Entity\EntityInterface;

class RevisionIndex extends EntityIndex implements RevisionIndexInterface {

  /**
   * @var string
   */
  protected $collectionPrefix = 'entity.index.rev.';

  /**
   * {@inheritdoc}
   */
  protected function buildKey(EntityInterface $entity) {
    return $entity->_rev->value;
  }

}
