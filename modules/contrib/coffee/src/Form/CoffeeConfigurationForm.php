<?php

/**
 * @file
 * Contains \Drupal\coffee\Form\CoffeeConfigurationForm.
 */

namespace Drupal\coffee\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Coffee for this site.
 */
class CoffeeConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'coffee_configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'coffee.configuration',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('coffee.configuration');

    $menus = menu_ui_get_menus();

    if (!empty($menus)) {
      $form['coffee_menus'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Menus to include'),
        '#description' => $this->t('Select the menus that should be used by Coffee to search.'),
        '#options' => $menus,
        '#required' => TRUE,
        '#default_value' => $config->get('coffee_menus'),
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('coffee.configuration')
      ->set('coffee_menus', array_filter($values['coffee_menus']))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
