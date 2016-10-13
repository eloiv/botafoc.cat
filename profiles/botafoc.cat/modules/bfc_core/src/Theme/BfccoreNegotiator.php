<?php
/**
 * @file
 * Contains \Drupal\btf_core\Theme\BfccoreNegotiator.
 */

namespace Drupal\bfc_core\Theme;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ThemeNegotiatorInterface;

class BfccoreNegotiator implements ThemeNegotiatorInterface {

  public function applies(RouteMatchInterface $route_match) {
    $applies = FALSE;
    $current_path = \Drupal::service('path.current')->getPath();
    $path_args = explode('/', $current_path);
    if ($path_args[1] == 'user' and ($path_args[2] == 'login' || $path_args[2] == 'password')) {
      $applies = TRUE;
    }
    return $applies;
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    //$section_menus = array('section1', 'section2', 'section3');
    /*foreach ($section_menus as $menu_name) {
      $link = \Drupal::service('menu.active_trail')->getActiveLink($menu_name);
      if (!empty($link)) {*/
        return 'bfc_admin_theme';
      //}
    /*}*/
  }
}
