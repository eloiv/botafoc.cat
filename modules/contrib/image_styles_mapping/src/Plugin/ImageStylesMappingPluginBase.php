<?php

namespace Drupal\image_styles_mapping\Plugin;

use Drupal\Core\Plugin\PluginBase;

/**
 * Base class for plugins able to add columns on image styles mapping reports.
 *
 * @ingroup plugin_api
 */
abstract class ImageStylesMappingPluginBase extends PluginBase implements ImageStylesMappingPluginInterface {

  /**
   * {@inheritdoc}
   */
  function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies() {
    return array('image');
  }

  /**
   * {@inheritdoc}
   */
  public function getHeader() {
    return $this->t('Image styles (not sortable)');
  }

  /**
   * {@inheritdoc}
   */
  public function getRowData(array $field_settings) {
    $image_styles = array();

    foreach ($this->getImageStyles() as $image_style_name => $image_style_label) {
      // Use recursive search because the structure of the
      // field_formatter is unknown.
      $search_result = FALSE;
      $this->recursiveSearch($image_style_name, $field_settings, $search_result);
      if ($search_result) {
        $image_styles[] = $this->displayImageStyleLink($image_style_label, $image_style_name);
      }
    }

    // Case empty.
    if (empty($image_styles)) {
      $image_styles[] = $this->t('No image style used');
    }

    $image_styles = implode(', ', $image_styles);
    return $image_styles;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return array();
  }

  /**
   * Helper function.
   *
   * Checks if a value is used in an array.
   *
   * @param string $needle
   *   The value searched.
   * @param array $haystack
   *   The array in which the value is searched.
   * @param bool @result
   *   If the needle has been found.
   *
   * @return bool
   *   TRUE if the value is found.
   */
  public function recursiveSearch($needle, $haystack, &$result) {
    if (!is_array($haystack)) {
      return;
    }

    foreach ($haystack as $value) {
      if (is_array($value)) {
        $this->recursiveSearch($needle, $value, $result);
      }
      elseif ($needle === $value) {
        $result = TRUE;
      }
    }
  }

}
