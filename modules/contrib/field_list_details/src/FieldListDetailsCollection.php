<?php

namespace Drupal\field_list_details;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class FieldListDetailsCollection.
 *
 * @package Drupal\field_list_details
 */
class FieldListDetailsCollection {

  use StringTranslationTrait;

  /**
   * Field definition.
   *
   * @var \Drupal\Core\Field\FieldDefinitionInterface
   */
  protected $field;

  /**
   * Field settings.
   *
   * @var array
   */
  protected $settings = [];

  /**
   * Generated details array.
   *
   * @var array
   */
  protected $details = [];

  /**
   * FieldListDetailsCollection constructor.
   *
   * @param FieldDefinitionInterface $field
   */
  function __construct($field) {
    $this->field = $field;
    $this->settings = $field->getSettings();
  }

  /**
   * Create and return a keyed array of field details.
   *
   * @return array
   */
  function getDetails(){

    $this->setDetail('field_name', $this->field->getName());

    if ($this->field->isRequired()) {
      $this->addRequiredDetail();
    }

    if (!empty($this->settings['datetime_type'])) {
      $this->addDateTypeDetail();
    }

    if ($this->field->getType() == 'entity_reference') {
      $this->addEntityReferenceDetail();
    }

    if ($this->field->getType() == 'file' || $this->field->getType() == 'image') {
      $this->addFileDetail();
    }

    if ($this->field->getType() == 'address') {
      $this->addAddressDetail();
    }

    $this->addFieldEncryptDetail();
    $this->addCardinalityDetail();

    // Allow modules to add their own details.
    $field_copy = clone $this->field;
    \Drupal::moduleHandler()->alter('field_list_details', $this, $field_copy);

    return $this->details;
  }

  /**
   * Set a single detail in the collection.
   *
   * @param $key
   * @param $value
   * @param null $label
   */
  function setDetail($key, $value, $label = null) {
    $detail = ['value' => $value];

    if ($label) {
      $detail['label'] = $label;
    }

    $this->details[$key] = $detail;
  }

  /**
   * Add the required detail
   */
  function addRequiredDetail() {
    $this->setDetail('required', $this->field->isRequired() ? $this->t('true') : $this->t('false'), $this->t('Required'));
  }

  /**
   * Add the field_type detail for datetime fields.
   */
  function addDateTypeDetail() {
    $this->setDetail('field_type', $this->settings['datetime_type'], $this->t('Date type'));
  }

  /**
   * Add the field_type detail for entity reference fields.
   */
  function addEntityReferenceDetail() {
    $value = $this->settings['target_type'];

    if (!empty($this->settings['handler_settings']['target_bundles'])) {
      $value .= ' - ' . implode(',', $this->settings['handler_settings']['target_bundles']);
    }

    $this->setDetail('field_type', $value, $this->t('Reference'));
  }

  /**
   * Add the field_type detail for file fields.
   */
  function addFileDetail() {
    if ($definition instanceof Drupal\Core\Config\Entity\ThirdPartySettingsInterface) {
      $ffp = $definition->getThirdPartySettings('filefield_paths');
    }
    $path = "{$this->settings['file_directory']}";

    if (!empty($ffp)) {
      if ($ffp['enabled']) {
        $path = "{$ffp['file_path']['value']}/{$ffp['file_name']['value']}";

        if ($ffp['active_updating']) {
          $details[] = ['value' => '-active updating-'];
        }
      }
    }

    $this->setDetail('field_type', "{$this->settings['uri_scheme']}://{$path}", $this->t('File'));
  }

  /**
   * Add field_type detail about address fields
   */
  function addAddressDetail() {
    $this->setDetail('field_type', implode(', ', array_filter($this->settings['fields'])), $this->t('Address'));
  }

  /**
   * Add details about the field_encrypt module.
   */
  function addFieldEncryptDetail() {
    $settings = $this->field->getFieldStorageDefinition()->getThirdPartySettings('field_encrypt');
    $definition = $this->field;
    if ($definition instanceof Drupal\Core\Config\Entity\ThirdPartySettingsInterface) {
      $settings = $definition->getThirdPartySettings('field_encrypt');
    }

    if (!empty($settings)) {
      $details = [];

      foreach ($settings as $key => $value) {
        if (!is_array($value)) {
          $details[] =  ucfirst($key) . ': ' . (string) $value;
        }
      }

      $this->setDetail('field_encrypt', implode(', ', $details), $this->t('Field Encrypt'));
    }
  }

  /**
   * Add field_cardinality detail. Assume 1 is default and not need to be shown.
   */
  function addCardinalityDetail() {
    $cardinality = $this->field->getFieldStorageDefinition()->getCardinality();

    if ($cardinality != 1) {
      if ($cardinality < 0) {
        $cardinality = $this->t('unlimited');
      }

      $this->setDetail('field_cardinality', $cardinality, $this->t('Cardinality'));
    }
  }
}
