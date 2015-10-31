<?php

/**
 * @file
 * Contains \Drupal\multiversion\Plugin\Migrate\source\UserDrupal.
 */

namespace Drupal\multiversion\Plugin\Migrate\source;

use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;

/**
 * User source from Drupal 8 to json.
 *
 * @MigrateSource(
 *   id = "user_drupal"
 * )
 */
class UserDrupal extends SourcePluginBase {

  /**
   * Initialize the iterator with the source data.
   *
   * @return array
   *   An array of the data for this source.
   */
  protected function initializeIterator() {
    $entities = entity_load_multiple('user');
    $result = array();
    foreach ($entities as $entity) {
      foreach ($this->fields() as $field_name => $label) {
        if ($field_name == 'roles') {
          $result[$entity->id()][$field_name] = $entity->getRoles();
          continue;
        }
        if ($field_name == 'user_picture' && isset($entity->{$field_name}->target_id)) {
          $result[$entity->id()][$field_name]['target_id'] = $entity->{$field_name}->target_id;
          continue;
        }
        $result[$entity->id()][$field_name] = NULL;
        if (isset($entity->{$field_name}->value)) {
          $result[$entity->id()][$field_name] = $entity->{$field_name}->value;
        }
      }
    }

    return new \ArrayIterator(array_values($result));
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = $this->baseFields();

    // Add roles field.
    $fields['roles'] = $this->t('Roles');

    // Add user picture field.
    $fields['user_picture'] = $this->t('Roles');

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return array(
      'uid' => array(
        'type' => 'integer',
        'alias' => 'u',
      ),
    );
  }

  /**
   * Returns the user base fields to be migrated.
   *
   * @return array
   *   Associative array having field name as key and description as value.
   */
  protected function baseFields() {
    $fields = array(
      'uid' => $this->t('User ID'),
      'uuid' => $this->t('UUID'),
      'langcode' => $this->t('Language code'),
      'preferred_langcode' => $this->t('Preferred language code'),
      'preferred_admin_langcode' => $this->t('Preferred admin language code'),
      'name' => $this->t('Username'),
      'pass' => $this->t('Password'),
      'mail' => $this->t('Email'),
      'timezone' => $this->t('Timezone'),
      'status' => $this->t('Status'),
      'created' => $this->t('Created'),
      'changed' => $this->t('Changed'),
      'access' => $this->t('Last access'),
      'login' => $this->t('Last login'),
      'init' => $this->t('Initial email'),
    );

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function bundleMigrationRequired() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function entityTypeId() {
    return 'user';
  }

  public function __toString() {
    // TODO: Implement __toString() method.
  }

}
