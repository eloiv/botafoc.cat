<?php

/**
 * @file
 * Contains \Drupal\multiversion\Entity\Storage\Sql\UserStorage.
 */

namespace Drupal\multiversion\Entity\Storage\Sql;

use Drupal\multiversion\Entity\Storage\ContentEntityStorageInterface;
use Drupal\multiversion\Entity\Storage\ContentEntityStorageTrait;
use Drupal\user\UserStorage as CoreUserStorage;

/**
 * Defines the controller class for nodes.
 */
class UserStorage extends CoreUserStorage implements ContentEntityStorageInterface {

  use ContentEntityStorageTrait;

}
