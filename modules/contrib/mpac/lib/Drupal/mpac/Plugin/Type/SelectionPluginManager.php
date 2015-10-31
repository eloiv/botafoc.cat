<?php

/**
 * @file
 * Contains \Drupal\mpac\Plugin\Type\SelectionPluginManager.
 */

namespace Drupal\mpac\Plugin\Type;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\Factory\ReflectionFactory;
use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Core\Plugin\Discovery\AlterDecorator;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Core\Plugin\Discovery\CacheDecorator;
use Drupal\mpac\Plugin\Type\Selection\SelectionBroken;

/**
 * Plugin type manager for the Multi-path autocomplete Selection plugin.
 */
class SelectionPluginManager extends PluginManagerBase {

  /**
   * Constructs a SelectionPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations,
   */
  public function __construct(\Traversable $namespaces) {
    $this->baseDiscovery = new AlterDecorator(new AnnotatedClassDiscovery('mpac/selection', $namespaces), 'mpac_selection');
    $this->discovery = new CacheDecorator($this->baseDiscovery, 'mpac_selection');
    $this->factory = new ReflectionFactory($this);
  }

  /**
   * Overrides \Drupal\Component\Plugin\PluginManagerBase::createInstance().
   */
  public function createInstance($plugin_id, array $configuration = array()) {
    // We want to provide a broken handler class whenever a class is not found.
    try {
      return parent::createInstance($plugin_id, $configuration);
    }
    catch (PluginException $e) {
      return new SelectionBroken();
    }
  }

  /**
   * Overrides \Drupal\Component\Plugin\PluginManagerBase::getInstance().
   */
  public function getInstance(array $options) {
    $type = $options['type'];

    // Get all available selection plugins for this entity type.
    $selection_handler_groups = $this->getSelectionGroups($type);

    // Sort the selection plugins by weight and select the best match.
    uasort($selection_handler_groups, 'drupal_sort_weight');
    end($selection_handler_groups);
    $plugin_id = key($selection_handler_groups);

    return $this->createInstance($plugin_id, $options);
  }

  /**
   * Returns a list of selection plugins that can provide autocomplete results.
   *
   * @param string $type
   *   A Multi-path autocomplete type.
   *
   * @return array
   *   An array of selection plugins grouped by selection group.
   */
  public function getSelectionGroups($type) {
    $plugins = array();

    foreach ($this->getDefinitions() as $plugin_id => $plugin) {
      if (!isset($plugin['types']) || in_array($type, $plugin['types'])) {
        $plugins[$plugin_id] = $plugin;
      }
    }

    return $plugins;
  }
}
