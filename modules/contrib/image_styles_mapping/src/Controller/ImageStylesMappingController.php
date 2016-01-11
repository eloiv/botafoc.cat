<?php

/**
 * @file
 * Contains \Drupal\image_styles_mapping\Controller\ImageStylesMappingController.
 */

namespace Drupal\image_styles_mapping\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\image_styles_mapping\Service\ImageStylesMappingService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ImageStylesMappingController.
 *
 * @package Drupal\image_styles_mapping\Controller
 */
class ImageStylesMappingController extends ControllerBase {

  /**
   * Fields report title.
   *
   * @var string
   */
  protected $fieldsReportTitle;

  /**
   * Fields report empty value.
   *
   * @var string
   */
  protected $fieldsReportEmptyValue;

  /**
   * Views fields report title.
   *
   * @var string
   */
  protected $viewsFieldsReportTitle;

  /**
   * Views fields report empty value.
   *
   * @var string
   */
  protected $viewsFieldsReportEmptyValue;

  /**
   * Image styles mapping service.
   *
   * @var ImageStylesMappingService
   */
  protected $imageStylesMappingService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('image_styles_mapping.image_styles_mapping_service')
    );
  }

  /**
   * Constructs a new ImageStylesMappingController.
   *
   * @param \Drupal\image_styles_mapping\Service\ImageStylesMappingService $imageStylesMappingService
   */
  public function __construct(ImageStylesMappingService $imageStylesMappingService) {
    $this->imageStylesMappingService = $imageStylesMappingService;
    $this->fieldsReportTitle = $this->t('Image fields');
    $this->fieldsReportEmptyValue = $this->t('No image styles or responsive image styles have been used in any views fields yet.');
    $this->viewsFieldsReportTitle = $this->t('View image fields');
    $this->viewsFieldsReportEmptyValue = $this->t('No image styles or responsive image styles have been used in any views fields yet.');
  }

  /**
   * @return string[]
   *   Name of available reports.
   */
  public function getAvailableReports() {
    // TODO: Remove the hardcoded reports, maybe using a plugin architecture.
    return array(
      'fieldsReport',
      'viewsFieldsReport',
    );
  }

  /**
   * @param string $reportName
   *   The report's name.
   *
   * @return bool
   *   TRUE if the report is available. FALSE otherwise.
   */
  public function isAvailableReport($reportName) {
    $available = FALSE;
    // TODO: Remove the hardcoded reports, maybe using a plugin architecture.
    switch ($reportName) {
      case 'fieldsReport':
        $available = TRUE;
        break;

      case 'viewsFieldsReport':
        if ($this->moduleHandler()->moduleExists('views')) {
          $available = TRUE;
        }
        break;
    }
    return $available;
  }

  /**
   * @param string $reportName
   *   The report name to display.
   *
   * @return array
   *   Display a table of the image styles used in fields.
   */
  public function getReport($reportName) {
    $fieldReport = $this->imageStylesMappingService->{$reportName}();
    return $this->renderTable(
      $this->{$reportName . 'Title'},
      $fieldReport['header'],
      $fieldReport['rows'],
      $this->{$reportName . 'EmptyValue'});
  }

  /**
   * Displays all the reports.
   *
   * @return string
   *   HTML tables for the results.
   */
  public function allReport() {
    $reports = $this->getAvailableReports();
    $output = array();

    foreach ($reports as $reportName) {
      if ($this->isAvailableReport($reportName)) {
        $output[] = $this->getReport($reportName);
      }
    }

    return $output;
  }

  /**
   * Helper function to sort rows.
   *
   * @param array $header
   *   The table's header.
   * @param array $rows
   *   Array of rows.
   *
   * @return array $rows
   *   Array of sorted rows.
   */
  public function sortRows(array $header, array $rows) {
    if (!empty($rows)) {
      // Get selected order from the request or the default one.
      $order = tablesort_get_order($header);
      // Please note that we do not run any sql query against the database. The
      // 'sql' key is simply there for tablesort needs.
      $order = $order['sql'];

      // Get the field we sort by from the request if any.
      $sort = tablesort_get_sort($header);

      // Obtain the column we need to sort by.
      foreach ($rows as $key => $value) {
        $order_column[$key] = $value[$order];
      }
      // Sort data.
      if ($sort == 'asc') {
        array_multisort($order_column, SORT_ASC, $rows);
      }
      elseif ($sort == 'desc') {
        array_multisort($order_column, SORT_DESC, $rows);
      }
    }
    return $rows;
  }

  /**
   * Helper function to render a table.
   *
   * @param string $h2_string
   *   The string to use as H2 title.
   * @param array $header
   *   The table's header.
   * @param array $rows
   *   The table's row.
   * @param $empty_string
   *   The string to use of the table is empty.
   *
   * @return array
   *   A renderable array.
   */
  public function renderTable($h2_string, array $header, array $rows, $empty_string) {
    $output = array();
    $output[] = array(
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => $h2_string,
    );

    $output[] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $this->sortRows($header, $rows),
      '#empty' => $empty_string,
    );

    return $output;
  }
}
