<?php

namespace Drupal\image_styles_mapping\Plugin;

/**
 * Provides an interface for a plugin that add columns on image styles mapping
 * reports.
 *
 * @ingroup plugin_api
 */
interface ImageStylesMappingPluginInterface {

  /**
   * Get the plugin's dependencies.
   *
   * @return array
   *   The plugin's dependencies.
   */
  public function getDependencies();

  /**
   * Get the header for the column added by the plugin.
   *
   * @return string
   *   The header for the column added by the plugin.
   */
  public function getHeader();

  /**
   * Get the row for the column added by the plugin.
   *
   * @param array $field_settings
   *   The field display of the row.
   *
   * @return string
   *   The header for the column added by the plugin.
   */
  public function getRowData(array $field_settings);

}
