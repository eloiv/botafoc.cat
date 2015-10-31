<?php

/**
 * @file
 * Contains \Drupal\multiversion\MigratePassword.
 */

namespace Drupal\multiversion;

use Drupal\user\MigratePassword as CoreMigratePassword;

/**
 * Extends core MigratePassword class.
 */
class MigratePassword extends CoreMigratePassword {

  /**
   * Indicates if the source password should be hashed or not.
   */
  protected $hashPassword = TRUE;

  /**
   * {@inheritdoc}
   */
  public function hash($password) {
    if (!$this->hashPassword) {
      return $password;
    }

    return parent::hash($password);
  }

  /**
   * Disables password hashing.
   */
  public function disablePasswordHashing() {
    $this->hashPassword = FALSE;
  }

}
