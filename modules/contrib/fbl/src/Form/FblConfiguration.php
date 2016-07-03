<?php

namespace Drupal\fbl\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class FblConfiguration extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return '_fbl_configuration';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['fbl.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    //Getting the configuration value
    $default_value_config = $this->config('fbl.settings');
    $default_value = $default_value_config->get('field_based_login');
    $form['field_based_login'] = [
      '#type' => 'fieldset',
      '#title' => t('Field based login Configurations'),
      '#collapsible' => FALSE,
      '#tree' => TRUE,
    ];
    // todo : in feature profile 2 should work :
    $entity_type_id = 'user';
    $bundle = 'user';
    $bundleFields = array();
    foreach (\Drupal::entityManager()->getFieldDefinitions($entity_type_id, $bundle) as $field_name => $field_definition) {
      if (!empty($field_definition->getTargetBundle())) {
        if ($field_definition->getType() == 'string' || $field_definition->getType() == 'integer') {
          $bundleFields[$field_name] = $field_definition->getLabel();
        }
      }
    }
    $form['field_based_login']['field'] = [
      '#type' => 'select',
      '#title' => t('Unique field'),
      '#options' => $bundleFields,
      '#empty_option' => '- Select -',
      '#default_value' => isset($default_value['field']) ? $default_value['field'] : '',
      '#description' => t('Unique field to allow users to login with this field. Note : Selected field will become unique filed.'),
    ];

    $form['field_based_login']['allow_user_name'] = [
      '#type' => 'checkbox',
      '#title' => t('Allow login with username'),
      '#default_value' => isset($default_value['allow_user_name']) ? $default_value['allow_user_name'] : 1,
    ];

    $form['field_based_login']['allow_user_email'] = [
      '#type' => 'checkbox',
      '#title' => t('Allow login with E-mail'),
      '#default_value' => isset($default_value['allow_user_email']) ? $default_value['allow_user_email'] : '',
    ];

    $form['field_based_login']['label'] = [
      '#type' => 'textfield',
      '#title' => t('User login form - User name field Label'),
      '#default_value' => isset($default_value['label']) ? $default_value['label'] : '',
      '#description' => t('Ex: Phone / E-mail'),
      '#size' => 60,
      '#maxlength' => 60,
    ];

    // added description field for configuration.
    $form['field_based_login']['field_desc'] = [
      '#type' => 'textfield',
      '#title' => t('User login form - User name field Description'),
      '#default_value' => isset($default_value['field_desc']) ? $default_value['field_desc'] : '',
      '#description' => t('Ex: Provide description for custom login field'),
      '#size' => 60,
      '#maxlength' => 60,
    ];

    $form['#validate'][] = '_fbl_configuration_validate';

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $field_name = $form_state->getValue(['field_based_login', 'field']);
    $allow_user_login_by_name = $form_state->getValue(['field_based_login', 'allow_user_name']);
    //$allow_user_login_by_email = $form_state->getValue(['field_based_login', 'allow_user_email']);
    if (isset($field_name) && empty($field_name) && ($allow_user_login_by_name == 0) && ($allow_user_login_by_name == 0)) {
      $form_state->setErrorByName('field_based_login][field', t('Please select any one of the option to login'));
    }
    $user_count = _fbl_user_count();
    if (isset($field_name) && !empty($field_name)) {
      $field_data_count = _fbl_field_data_count($field_name);
    }
    $entity_type_id = 'user';
    $bundle = 'user';
    foreach (\Drupal::entityManager()->getFieldDefinitions($entity_type_id, $bundle) as $field_name_value => $field_definition) {
      if (!empty($field_definition->getTargetBundle())) {
        if ($field_name_value == $field_name) {
          $is_required = $field_definition->isRequired();
        }
      }
    }
    if (!empty($field_name)) {
      if ($is_required == '') {
        drupal_set_message(t('Selected field is not mandatory.'), 'warning');
      }
      if ($user_count > $field_data_count) {
        drupal_set_message(t('Selected field is not having data for some of the user'), 'warning');
      }
      if (_fbl_check_for_duplicates($field_name)) {
        $form_state->setErrorByName('field_based_login][field', t('Selected field is not unique. There are duplicates found, Please select other field.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('fbl.settings');

    foreach (Element::children($form) as $variable) {
      $config->set($variable, $form_state->getValue($form[$variable]['#parents']));
    }
    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

}

/*
 * Helper function to check duplicate records of user data.
 *
 * @param field_name
 *  Machine name of the user account field.
 */
function _fbl_check_for_duplicates($field_name) {
  $table_name = 'user__' . $field_name;
  $table_column = $field_name . '_value';
  //query to find the number of unique values of the login selected field

  $query = db_select($table_name, 't');
  $query->fields('t', array($table_column));
  $query->groupBy('t.' . $table_column . '');
  $query->condition('t.bundle', 'user');
  $query->addExpression('COUNT(' . $table_column . ')', 'field_count');
  //$query->havingCondition('t.' . $table_column, '1', '>');
  $query->range(0, 1);
  $duplicate_count = $query->execute()->fetchAll();
  foreach ($duplicate_count as $count) {
    $count = $count->field_count;
  }
  // todo : exception handling  ??
  if ($count > 1) {
    return TRUE;
  }
  return FALSE;
}

/**
 * Returns number of user
 * @return string
 */
function _fbl_user_count() {
  $query = db_select('users', 'u');
  $query->fields('u', array('uid'));
  $query->condition('u.uid', '0', '!=');
  $user_count = $query->countQuery()->execute()->fetchField();
  return $user_count;
}

/**
 * @param string $field_name
 *
 * @return integer
 *  Returns how many fields having value
 */
function _fbl_field_data_count($field_name) {
  $table_name = 'user__' . $field_name;
  $table_column = $field_name . '_value';
  $query = db_select($table_name, 'field_value');
  $query->fields('field_value', array($table_column));
  $query->condition('field_value.bundle', 'user');
  $field_data_count = $query->countQuery()->execute()->fetchField();
  return $field_data_count;
}
