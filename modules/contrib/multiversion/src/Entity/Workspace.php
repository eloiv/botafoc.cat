<?php

namespace Drupal\multiversion\Entity;

use Drupal\Core\Entity\Entity;
use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * The workspace entity class.
 *
 * @ConfigEntityType(
 *   id = "workspace",
 *   label = @Translation("Workspace"),
 *   config_prefix = "workspace",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "id",
 *     "created" = "created",
 *   }
 * )
 */
class Workspace extends ConfigEntityBase implements WorkspaceInterface {

  /**
   * The name of the workspace.
   *
   * @var string
   */
  public $id;

  /**
   * The UNIX timestamp of when the workspace has been created.
   *
   * @var int
   */
  public $created;

  /**
   * {@inheritdoc}
   */
  public function getUpdateSeq() {
    return \Drupal::service('entity.index.sequence')->useWorkspace($this->id)->getLastSequenceId();
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($created) {
    $this->created = (int) $created;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStartTime() {
    return $this->created;
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    if (is_null($this->getStartTime())) {
      $this->setCreatedTime(microtime(TRUE) * 1000000);
    }
    parent::save();
  }

}
