<?php

namespace Drupal\multiversion\Entity\Index;

use Drupal\Core\Entity\ContentEntityInterface;

interface SequenceIndexInterface {

  /**
   * @param $id
   * @return \Drupal\multiversion\Entity\Index\SequenceIndex
   */
  public function useWorkspace($id);

  /**
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   */
  public function add(ContentEntityInterface $entity);

  /**
   * @param float $start
   * @param float $stop
   *
   * @return array
   */
  public function getRange($start, $stop = NULL);

  /**
   * @return float
   */
  public function getLastSequenceId();

}
