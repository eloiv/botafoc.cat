<?php

namespace Drupal\contact_emails\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;
use Drupal\contact_emails\ContactEmails;

/**
 * Class ContactEmailSettingsForm.
 *
 * @package Drupal\contact_emails\Form
 */
class ContactEmailSettingsForm extends FormBase {

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
    return 'contact_email_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $contact_form = '', $id = '') {

    if ($contact_form != 'personal') {
      $email = FALSE;
      if ($id) {
        $email = $this->contactEmails->getEmailById($id);
      }

      $form['id'] = [
        '#type' => 'hidden',
        '#value' => $id,
      ];
      $form['contact_form'] = [
        '#type' => 'hidden',
        '#value' => $contact_form,
      ];
      $form['subject'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Subject'),
        '#maxlength' => 255,
        '#required' => TRUE,
        '#size' => 64,
        '#description' => $this->t('The subject of the email. To add a variable, click within the above field and then click the "Browse available tokens" link below. You will find the fields of your forms within Contact Message. Note that tokens that produce html markup are not supported for an email subject.'),
        '#element_validate' => ['token_element_validate'],
        '#token_types' => ['contact_message'],
        '#default_value' => ($email ? $email['subject'] : ''),
      ];

      $body = $this->getBody(($email['body'] ? $email['body'] : ''));
      $form['body'] = [
        '#type' => 'text_format',
        '#title' => $this->t('Body'),
        '#required' => TRUE,
        '#description' => $this->t('The body of the email. To add a variable, click within the above field and then click the "Browse available tokens" link below. You will find the fields of your forms within Contact Message.'),
        '#element_validate' => ['token_element_validate'],
        '#token_types' => ['contact_message'],
        '#default_value' => ($body ? $body['value'] : ''),
      ];
      if ($body['format']) {
        $form['body']['#format'] = $body['format'];
      }

      $form['token_help'] = array(
        '#theme' => 'token_tree_link',
        '#token_types' => ['contact_message'],
      );
      $form['recipient_type'] = [
        '#type' => 'select',
        '#title' => $this->t('Recipient type'),
        '#description' => $this->t('Choose how to determine who the email recipient(s) should be.'),
        '#options' => [
          'submitter' => $this->t('Send this email to the submitter of the form'),
          'field' => $this->t('Send this email to the value of a specific field in the form'),
          'reference' => $this->t('Send this email to the value of a specific field in an entity reference'),
          'manual' => $this->t('Send this email to specific email addresses'),
        ],
        '#default_value' => ($email ? $email['recipient_type'] : 'manual'),
      ];

      // Fields for field recipient type.
      $fields = $this->contactEmails->getContactFormFields($contact_form, 'email');
      if ($fields) {
        $form['recipient_field'] = [
          '#type' => 'select',
          '#title' => $this->t('Recipient field'),
          '#description' => $this->t('Send the email to the value of this field.'),
          '#options' => $fields,
          '#states' => [
            'visible' => [
              ':input[name="recipient_type"]' => ['value' => 'field'],
            ],
          ],
          '#default_value' => ($email ? $email['recipient_field'] : ''),
        ];
      }
      else {
        $form['recipient_field'] = [
          '#type' => 'item',
          '#title' => $this->t('No fields available'),
          '#description' => $this->t('You must have at least one email field available to use this option.'),
          '#states' => [
            'visible' => [
              ':input[name="recipient_type"]' => ['value' => 'field'],
            ],
          ],
        ];
      }

      // Fields for field recipient type.
      $referenced_fields = $this->contactEmails->getContactFormFields($contact_form, 'entity_reference');
      if ($referenced_fields) {
        $form['recipient_reference'] = [
          '#type' => 'select',
          '#title' => $this->t('Recipient field in reference'),
          '#description' => $this->t('Send the email to the value of this field.'),
          '#options' => $referenced_fields,
          '#states' => [
            'visible' => [
              ':input[name="recipient_type"]' => ['value' => 'reference'],
            ],
          ],
          '#default_value' => ($email ? $email['recipient_reference'] : ''),
        ];
      }
      else {
        $form['recipient_reference'] = [
          '#type' => 'item',
          '#title' => $this->t('No fields available'),
          '#description' => $this->t('You must have at least one referenced entity available that has at least one email field available to use this option.'),
          '#states' => [
            'visible' => [
              ':input[name="recipient_type"]' => ['value' => 'reference'],
            ],
          ],
        ];
      }

      // Fields for manual recipient type.
      $form['recipients'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Recipients'),
        '#description' => $this->t('Enter one or more recipients, separating multiple by commas.'),
        '#states' => [
          'visible' => [
            ':input[name="recipient_type"]' => ['value' => 'manual'],
          ],
        ],
        '#default_value' => ($email ? $email['recipients'] : ''),
      ];

      // Reply-to settings.
      $form['reply_to_type'] = [
        '#type' => 'select',
        '#title' => $this->t('Reply-to type'),
        '#description' => $this->t('Choose how to determine where email replies should be sent.'),
        '#options' => [
          'default' => $this->t('Email replies should go to the website from email address (default)'),
          'field' => $this->t('Email replies should go to the value of a specific field in the form'),
          'email' => $this->t('Email replies should go to a specific email address'),
        ],
        '#default_value' => ($email ? $email['reply_to_type'] : 'default'),
      ];

      // Fields for reply to field type.
      if ($fields) {
        $form['reply_to_field'] = [
          '#type' => 'select',
          '#title' => $this->t('Reply-to field'),
          '#description' => $this->t('Email replies should go to the value of this field. Please note that if the field is not required and is left blank by the user, the reply-to will be set as the default website email instead.'),
          '#options' => $fields,
          '#states' => [
            'visible' => [
              ':input[name="reply_to_type"]' => ['value' => 'field'],
            ],
          ],
          '#default_value' => ($email ? $email['reply_to_field'] : ''),
        ];
      }
      else {
        $form['reply_to_field'] = [
          '#type' => 'item',
          '#title' => $this->t('No fields available'),
          '#description' => $this->t('You must have at least one email field available to use this option.'),
          '#states' => [
            'visible' => [
              ':input[name="reply_to_type"]' => ['value' => 'field'],
            ],
          ],
        ];
      }

      // Fields for email reply-to type.
      $form['reply_to_email'] = [
        '#type' => 'email',
        '#title' => $this->t('Reply-to email'),
        '#description' => $this->t('Enter the email address replies to this email should be sent to.'),
        '#states' => [
          'visible' => [
            ':input[name="reply_to_type"]' => ['value' => 'email'],
          ],
        ],
        '#default_value' => ($email ? $email['reply_to_email'] : ''),
      ];

      // Disable email option.
      $form['disabled'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Disable this email'),
        '#description' => $this->t('Check this box to disable sending of this email.'),
        '#default_value' => ($email ? $email['disabled'] : ''),
      ];

      $form['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Save email settings'),
      ];

    }
    else {

      $form['message'] = [
        '#plain_text' => $this->t('The Contact Emails module does not cover the personal contact form.'),
      ];
    }

    return $form;
  }

  /**
   * Get body potentially with format.
   */
  protected function getBody($body) {
    if ($body) {
      $data = @unserialize($body);
      if ($data !== FALSE) {
        $body = $data;
        if (is_array($body)) {
          return $body;
        }
      }
    }
    return [
      'value' => $body,
      'format' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // Require recipients if manual recipient type.
    if ($values['recipient_type'] == 'manual' && !$values['recipients']) {
      $form_state->setErrorByName('recipients', t('Please add at least one recipient.'));
    }

    // Require field if field recipient type.
    if ($values['recipient_type'] == 'field' && !$values['recipient_field']) {
      $form_state->setErrorByName('recipient_field', t('Please select a field with the email type to use this recipient type.'));
    }

    // Require reference field if reference recipient type.
    if ($values['recipient_type'] == 'reference' && !$values['recipient_reference']) {
      $form_state->setErrorByName('recipient_referene', t('Please select a referenced field with the email type to use this recipient type.'));
    }

    // Require field if field reply-to type.
    if ($values['reply_to_type'] == 'field' && !$values['reply_to_field']) {
      $form_state->setErrorByName('reply_to_field', t('Please select a field with the email type to use this reply-to type.'));
    }

    // Require email if email reply-to type.
    if ($values['reply_to_type'] == 'email' && !$values['reply_to_email']) {
      $form_state->setErrorByName('reply_to_email', t('Please enter a reply-to email address to use this reply-to type.'));
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $values = $form_state->getValues();
    $fields = [
      'contact_form' => $values['contact_form'],
      'subject' => $values['subject'],
      'body' => serialize($values['body']),
      'recipients' => $values['recipients'],
      'recipient_type' => $values['recipient_type'],
      'recipient_field' => $values['recipient_field'],
      'recipient_reference' => $values['recipient_reference'],
      'reply_to_type' => $values['reply_to_type'],
      'reply_to_field' => $values['reply_to_field'],
      'reply_to_email' => $values['reply_to_email'],
      'disabled' => $values['disabled'],
    ];

    // Before we take any action, determine if we already have any
    // contact emails in place.
    $has_emails = $this->contactEmails->contactFormHasEmails($values['contact_form']);

    if ($values['id'] != 'new') {

      // Update existing.
      $this->database->update('contact_message_email_settings')
        ->fields($fields)
        ->condition('id', $values['id'])
        ->execute();
      drupal_set_message(t('The email has been successfully updated.'), 'status');
      $form_state->setRedirect('contact_emails.contact_emails_controller_list', [
        'contact_form' => $values['contact_form'],
      ]);

      // If this is the first email, warn that the Drupal Core contact email
      // has been disabled.
      if (!$has_emails) {
        drupal_set_message(t('The default contact email from the form settings has been disabled and your new email has replaced it.'), 'warning');
      }

    }
    else {

      // Create new.
      $values['id'] = $this->database->insert('contact_message_email_settings')
        ->fields($fields)
        ->execute();
      drupal_set_message(t('The email has been successfully created.'), 'status');
      $form_state->setRedirect('contact_emails.contact_emails_controller_list', [
        'contact_form' => $values['contact_form'],
      ]);

    }

    // Rebuild cache.
    $this->contactEmails->rebuildCache();
  }

}
