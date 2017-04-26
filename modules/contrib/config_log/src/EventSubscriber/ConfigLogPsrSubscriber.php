<?php

/**
 * @file
 * Contains \Drupal\config\ConfigPsrSubscriber.
 */

namespace Drupal\config_log\EventSubscriber;

use Drupal\Component\Utility\DiffArray;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Config subscriber.
 */
class ConfigLogPsrSubscriber implements EventSubscriberInterface {

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * @param \Psr\Log\LoggerInterface $logger
   */
  public function __construct(LoggerInterface $logger) {
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[ConfigEvents::SAVE][] = array('onConfigSave', 20);
    $events[ConfigEvents::DELETE][] = array('onConfigSave', 20);
    return $events;
  }

  /**
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   */
  public function onConfigSave(ConfigCrudEvent $event) {
    $config = $event->getConfig();
    $diff = DiffArray::diffAssocRecursive($config->get(), $config->getOriginal());
    $this->logConfigChanges($config, $diff);
  }

  /**
   * @param \Drupal\Core\Config\Config $config
   * @param array $diff
   * @param string $subkey
   */
  protected function logConfigChanges($config, $diff, $subkey = NULL) {
    foreach ($diff as $key => $value) {
      $full_key = $key;
      if ($subkey) {
        $full_key = $this->joinKey($subkey, $key);
      }

      if (is_array($value)) {
        $this->logConfigChanges($config, $diff[$key], $full_key);
      }
      else {
        $this->logger->info("Configuration changed: %key changed from %original to %value", array(
          '%key' => $this->joinKey($config->getName(), $full_key),
          '%original' => $this->format($config->getOriginal($full_key)),
          '%value' => $this->format($value),
        ));
      }
    }
  }

  /**
   * @param $value
   * @return mixed
   */
  private function format($value) {
    if ($value === NULL) {
      return "NULL";
    }

    if ($value === "") {
      return '<empty string>';
    }

    if (is_bool($value)) {
      return ($value ? 'TRUE' : 'FALSE');
    }

    return $value;
  }

  /**
   * @param $subkey
   * @param $key
   * @return string
   */
  private function joinKey($subkey, $key) {
    return $subkey . '.' . $key;
  }
}
