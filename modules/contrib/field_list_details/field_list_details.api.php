<?php

/**
 * Implements hook_field_list_details_alter().
 *
 * @param \Drupal\field_list_details\FieldListDetailsCollection $collection
 * @param \Drupal\Core\Field\FieldDefinitionInterface $field
 */
function hook_field_list_details_alter($collection, $field) {
  $settings = $field->getThirdPartySettings('some_third_party_module_name');

  if (!empty($settings['my_setting'])) {
    $collection->setDetail('my_setting', t('My Value'), t('My Label'));
  }
}
