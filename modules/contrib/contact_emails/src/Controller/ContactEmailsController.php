<?php

namespace Drupal\contact_emails\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Driver\mysql\Connection;
use Drupal\Core\Url;
use Drupal\contact_emails\ContactEmails;

/**
 * Class ContactEmailsController.
 *
 * @package Drupal\contact_emails\Controller
 */
class ContactEmailsController extends ControllerBase {

  /**
   * Drupal\Core\Database\Driver\mysql\Connection definition.
   *
   * @var \Drupal\Core\Database\Driver\mysql\Connection
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
   * Select form.
   */
  public function selectContactForm() {
    $build = [];

    $build['contact_forms'] = array(
      '#type' => 'table',
      '#header' => [t('Form name'), t('Operations')],
      '#empty' => t('There are no contact forms yet.'),
    );

    $contact_forms = \Drupal::entityManager()->getBundleInfo('contact_message');
    if ($contact_forms) {
      foreach ($contact_forms as $contact_form => $data) {
        $row = [];

        // Subject.
        $row['name'] = [
          '#markup' => $data['label'],
        ];

        // Operations.
        if ($contact_form != 'personal') {
          $links = [];
          $links['edit'] = [
            'title' => t('Manage emails'),
            'url' => Url::fromRoute('contact_emails.contact_emails_controller_list', [
              'contact_form' => $contact_form,
            ]),
          ];
          $row[] = [
            'operations' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ];
        }
        else {
          $row[] = [
            '#plain_text' => $this->t('Not applicable'),
          ];
        }

        $build['contact_forms'][] = $row;
      }
    }
    return $build;
  }

  /**
   * List emails.
   */
  public function listEmails($contact_form) {
    $build = [];

    // Determine informational text based on whether emails are enabled or not.
    if ($this->contactEmails->contactFormHasEmails($contact_form)) {
      $status = $this->t('Using the below emails');
      $info = $this->t('The default email for your form has been disabled and the emails listed below are used instead.');
    }
    else {
      $status = $this->t('Using the default contact email');
      $info = $this->t('The default email for your form from your form settings is in use. Add an email to override it.');
    }

    // Output the informational text.
    $build['status'] = [
      '#type' => 'details',
      '#title' => $this->t('Current email status: @status', [
        '@status' => $status,
      ]),
    ];
    $build['status']['info'] = [
      '#markup' => $info,
    ];

    $build['emails'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('ID'),
        $this->t('Subject'),
        $this->t('Recipients'),
        $this->t('Active'),
        $this->t('Operations'),
      ],
      '#empty' => t('There are no emails yet for this form.'),
    ];

    $include_disabled = TRUE;
    $contact_emails = \Drupal::service('contact_emails.helper');
    $emails = $contact_emails->getEmailsByContactForm($contact_form, $include_disabled);
    if ($emails) {
      foreach ($emails as $email) {

        $row = [];

        // ID.
        $row['id'] = [
          '#markup' => $email->id,
        ];

        // Subject.
        $row['subject'] = [
          '#markup' => $email->subject,
        ];

        // Recipients.
        switch ($email->recipient_type) {
          case 'submitter':
            $row['recipients'] = [
              '#markup' => $this->t('[The submitter of the form]'),
            ];
            break;

          case 'field':
            $fields = $this->contactEmails->getContactFormFields($contact_form, 'email');
            if (isset($fields[$email->recipient_field])) {
              $field_label = $fields[$email->recipient_field];
            }
            else {
              $field_label = $this->t('*Unknown or deleted field*');
            }
            $row['recipients'] = [
              '#markup' => $this->t('[The value of the "@field" field]', [
                '@field' => $field_label,
              ]),
            ];
            break;

          case 'reference':
            $fields = $this->contactEmails->getContactFormFields($contact_form, 'entity_reference');
            if (isset($fields[$email->recipient_reference])) {
              $field_label = $fields[$email->recipient_reference];
            }
            else {
              $field_label = $this->t('*Unknown or deleted field*');
            }
            $row['recipients'] = [
              '#markup' => $this->t('[The value of the "@field" field]', [
                '@field' => $field_label,
              ]),
            ];
            break;

          case 'manual':
          default:
            $row['recipients'] = [
              '#markup' => implode(', ', $email->recipients),
            ];
            break;
        }

        // Active.
        $row['active'] = [
          '#markup' => ($email->disabled ? t('No') : t('Yes')),
        ];

        // Operations.
        $links = [];
        $links['edit'] = [
          'title' => t('Edit'),
          'url' => Url::fromRoute('entity.contact_form.email_settings_form', [
            'contact_form' => $contact_form,
            'id' => $email->id,
          ]),
        ];
        $links['delete'] = [
          'title' => t('Delete'),
          'url' => Url::fromRoute('contact_emails.contact_email_delete_form', [
            'contact_form' => $contact_form,
            'id' => $email->id,
          ]),
        ];
        $row[] = [
          'operations' => [
            '#type' => 'operations',
            '#links' => $links,
          ],
        ];

        $build['emails'][] = $row;
      }
    }

    $build['new'] = [
      '#markup' => $this->getUpsertLink($contact_form),
    ];
    return $build;
  }

  /**
   * Get the update or insert link.
   *
   * @param string $contact_form
   *   The contact form machine name.
   * @param int $id
   *   The email id or 'new'.
   *
   * @return string
   *   The rendered link.
   */
  private function getUpsertLink($contact_form, $id = 'new') {
    // Generate link from route.
    $url = new Url('entity.contact_form.email_settings_form', [
      'id' => $id,
      'contact_form' => $contact_form,
    ]);

    // Add class to link.
    $link_options = [
      'attributes' => [
        'class' => [
          'button',
        ],
      ],
    ];
    $url->setOptions($link_options);

    // Render link.
    if ($id == 'new') {
      $label = $this->t('Create email');
    }
    else {
      $label = $this->t('Edit email');
    }
    $link = $this->l($label, $url);

    return $link;
  }

}
