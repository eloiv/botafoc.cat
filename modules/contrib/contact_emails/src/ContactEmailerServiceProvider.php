<?php

namespace Drupal\contact_emails;

use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Utility\Token;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Render\Renderer;
use Drupal\Component\Render\FormattableMarkup;
use Egulias\EmailValidator\EmailValidator;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Mail\MailFormatHelper;

/**
 * Class ContactEmailerServiceProvider.
 *
 * @package Drupal\contact_emails
 */
class ContactEmailerServiceProvider {

  use StringTranslationTrait;

  /**
   * Drupal\Core\Mail\MailManagerInterface definition.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * Drupal\Core\Utility\Token definition.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Drupal\contact_emails\ContactEmails definition.
   *
   * @var \Drupal\contact_emails\ContactEmails
   */
  protected $contactEmails;

  /**
   * Drupal\Core\Session\AccountProxy definition.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * Drupal\Core\Render\Renderer definition.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * Egulias\EmailValidator\EmailValidator definition.
   *
   * @var \Egulias\EmailValidator\EmailValidator;
   */
  protected $emailValidator;

  /**
   * Contact form machine name.
   *
   * @var string
   */
  protected $contactForm;

  /**
   * Contact message entity.
   *
   * @var object
   */
  protected $contactMessage;

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface;
   */
  protected $entityTypeManager;

