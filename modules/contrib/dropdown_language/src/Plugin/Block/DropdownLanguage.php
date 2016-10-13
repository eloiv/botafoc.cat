<?php

namespace Drupal\dropdown_language\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;

/**
 * Class DropdownLanguage.
 *
 * @package Drupal\dropdown_language\Form
 *
 * @Block(
 *   id = "dropdown_language",
 *   admin_label = @Translation("Dropdown Language Selector"),
 *   category = @Translation("Custom Blocks"),
 * )
 */
class DropdownLanguage extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'label_display' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $block = [];
    $language_manager = \Drupal::languageManager();
    $current_language = $language_manager->getCurrentLanguage()->getId();
    $languages = $language_manager->getLanguages();
    if (count($languages) > 1) {
      foreach ($languages as $lid => $item) {
        $url = Url::fromRoute('<current>', [], ['language' => $item]);
        $links[$lid] = [
          'title' => $item->getName(),
          'url' => $url,
        ];
      }
      // Place active language ontop of list.
      $active = $links[$current_language];
      unset($links[$current_language]);
      array_unshift($links, $active);
      $config = \Drupal::config('dropdown_language.setting');
      $wrapper_default = $config->get('wrapper');
      if ($wrapper_default == 1) {
        $block['switcher'] = [
          '#weight' => -100,
          '#type' => 'fieldset',
          '#title' => $this->t('Switch Language'),
        ];
        $block['switcher']['switch-language'] = [
          '#type' => 'dropbutton',
          '#links' => $links,
        ];
      }
      else {
        $block['switch-language'] = [
          '#type' => 'dropbutton',
          '#links' => $links,
        ];
      }
    }

    return $block;
  }

}
