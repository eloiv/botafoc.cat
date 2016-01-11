<?php

namespace Drupal\image_styles_mapping\Plugin\ImageStylesMapping;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;
use Drupal\image_styles_mapping\Plugin\ImageStylesMappingPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @ImageStylesMapping(
 *   id = "responsive_image_styles",
 *   label = @Translation("Responsive image styles"),
 *   description = @Translation("Adds responsive image styles support."),
 * )
 */
class ResponsiveImageStyles extends ImageStylesMappingPluginBase implements ContainerFactoryPluginInterface {
  use LinkGeneratorTrait;

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entity_type_manager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityTypeManager $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entity_type_manager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies() {
    return array('responsive_image');
  }

  /**
   * {@inheritdoc}
   */
  public function getHeader() {
    return $this->displayResponsiveImageStyleLink();
  }

  /**
   * {@inheritdoc}
   */
  public function getRowData(array $field_settings) {
    $responsive_image_styles = array();

    foreach ($this->getResponsiveImageStyles() as $responsive_image_style_id => $responsive_image_style_label) {
      // Use recursive search because the structure of the field_formatter is
      // unknown.
      $search_result = FALSE;
      $this->recursiveSearch($responsive_image_style_id, $field_settings, $search_result);
      if ($search_result) {
        $responsive_image_styles[] = $this->displayResponsiveImageStyleLink($responsive_image_style_label, $responsive_image_style_id);
      }
    }

    // Case empty.
    if (empty($responsive_image_styles)) {
      $responsive_image_styles[] = $this->t('No responsive image style used');
    }

    $responsive_image_styles = implode(', ', $responsive_image_styles);
    // Use FormattableMarkup object to avoid link in plain text.
    $responsive_image_styles = new FormattableMarkup($responsive_image_styles, array());
    return $responsive_image_styles;
  }

  /**
   * Helper function to get the responsive image styles.
   *
   * @return array
   *   An array of responsive image styles name keyed with its id.
   */
  public function getResponsiveImageStyles() {
    $responsive_image_styles = &drupal_static(__FUNCTION__);

    if (!isset($responsive_image_styles)) {
      $responsive_image_style_entities = $this->entity_type_manager->getStorage('responsive_image_style')->loadMultiple();

      $responsive_image_styles = array();
      foreach ($responsive_image_style_entities as $responsive_image_style_entity) {
        // Get the info we seek from the responsive image styles entity.
        $responsive_image_styles[$responsive_image_style_entity->get('id')] = $responsive_image_style_entity->get('label');
      }
    }

    return $responsive_image_styles;
  }

  /**
   * Helper function.
   *
   * Display a link to responsive image style edit page if user has
   * permission.
   *
   * If no argument is given, display a link to the responsive image style
   * list.
   *
   * @param string $responsive_image_style_label
   *   The label of the responsive image style.
   * @param string $responsive_image_style_id
   *   The ID of the responsive image style.
   *
   * @return string
   *   A link to the responsive image style if user has access.
   *   The responsive image style's label otherwise.
   */
  public function displayResponsiveImageStyleLink($responsive_image_style_label = '', $responsive_image_style_id = '') {
    // Prepare link.
    if ($responsive_image_style_label != '' && $responsive_image_style_id != '') {
      $url = Url::fromRoute('entity.responsive_image_style.edit_form', array('responsive_image_style' => $responsive_image_style_id));
      $link_text = $responsive_image_style_label;
    }
    else {
      $url = Url::fromRoute('entity.responsive_image_style.collection');
      $link_text = $this->t('Responsive image styles (not sortable)');
    }

    // Use the routing system to check access.
    if ($url->renderAccess($url->toRenderArray())) {
      return $this->l($link_text, $url);
    }
    else {
      return $responsive_image_style_label;
    }
  }

}
