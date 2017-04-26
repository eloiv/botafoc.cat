<?php

/**
 * @file
 * Contains Drupal\config_log\EventSubscriber\ConfigLogDatabaseSubscriber.
 */

namespace Drupal\config_log\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigRenameEvent;
use Drupal\Core\Config\ConfigImporterEvent;
use Drupal\Core\Config\ConfigEvents;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Exception\DumpException;
use Symfony\Component\Yaml\Parser;

/**
 * ConfigLog subscriber for configuration CRUD events.
 */
class ConfigLogDatabaseSubscriber implements EventSubscriberInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * A shared YAML dumper instance.
   *
   * @var Symfony\Component\Yaml\Dumper
   */
  protected $dumper;

  /**
   * Constructs the ConfigLogDatabaseSubscriber object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   */
  function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * Encode data as YAML
   *
   * @see: Drupal\Core\Config\FileStorage:encode()
   *
   * @throws Symfony\Component\Yaml\Exception\DumpException
   */
  protected function encode($data) {
    if (!isset($this->dumper)) {
      $this->dumper = new Dumper();
      // Set Yaml\Dumper's default indentation for nested nodes/collections to
      // 2 spaces for consistency with Drupal coding standards.
      $this->dumper->setIndentation(2);
    }
    // The level where you switch to inline YAML is set to PHP_INT_MAX to ensure
    // this does not occur. Also set the exceptionOnInvalidType parameter to
    // TRUE, so exceptions are thrown for an invalid data type.
    return $this->dumper->dump($data, PHP_INT_MAX, 0, TRUE);
  }

  /**
   * React to configuration ConfigEvent::SAVE events.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   The event to process.
   */
  public function onConfigSave(ConfigCrudEvent $event) {
    $config = $event->getConfig();
    $values = array(
      'uid' => \Drupal::currentUser()->id(),
      'operation' => $config->isNew() ? 'create' : 'update',
      'name' => $config->getName(),
      'data' => $this->encode($config->get()),
    );
    $this->database
      ->insert('config_log')
      ->fields(array('uid', 'operation', 'name', 'data'))
      ->values($values)
      ->execute();
  }

  /**
   * React to configuration ConfigEvent::RENAME events.
   *
   * @param \Drupal\Core\Config\ConfigRenameEvent $event
   *   The event to process.
   */
  public function onConfigRename(ConfigRenameEvent $event) {
    $config = $event->getConfig();
    $values = array(
      'uid' => \Drupal::currentUser()->id(),
      'operation' => 'rename',
      'name' => $config->getName(),
      'old_name' => $event->getOldName(),
      'data' => $this->encode($config->get()),
    );
    $this->database
      ->insert('config_log')
      ->fields(array('uid', 'operation', 'name', 'old_name', 'data'))
      ->values($values)
      ->execute();
  }

  /**
   * React to configuration ConfigEvent::DELETE events.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   The event to process.
   */
  public function onConfigDelete(ConfigCrudEvent $event) {
    $config = $event->getConfig();
    $values = array(
      'uid' => \Drupal::currentUser()->id(),
      'operation' => 'delete',
      'name' => $config->getName(),
    );
    $this->database
      ->insert('config_log')
      ->fields(array('uid', 'operation', 'name'))
      ->values($values)
      ->execute();
  }

  /**
   * React to configuration ConfigEvent::IMPORT events.
   *
   * @param \Drupal\Core\Config\ConfigImporterEvent $event
   *   The event to process.
   */
  public function onConfigImport(ConfigImporterEvent $event) {
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  static function getSubscribedEvents() {
    $events[ConfigEvents::SAVE][] = array('onConfigSave', 10);
    $events[ConfigEvents::DELETE][] = array('onConfigDelete', 10);
    $events[ConfigEvents::RENAME][] = array('onConfigRename', 10);
    $events[ConfigEvents::IMPORT][] = array('onConfigImport', 10);
    return $events;
  }
}
