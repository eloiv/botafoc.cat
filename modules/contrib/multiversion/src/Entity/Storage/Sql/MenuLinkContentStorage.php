<?php

/**
 * @file
 * Contains Drupal\multiversion\Entity\Storage\Sql\MenuLinkContentStorage.
 */

namespace Drupal\multiversion\Entity\Storage\Sql;

use Drupal\multiversion\Entity\Storage\ContentEntityStorageInterface;

/**
 * Defines the controller class for menu_link_content.
 */
class MenuLinkContentStorage extends ContentEntityStorage implements ContentEntityStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function delete(array $entities) {
    parent::delete($entities);

    /** @var \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager */
    $menu_link_manager = \Drupal::service('plugin.manager.menu.link');

    foreach ($entities as $menu_link) {
      // Remove link definition from the menu tree storage.
      $menu_link_manager->removeDefinition($menu_link->getPluginId(), FALSE);
    }
  }

}
