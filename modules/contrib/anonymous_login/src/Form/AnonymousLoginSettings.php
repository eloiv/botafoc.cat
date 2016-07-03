<?php

/**
 * @file
 * Contains Drupal\anonymous_login\Form\AnonymousLoginSettings.
 */

namespace Drupal\anonymous_login\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class AnonymousLoginSettings.
 *
 * @package Drupal\anonymous_login\Form
 */
class AnonymousLoginSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'anonymous_login.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'anonymous_login_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('anonymous_login.settings');
    $form['paths'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Page paths'),
      '#default_value' => $config->get('paths'),
      '#description' => $this->t('Enter a list of page paths that will force anonymous users to login before viewing. After logging in, they will be redirected back to the requested page. Enter each path on a different line. Wildcards (*) can be used. Prefix a path with ~ to exclude it from being redirected.'),
    );
    $form['message'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Login message'),
      '#default_value' => $config->get('message'),
      '#description' => $this->t('Optionally provide a message that will be shown to users when they are redirected to login.'),
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('anonymous_login.settings')
      ->set('paths', $form_state->getValue('paths'))
      ->set('message', $form_state->getValue('message'))
      ->save();
  }

}
