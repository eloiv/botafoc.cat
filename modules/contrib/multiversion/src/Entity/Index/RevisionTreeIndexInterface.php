<?php

namespace Drupal\multiversion\Entity\Index;

interface RevisionTreeIndexInterface {

  /**
   * @param $workspace_id
   * @return \Drupal\multiversion\Entity\Index\EntityIndexInterface
   */
  public function useWorkspace($workspace_id);

  /**
   * @param string $uuid
   *
   * @return array
   */
  public function getTree($uuid);

  /**
   * @param array $branch
   */
  public function updateTree($uuid, array $branch = array());

  /**
   * @param string $uuid
   *
   * @return string
   */
  public function getDefaultRevision($uuid);

  /**
   * @param string $uuid
   *
   * @return string[]
   */
  public function getDefaultBranch($uuid);

  /**
   * @param string $uuid
   *
   * @return string[]
   */
  public function getOpenRevisions($uuid);

  /**
   * @param string $uuid
   *
   * @return string[]
   */
  public function getConflicts($uuid);

}
