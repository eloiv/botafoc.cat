<?php

/**
 * @file
 * Contains \Drupal\multiversion\Plugin\Migrate\source\UserJson.
 */

namespace Drupal\multiversion\Plugin\Migrate\source;

use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;

/**
 * User source from json file.
 *
 * @MigrateSource(
 *   id = "user_json"
 * )
 */
class UserJson extends  SourcePluginBase {

  /**
   * @var string
   */
  protected $json;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
  }

  public function __toString() {
    // TODO: Implement __toString() method.
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = array(
      'uid' => $this->t('User ID'),
      'name' => $this->t('Username'),
      'pass' => $this->t('Password'),
      'mail' => $this->t('Email address'),
      'signature' => $this->t('Signature'),
      'signature_format' => $this->t('Signature format'),
      'created' => $this->t('Registered timestamp'),
      'access' => $this->t('Last access timestamp'),
      'login' => $this->t('Last login timestamp'),
      'status' => $this->t('Status'),
      'timezone' => $this->t('Timezone'),
      'language' => $this->t('Language'),
      'picture' => $this->t('Picture'),
      'init' => $this->t('Init'),
    );
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

  /**
   * Getter for currentSourceIds data member.
   */
  public function getCurrentIds() {
    $lines = $this->getValues();
    $ids = array();
    foreach ($lines as $line) {
      $ids[] = $line['uid'];
    }
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function count($refresh = FALSE) {
    $lines = $this->getValues();
    return count($lines);
  }

  /**
   * Initialize the iterator with the source data.
   *
   * @return array
   *   An array of the data for this source.
   */
  protected function initializeIterator() {
    $lines = $this->getValues();
    $values = array();
    foreach ($lines as $line) {
      $values[] = json_decode($line, TRUE);
    }
    return new \ArrayIterator($values);
  }

  protected function getValues() {
    $lines = file('private://multiversion_migrate_users.json', FILE_IGNORE_NEW_LINES);
    return $lines;
  }

}