  /**
   * Constructor.
   */
  public function __construct(
    MailManagerInterface $plugin_manager_mail,
    Token $token,
    ContactEmails $contact_emails,
    AccountProxy $current_user,
    Renderer $renderer,
    EmailValidator $email_validator,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->mailManager = $plugin_manager_mail;
    $this->token = $token;
    $this->contactEmails = $contact_emails;
    $this->currentUser = $current_user;
    $this->renderer = $renderer;
    $this->emailValidator = $email_validator;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Set the contact form.
   *
   * @param object $contactMessage
   *   The contact message.
   */
  public function setContactMessage($contactMessage) {
    $contactForm_object = $contactMessage->getContactForm();
    $this->contactForm = $contactForm_object->id();
    $this->contactMessage = $contactMessage;
  }

  /**
   * Send the emails.
   */
  public function sendEmails() {
    if ($emails = $this->getEmails()) {
      foreach ($emails as $email) {
        $module = 'contact_emails';
        $key = 'contact_emails';
        $to = $this->getTo($email);
        $reply_to = $this->getReplyTo($email);

        // Stop here if we don't know who to send to.
        if (!$to) {
          $error = $this->t('Unable to determine who to send the message to for for email id @id', [
            '@id' => $email->id,
          ]);
          drupal_set_message($error, 'warning', FALSE);
          continue;
        }

        // Subject and body.
        $params['subject'] = $this->setSubject($email->subject);
        $params['message'] = $this->setBody($email->body);

        // Set to html mail by default.
        $params['format'] = 'text/html';

        // Final prep and send.
        $langcode = $this->currentUser->getPreferredLangcode();
        $send = TRUE;
        $this->mailManager->mail($module, $key, $to, $langcode, $params, $reply_to, $send);
      }
    }
  }

  /**
   * Get who to send the email to.
   *
   * @param object $email
   *   The email settings.
   *
   * @return string
   *   The to string to be used by the mail manager.
   */
  protected function getTo($email) {
    $to = [];

    switch ($email->recipient_type) {
      case 'submitter':
        // Send to the submitter of the form.
        $to[] = $this->contactMessage->getSenderMail();
        break;

      case 'field':
        // Send to the value of an email field.
        if ($this->contactMessage->hasField($email->recipient_field)) {
          // Email could potentially be a repeating field.
          $emails = $this->contactMessage->get($email->recipient_field)->getValue();
          $to = $this->addEmailValues($emails, $to);
        }
        break;

      case 'reference':
        // Send to the value of an email field in a reference.
        // Get the reference path, it consists of:
        // [0] contact_message reference field name.
        // [1] handler.
        // [2] bundle.
        // [3] referenced bundle email field name.
        $reference_path = explode('.', $email->recipient_reference);
        if (count($reference_path) != 4) {
          // Something is wrong.
          break;
        }
        $reference_field_name = $reference_path[0];
        $entity_type = $reference_path[1];
        $email_field_name = $reference_path[3];
        if ($this->contactMessage->hasField($reference_field_name)) {

          // Reference could potentially be a repeating field.
          $referenced_entities = $this->contactMessage->get($reference_field_name)->getValue();
          if ($referenced_entities) {
            foreach ($referenced_entities as $referenced_entity) {
              $referenced_entity_id = $referenced_entity['target_id'];
              if ($referenced_entity_id > 0) {
                $storage = $this->entityTypeManager->getStorage($entity_type);
                $entity = $storage->load($referenced_entity_id);
                $emails = $entity->get($email_field_name)->getValue();
                $to = $this->addEmailValues($emails, $to);
              }
            }
          }
        }
        break;

      case 'manual':
      default:
        // Send to the emails added via the manage email settings form. This is
        // already an array.
        $to = $email->recipients;
        break;
    }

    $to = $this->removeInvalidEmails($to);
    return implode(', ', $to);
  }

  /**
   * Get the reply-to email.
   *
   * @param object $email
   *   The email settings.
   *
   * @return string
   *   The to string to be used by the mail manager.
   */
  protected function getReplyTo($email) {
    $reply_to = NULL;

    switch ($email->reply_to_type) {
      case 'field':
        // Send to the value of an email field.
        if ($this->contactMessage->hasField($email->reply_to_field)) {
          // Email could potentially be a repeating field.
          $email = $this->contactMessage->get($email->reply_to_field)->value;
          $reply_to = (is_array($email) ? reset($email) : $email);
        }
        break;

      case 'email':
        // Send to the value of an email field.
        if ($email->reply_to_email) {
          $reply_to = $email->reply_to_email;
        }
        break;

    }
    return $reply_to;
  }

  /**
   * Add email values.
   *
   * @param array $emails
   *   The email field value(s).
   * @param string $to
   *   The existing array of to emails.
   *
   * @return array $to
   *   The modified array of to emails.
   */
  protected function addEmailValues($emails, $to) {
    if ($emails) {
      foreach ($emails as $email) {
        if ($email['value']) {
          $to[] = $email['value'];
        }
      }
    }
    return $to;
  }

  /**
   * Remove invalid emails.
   *
   * @param array $emails
   *   An array of potentially valid emails.
   *
   * @return array
   *   An array of valid emails.
   */
  protected function removeInvalidEmails($emails) {
    $valid_emails = [];
    foreach ($emails as $email) {
      if ($this->emailValidator->isValid($email)) {
        $valid_emails[] = $email;
      }
      else {
        $error = $this->t('The following email does not appear to be valid and was not sent to: @email', [
          '@email' => $email,
        ]);
        drupal_set_message($error, 'warning', FALSE);
      }
    }
    return $valid_emails;
  }

  /**
   * Set the email message subject.
   *
   * @var mixed $body
   *   A string or text format array.
   *
   * @return string
   *   The plain text.
   */
  protected function setSubject($subject) {
    $subject = $this->tokenizeString($subject);

    // Convert any html to plain text.
    $subject = MailFormatHelper::htmlToText($subject);

    // Remove any line breaks as the above method assumes new lines allowed.
    $subject = str_replace("\n", '', $subject);
    return $subject;
  }

  /**
   * Set the email message body.
   *
   * @var mixed $body
   *   A string or text format array.
   *
   * @return string
   *   The rendered html or plain text.
   */
  protected function setBody($body) {
    if (!$body) {
      return '';
    }

    $body = unserialize($body);

    // Render based on text format.
    if (!is_array($body)) {

      // No text format, plain text.
      $body = [
        '#plain_text' => $this->tokenizeString($body),
      ];
    }
    else {

      // Has a text format, process the text.
      $body = [
        '#type' => 'processed_text',
        '#format' => $body['format'],
        '#text' => $this->tokenizeString($body['value']),
      ];
    }

    // Render the body.
    $rendered_body = $this->renderer->render($body);

    // Send FormattableMarkup to ensure SwiftMailer doesn't further escape it.
    return new FormattableMarkup($rendered_body, []);
  }

  /**
   * Apply tokens to body value.
   *
   * @param string $string
   *   The string value such as the subject or body.
   *
   * @return string
   *   The tokenized value.
   */
  protected function tokenizeString($string) {
    $data = [
      'contact_message' => $this->contactMessage,
    ];
    $options = [
      'clear' => TRUE,
    ];
    return $this->token->replace($string, $data, $options);
  }

  /**
   * Get the emails.
   */
  protected function getEmails() {
    $include_disabled = FALSE;
    return $this->contactEmails->getEmailsByContactForm($this->contactForm, $include_disabled);
  }

}
