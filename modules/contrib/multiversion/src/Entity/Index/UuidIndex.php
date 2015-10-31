<?php

namespace Drupal\multiversion\Entity\Index;

use Drupal\Core\Entity\EntityInterface;

class UuidIndex extends EntityIndex implements UuidIndexInterface {

  /**
   * @var string
   */
  protected $collectionPrefix = 'entity.index.uuid.';

  /**
   * {@inheritdoc}
   */
  protected function buildKey(EntityInterface $entity) {
    return $entity->uuid();
  }

}
