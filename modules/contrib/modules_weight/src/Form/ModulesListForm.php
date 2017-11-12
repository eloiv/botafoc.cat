<?php

namespace Drupal\modules_weight\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;

/**
 * Builds the form to configure the Modules weight.
 */
class ModulesListForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'modules_weight_modules_list';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // The table header.
    $header = [
      $this->t('Name'),
      [
        'data' => $this->t('Description'),
        // Hidding the description on narrow width devices.
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      $this->t('Weight'),
      [
        'data' => $this->t('Package'),
        // Hidding the description on narrow width devices.
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
    ];

    // The table.
    $form['modules'] = [
      '#type' => 'table',
      '#header' => $header,
      '#sticky' => TRUE,
    ];

    // Getting the module list.
    $modules = _modules_weight_modules_list();
    // Iterate over each module.
    foreach ($modules as $filename => $module) {
      // The rows info.
      // Module name.
      $form['modules'][$filename]['name'] = [
        '#markup' => $module->info['name'],
      ];
      // Module description.
      $form['modules'][$filename]['description'] = [
        '#markup' => $module->info['description'],
      ];
      // Module weight.
      $form['modules'][$filename]['weight'] = [
        '#type' => 'weight',
        '#default_value' => $module->weight,
        '#delta' => _modules_weight_prepare_delta($module->weight),
      ];
      // Module old weight value, used to see if we need to update the weight.
      $form['old_weight_value'][$filename] = [
        '#type' => 'hidden',
        '#value' => $module->weight,
      ];
      // Module package.
      $form['modules'][$filename]['package'] = [
        '#markup' => $module->info['package'],
      ];
    }
    // Making $form['old_weight_value'] non flat.
    // Read more: https://www.drupal.org/docs/7/api/form-api/tree-and-parents .
    $form['old_weight_value']['#tree'] = TRUE;

    // The form button.
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Save Changes'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Variable to see if we have uptaded some module weight.
    $update = FALSE;
    // The modules information.
    $modules_info = $form_state->getValue('modules');
    // The old values information.
    $old_weight_value = $form_state->getValue('old_weight_value');

    foreach ($modules_info as $module => $values) {
      // Checking if a value has changed.
      if ($modules_info[$module]['weight'] != $old_weight_value[$module]) {
        // Setting the new weight.
        module_set_weight($module, $values['weight']);
        $update = TRUE;
      }
    }
    // Printing the update message.
    if ($update) {
      drupal_set_message($this->t('The modules weight was updated.'));
    }
  }

}
