<?php

namespace Drupal\dropdown_language\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class WrapperConfig.
 *
 * @package Drupal\dropdown_language\Form
 */
class DropdownLanguageSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dropdown_language_config';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'dropdown_language.setting',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('dropdown_language.setting');
    $wrapper_default = $config->get('wrapper');

    $form['wrapper'] = [
      '#type' => 'radios',
      '#options' => ['1' => $this->t('Yes'), '0' => $this->t('No')],
      '#title' => $this->t('Use Fieldset wrapping around Dropdown'),
      '#default_value' => $wrapper_default,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('dropdown_language.setting')
      ->set('wrapper', $form_state->getValue('wrapper'))
      ->save();
    parent::submitForm($form, $form_state);
    Cache::invalidateTags(['rendered']);
  }

}
