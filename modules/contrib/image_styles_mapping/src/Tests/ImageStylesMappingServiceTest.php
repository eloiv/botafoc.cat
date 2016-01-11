<?php

/**
 * @file
 * Tests for ImageStylesMappingService.
 */

namespace Drupal\image_styles_mapping\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the image styles mapping service.
 *
 * @group Image Styles Mapping
 */
class ImageStylesMappingServiceTest extends WebTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = array('image_styles_mapping', 'responsive_image');

  /**
   * Verifies the value returned by the function getImageStyles.
   */
  public function testActivePlugins() {
    // Get image styles mapping plugin manager.
    $imageStylesMappingPluginManager = \Drupal::service('plugin.manager.image_styles_mapping.image_styles_mapping');

    // Get active image styles mapping plugins.
    $active_plugins = $imageStylesMappingPluginManager->getDefinitions();
    $active_plugin_ids = array_keys($active_plugins);

    // Expected image styles.
    $expected_plugin_ids = array(
      'image_styles',
      'responsive_image_styles',
    );

    // Sort arrays to avoid failing test with DrupalCI.
    sort($active_plugin_ids);
    sort($expected_plugin_ids);

    $this->assertEqual($active_plugin_ids, $expected_plugin_ids, 'The expected plugins are active.', 'Image Styles Mapping');
  }

}
