<?php

namespace Drupal\multiversion\Entity\Index;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\key_value\KeyValueStore\KeyValueSortedSetFactoryInterface;
use Drupal\multiversion\MultiversionManagerInterface;
use Drupal\multiversion\Workspace\WorkspaceManagerInterface;

class SequenceIndex implements SequenceIndexInterface {

  /**
   * @var string
   */
  protected $collectionPrefix = 'entity.index.sequence.';

  /**
   * @var string
   */
  protected $workspaceId;

  /**
   * @var \Drupal\key_value\KeyValueStore\KeyValueSortedSetFactoryInterface
   */
  protected $sortedSetFactory;

  /**
   * @var \Drupal\multiversion\Workspace\WorkspaceManagerInterface
   */
  protected $workspaceManager;

  /**
   * @var \Drupal\multiversion\MultiversionManagerInterface
   */
  protected $multiversionManager;

  /**
   * @param \Drupal\key_value\KeyValueStore\KeyValueSortedSetFactoryInterface $sorted_set_factory
   * @param \Drupal\multiversion\Workspace\WorkspaceManagerInterface $workspace_manager
   * @param \Drupal\multiversion\MultiversionManagerInterface $multiversion_manager
   */
  public function __construct(KeyValueSortedSetFactoryInterface $sorted_set_factory, WorkspaceManagerInterface $workspace_manager, MultiversionManagerInterface $multiversion_manager) {
    $this->sortedSetFactory = $sorted_set_factory;
    $this->workspaceManager = $workspace_manager;
    $this->multiversionManager = $multiversion_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function useWorkspace($id) {
    $this->workspaceId = $id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function add(ContentEntityInterface $entity) {
    $record = $this->buildRecord($entity);
    $this->sortedSetStore()->add($record['seq'], $record);
  }

  /**
   * {@inheritdoc}
   */
  public function getRange($start, $stop = NULL) {
    return $this->sortedSetStore()->getRange($start, $stop);
  }

  /**
   * {@inheritdoc}
   */
  public function getLastSequenceId() {
    return $this->sortedSetStore()->getMaxScore();
  }

  /**
   * @return \Drupal\key_value\KeyValueStore\KeyValueStoreSortedSetInterface
   */
  protected function sortedSetStore() {
    $workspace_id = $this->workspaceId ?: $this->workspaceManager->getActiveWorkspace()->id();
    return $this->sortedSetFactory->get($this->collectionPrefix . $workspace_id);
  }

  /**
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   * @return array
   */
  protected function buildRecord(ContentEntityInterface $entity) {
    return array(
      'entity_type_id' => $entity->getEntityTypeId(),
      'entity_id' => $entity->id(),
      'entity_uuid' => $entity->uuid(),
      'revision_id' => $entity->getRevisionId(),
      'deleted' => $entity->_deleted->value,
      'rev' => $entity->_rev->value,
      'seq' => $this->multiversionManager->newSequenceId(),
      'local' => (boolean) $entity->getEntityType()->get('local'),
    );
  }

}
