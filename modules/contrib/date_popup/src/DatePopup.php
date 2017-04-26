<?php

namespace Drupal\date_popup;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\Date;

/**
 * The date popup views filter plugin.
 */
class DatePopup extends Date {

  /**
   * {@inheritdoc}
   */
  public function buildExposedForm(&$form, FormStateInterface $form_state) {
    parent::buildExposedForm($form, $form_state);
    if (!empty($this->options['expose']['identifier'])) {
      $form[$this->options['expose']['identifier']]['#type'] = 'date';
    }
  }

}
