<?php

/**
 * @file
 * Contains \Drupal\multiversion\Plugin\migrate\destination\JsonFile.
 */

namespace Drupal\multiversion\Plugin\migrate\destination;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\migrate\destination\DestinationBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @MigrateDestination(
 *   id = "json_file"
 * )
 */
class JsonFile extends DestinationBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    $file_uri = 'private://multiversion_migrate_users.json';
    $file = file_save_data('', $file_uri, FILE_EXISTS_REPLACE);
    if (!$file) {
      throw new MigrateException(t('Error on creating the JSON file.'));
    }

    $configuration += array(
      'file_uri' => $file_uri,
    );

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration
    );
  }

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = array()) {
    $source = $row->getSource();
    if (file_put_contents($this->configuration['file_uri'], json_encode($source) . "\n", FILE_APPEND)) {
      return array('uid' => $source['uid']);
    }
    return FALSE;
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
  public function fields(MigrationInterface $migration = NULL) {
    // TODO: Implement fields() method.
  }

}
