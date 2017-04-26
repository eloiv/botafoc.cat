<?php

namespace Drupal\contact_emails;

use Drupal\Core\Database\Connection;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\field\FieldConfigInterface;

/**
 * Class ContactEmails.
 *
 * Provides a number of helper functions to assist in retrieving contact emails
 * and information about contact emails for a specific form.
 *
 * @package Drupal\contact_emails
 */
class ContactEmails {

  /**
   * Drupal\Core\Database\Connection definition.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Drupal\Core\Cache\CacheBackendInterface definition.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Drupal\Core\Entity\EntityFieldManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface;
   */
  protected $entityFieldManager;

  /**
   * Drupal\Core\Entity\EntityTypeBundleInfoInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface;
   */
  protected $entityTypeBundleInfo;

  /**
   * Constructor.
   */
  public function __construct(
    Connection $database,
    CacheBackendInterface $cache,
    EntityFieldManagerInterface $entity_field_manager,
    EntityTypeBundleInfoInterface $entity_type_bundle_info
  ) {
    $this->database = $database;
    $this->cache = $cache;
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * Get emails by contact form id.
   *
   * @param string $contact_form
   *   The contact form machine name.
   * @param bool $include_disabled
   *   Whether to include disabled contact message emails or not.
   *
   * @return array
   *   The rows from the contact message settings.
   */
  public function getEmailsByContactForm($contact_form, $include_disabled = FALSE) {
    $query = $this->database->select('contact_message_email_settings', 'cm');
    $query->fields('cm');
    $query->condition('cm.contact_form', $contact_form);
    if (!$include_disabled) {
      $query->condition('cm.disabled', 0, '=');
    }
    $results = $query->execute()->fetchAllAssoc('id');
    return $this->prepareResults($results);
  }

  /**
   * Check if a contact form id has at least one email (including disabled).
   *
   * @param string $contact_form
   *   The contact form machine name.
   *
   * @return bool
   *   True if the contact form has at least 1 email.
   */
  public function contactFormHasEmails($contact_form) {
    $contact_forms = $this->getContactFormsWithEmails();
    return (in_array($contact_form, $contact_forms) ? TRUE : FALSE);
  }

  /**
   * Get a list of contact forms that have emails.
   *
   * @param bool $from_cache
   *   Whether to load from the cache (if available).
   *
   * @return array
   *   An array of contact form ids that have at least 1 email.
   */
  protected function getContactFormsWithEmails($from_cache = TRUE) {
    $cid = 'contact_emails:contact_forms_with_emails';

    // Load a list of the forms with at least one contact_emails email.
    if ($from_cache && $cache = $this->cache->get($cid)) {
      $contact_forms = $cache->data;
    }
    else {
      $query = $this->database->select('contact_message_email_settings', 'cm');
      $query->fields('cm', ['contact_form']);
      $query->groupBy('contact_form');
      $contact_forms = $query->execute()->fetchCol();
      $this->cache->set($cid, $contact_forms);
    }
    return $contact_forms;
  }

  /**
   * Get an email by id.
   *
   * @param int $id
   *   The email id.
   *
   * @return array
   *   The single email row from the contact message settings.
   */
  public function getEmailById($id) {
    $query = $this->database->select('contact_message_email_settings', 'cm');
    $query->fields('cm');
    $query->condition('cm.id', $id);
    $results = $query->execute()->fetchAssoc();
    return $this->prepareResult($results);
  }

  /**
   * Prepare the results in a more useable format.
   *
   * @param array $results
   *   The contact emails results.
   *
   * @return array
   *   The prepared results.
   */
  protected function prepareResults($results) {
    if (is_array($results)) {
      foreach ($results as $key => $result) {
        $results[$key] = $this->prepareResult($result);
      }
    }
    return $results;
  }

  /**
   * Prepare a single result.
   *
   * @param object $result
   *   The contact emails result.
   *
   * @return object
   *   The prepared result.
   */
  protected function prepareResult($result) {
    if (isset($result->recipients) && $result->recipients) {

      // Remove new lines and carriage returns.
      $result->recipients = preg_replace("/\r|\n/", ",", $result->recipients);

      // Replace semicolons in case instructions are ignored.
      $result->recipients = str_replace(';', ',', $result->recipients);

      // Break apart into array.
      $result->recipients = explode(',', $result->recipients);

      // Trim whitespace.
      $result->recipients = array_map('trim', $result->recipients);

      // Remove empty in case double commas.
      array_filter($result->recipients);

    }
    return $result;
  }

  /**
   * Rebuild any caches.
   *
   * This should be called whenever a contact email is created, updated, or
   * deleted.
   */
  public function rebuildCache() {

    // Force reload of the list of contact forms with emails.
    $this->getContactFormsWithEmails(FALSE);
  }

  /**
   * Get contact form fields by type.
   *
   * @param string $contact_form_id
   *   The ID of the contact form.
   * @param string $field_type
   *   The type of field to get.
   *
   * @return mixed
   *   The array of email field keys.
   */
  public function getContactFormFields($contact_form_id, $field_type) {
    $available_fields = [];

    // Get all field entities attached to the particular contact form.
    $fields = array_filter($this->entityFieldManager->getFieldDefinitions('contact_message', $contact_form_id), function ($field_definition) {
      return $field_definition instanceof FieldConfigInterface;
    });
    if ($fields) {
      foreach ($fields as $field) {
        $type = $field->getType();
        if ($type == $field_type) {
          switch ($type) {
            case 'email':
              $available_fields = $this->getEmailField($available_fields, $field);
              break;

            case 'entity_reference':
              $available_fields = $this->getEntityReferenceEmailFields($available_fields, $field);
              break;
          }
        }
      }
    }
    return $available_fields;
  }

  /**
   * Get contact form fields by type.
   *
   * @param array $available_fields
   *   The already added available fields.
   * @param object $field
   *   The field.
   *
   * @return array
   *   The new array of available fields.
   */
  protected function getEmailField($available_fields, $field) {
    $available_fields[$field->getName()] = $field->getLabel();
    return $available_fields;
  }

  /**
   * Get contact form fields by type.
   *
   * @param array $available_fields
   *   The already added available fields.
   * @param object $field
   *   The field.
   *
   * @return array
   *   The new array of available fields.
   */
  protected function getEntityReferenceEmailFields($available_fields, $field) {
    $settings = $field->getSettings();

    // Get all bundles for given target type and filter by selected target
    // bundles.
    $bundle_info = $this->entityTypeBundleInfo->getBundleInfo($settings['target_type']);
    if (!empty($settings['handler_settings']['target_bundles'])) {
      $bundle_info = array_intersect_key($bundle_info, $settings['handler_settings']['target_bundles']);
    }

    if (!empty($bundle_info)) {
      foreach ($bundle_info as $bundle_name => $bundle) {
        $bundle_fields = array_filter($this->entityFieldManager->getFieldDefinitions($settings['target_type'], $bundle_name), function ($field_definition) {
          return $field_definition instanceof FieldConfigInterface;
        });

        if ($bundle_fields) {
          foreach ($bundle_fields as $bundle_field) {
            $type = $bundle_field->getType();

            // Allow field type email.
            if ($type == 'email') {
              $available_fields[$field->getName() . '.' . $settings['target_type'] . '.' . $bundle_name . '.' . $bundle_field->getName()] = $bundle['label'] . ': ' . $bundle_field->getLabel() . ' (' . $bundle_field->getName() . ')';
            }
          }
        }
      }
    }

    return $available_fields;
  }

}
