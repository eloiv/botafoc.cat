<?php

namespace Drupal\field_list_details;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field_ui\Form\EntityFormDisplayEditForm;

/**
 * Class FieldListDetailsEntityFormDisplayEditForm.
 *
 * @package Drupal\field_list_details\Form
 */
class FieldListDetailsEntityFormDisplayEditForm extends EntityFormDisplayEditForm {

  /**
   * {@inheritdoc}
   */
  protected function buildFieldRow(FieldDefinitionInterface $field_definition, array $form, FormStateInterface $form_state) {
    $field_row = parent::buildFieldRow($field_definition, $form, $form_state);

    // Remove plain_text field label and add back as part of details template
    unset($field_row['human_name']['#plain_text']);

    $collection = new FieldListDetailsCollection($field_definition);

    $field_row['human_name'] =  [
      '#theme' => 'field_list_details_list',
      '#label' => $field_definition->getLabel(),
      '#details' => $collection->getDetails(),
      '#attributes' => [
        'class' => ['field-list-details-list'],
      ],
    ];

    return $field_row;
  }

}
