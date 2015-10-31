<?php

/**
 * @file
 * Contains \Drupal\fences\TagManager.
 */

namespace Drupal\fences;

/**
 * Gathers and provides the tags that can be used to wrap fields.
 */
interface TagManagerInterface {

  /**
   * Get the tags that can wrap fields.
   *
   * @return array
   *   An array of tags.
   */
  public function getTagOptions();

}
