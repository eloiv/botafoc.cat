<?php

/**
 * @file
 * Contains \Drupal\multiversion\MenuTreeStorage.
 */

namespace Drupal\multiversion;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Menu\MenuTreeStorage as CoreMenuTreeStorage;

class MenuTreeStorage extends CoreMenuTreeStorage {

  /**
   * @var \Drupal\multiversion\Workspace\WorkspaceManagerInterface
   */
  protected $workspaceManager;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(Connection $connection, CacheBackendInterface $menu_cache_backend, CacheTagsInvalidatorInterface $cache_tags_invalidator, $table, array $options = array()) {
    $this->connection = $connection;
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
    $this->table = $table;
    $this->options = $options;

    $this->entityTypeManager = \Drupal::service('entity_type.manager');
    $this->workspaceManager = \Drupal::service('workspace.manager');

    $this->menuCacheBackend = new CacheBackendDecorator(
      $menu_cache_backend,
      $this->workspaceManager
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function loadLinks($menu_name, MenuTreeParameters $parameters) {
    $links = parent::loadLinks($menu_name, $parameters);
    $map = [];
    // Collect all menu_link_content IDs from the links.
    foreach ($links as $i => $link) {
      if ($link['provider'] != 'menu_link_content') {
        continue;
      }
      $metadata = unserialize($link['metadata']);
      $map[$metadata['entity_id']] = $i;
    }

    // Load all menu_link_content entities and remove links for the those that
    // don't belong to the active workspace.
    $active_workspace_id = $this->workspaceManager->getActiveWorkspace()->id();
    $entities = $this->entityTypeManager
      ->getStorage('menu_link_content')
      ->loadMultiple(array_keys($map));

    foreach ($map as $entity_id => $link_id) {
      if (!isset($entities[$entity_id]) || $active_workspace_id != $entities[$entity_id]->workspace->target_id) {
        unset($links[$link_id]);
      }
    }
    return $links;
  }
}
