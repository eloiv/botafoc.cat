<?php

namespace Drupal\contact_emails\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Url;
use Drupal\contact_emails\ContactEmails;

/**
 * Class ContactEmailDeleteForm.
 *
 * @package Drupal\contact_emails\Form
 */
class ContactEmailDeleteForm extends ConfirmFormBase {

  /**
   * Drupal\Core\Database\Connection definition.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Drupal\contact_emails\ContactEmails definition.
   *
   * @var \Drupal\contact_emails\ContactEmails;
   */
  protected $contactEmails;

  /**
   * {@inheritdoc}
   */
  public function __construct(Connection $database, ContactEmails $contact_emails) {
    $this->database = $database;
    $this->contactEmails = $contact_emails;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('contact_emails.helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'contact_email_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getFormName() {
    return 'confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Do you want to delete this email?');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t('Proceding will permanently delete this email.');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return t('Cancel');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('contact_emails.contact_emails_controller_list_selection');
  }

  /**
   * Build the delete form.
   *
   * @param int $contact_form
   *   The contact form ID from where the item should be deleted.
   * @param int $id
   *   The ID of the item to be deleted.
   *
   * @return array
   *   The form.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $contact_form = '', $id = '') {
    $form['id'] = [
      '#type' => 'hidden',
      '#value' => $id,
    ];
    $form['contact_form'] = [
      '#type' => 'hidden',
      '#value' => $contact_form,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // Run deletion.
    $this->database->delete('contact_message_email_settings')
      ->condition('id', $values['id'])
      ->condition('contact_form', $values['contact_form'])
      ->execute();

    // Rebuild cache.
    $this->contactEmails->rebuildCache();

    // Add message.
    drupal_set_message(t('The email has been successfully removed.'), 'status');

    // If this was the last email, warn that the Drupal Core contact email
    // has been enabled.
    $has_emails = $this->contactEmails->contactFormHasEmails($values['contact_form']);
    if (!$has_emails) {
      drupal_set_message(t('The default contact email from the form settings for this form has been reenabled now that you are no longer managing any emails for it.'), 'warning');
    }

    // Redirect back to list page.
    $form_state->setRedirect('contact_emails.contact_emails_controller_list', [
      'contact_form' => $values['contact_form'],
    ]);
  }

}
