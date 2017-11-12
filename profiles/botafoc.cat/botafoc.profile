<?php

use Drupal\Core\Config\StorageComparer;
use Drupal\Core\Config\ConfigImporter;
use Drupal\Core\Config\ConfigImporterException;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Language\LanguageInterface;
use Drupal\language\Entity\ConfigurableLanguage;

include_once __DIR__ . '/src/Form/SelectLanguagesForm.php';
include_once __DIR__ . '/src/Form/SelectConfigurationForm.php';

function botafoc_install_tasks_alter(&$tasks, $install_state) {
  $all_tasks = $tasks;
  $tasks = array();

  // Define first task Select languages
  $tasks['install_select_languages'] = array(
    'display_name' => t('Select languages'),
    'run' => INSTALL_TASK_RUN_IF_REACHED,
  );

  // Define second task Select configuration
  $tasks['install_select_configuration'] = array(
    'display_name' => t('Select configuration'),
  );

  // Added task after task key
  foreach($all_tasks as $key => $value) {
    $tasks[$key] = $value;

    if ($key == 'install_profile_modules') {
      $tasks['install_enabled_modules'] = array(
        'display_name' => t('Enabled modules'),
      );
    }

    if ($key == 'install_import_translations') {
      $tasks['install_initial_configuration'] = array(
        'display_name' => t('Install configuration'),
      );
    }

    // Is required to install_configuration executed after install_import_translations.
    if ($key == 'install_finished') {
      $tasks['install_last_configuration'] = array(
        'display_name' => t('Last configuration'),
      );
    }
  }

  // Disable tasks
  $tasks['install_select_language'] = array(
    'display' => 0,
  );
  //var_dump('<pre>',$tasks);
}

function install_select_languages(&$install_state) {
  // Find all available translation files.
  $files = install_find_translations();
  $install_state['translations'] += $files;

  // If a valid language code is set, continue with the next installation step.
  // When translations from the localization server are used, any language code
  // is accepted because the standard language list is kept in sync with the
  // languages available at http://localize.drupal.org.
  // When files from the translation directory are used, we only accept
  // languages for which a file is available.
  if (!empty($install_state['parameters']['langcode'])) {
    $standard_languages = LanguageManager::getStandardLanguageList();
    $langcode = $install_state['parameters']['langcode'];
    if ($langcode == 'en' || isset($files[$langcode]) || isset($standard_languages[$langcode])) {
      $install_state['parameters']['langcode'] = $langcode;
      return;
    }
  }

  if (empty($install_state['parameters']['langcode'])) {
    // If we are performing an interactive installation, we display a form to
    // select a right language. If no translation files were found in the
    // translations directory, the form shows a list of standard languages. If
    // translation files were found the form shows a select list of the
    // corresponding languages to choose from.
    if ($install_state['interactive']) {
      return install_get_form('Drupal\botafoc\Form\SelectLanguagesForm', $install_state);
    }
    // If we are performing a non-interactive installation. If only one language
    // (English) is available, assume the user knows what he is doing. Otherwise
    // throw an error.
    else {
      if (count($files) == 1) {
        $install_state['parameters']['langcode'] = current(array_keys($files));
        return;
      }
      else {
        throw new InstallerException(t('You must select a language to continue the installation.'));
      }
    }
  }
}

function install_select_configuration(&$install_state) {
  if (empty($install_state['parameters']['configuration'])) {
    return install_get_form('Drupal\botafoc\Form\SelectConfigurationForm', $install_state);
  }
}

function install_enabled_modules(&$install_state) {
  if (!empty($_GET['langcodes'])) {
    \Drupal::service('module_installer')->install(['lang_dropdown']);
    \Drupal::service('module_installer')->install(['hidden_language']);
  }
}

function install_initial_configuration(&$install_state) {
  if (!empty($_GET['langcodes'])) {
    $langcodes = $_GET['langcodes'];
    foreach($langcodes as $key => $value) {
      if ($key != $_GET['langcode']) {
        ConfigurableLanguage::createFromLangcode($key)->save();
      }
    }
  }
}

function install_last_configuration(&$install_state) {
  if(!empty($_GET['langcode'])) {
    $user = \Drupal\user\Entity\User::load(1);
    $user->set("preferred_langcode", $_GET['langcode']);
    $user->set("preferred_admin_langcode", $_GET['langcode']);
    $user->save();
  }

  if(!empty($_GET['langcodes'])) {
    \Drupal::service('module_installer')->install(['content_translation']);
    \Drupal::service('module_installer')->install(['config_translation']);
  }

  drupal_flush_all_caches();
}

function botafoc_form_alter(&$form, \Drupal\Core\Form\FormStateInterface $form_state, $form_id) {
  if ($form_id == 'install_configure_form') {
    $form['#attributes']['novalidate'] = 'novalidate';
    $form['site_information']['#type'] = 'details';
    $form['site_information']['#open'] = TRUE;
    $form['site_information']['#weight'] = 1;
    $form['admin_account']['#type'] = 'details';
    $form['admin_account']['#open'] = TRUE;
    $form['admin_account']['#weight'] = 2;

    if(!empty($_GET['editor'])) {
      $form['editor_create']['#title'] = 'Create a user editor?';
      $form['editor_create']['#type'] = 'details';
      $form['editor_create']['#open'] = TRUE;
      $form['editor_create']['#weight'] = 3;
      $form['editor_create']['check'] = array(
        '#type' =>'checkbox',
        '#title' => t('Do you want to create a user editor?'),
      );
      $form['editor_account']['#title'] = 'Editor account';
      $form['editor_account']['#type'] = 'details';
      $form['editor_account']['#open'] = TRUE;
      $form['editor_account']['#weight'] = 4;
      $form['editor_account']['editor'] = $form['admin_account']['account'];
      $form['editor_account']['editor']['name']['#required'] = FALSE;
      $form['editor_account']['editor']['pass']['#required'] = FALSE;
      $form['editor_account']['editor']['mail']['#required'] = FALSE;

      $form['editor_account']['#states'] = array(
        'visible' => array(
          ':input[name="check"]' => array('checked' => TRUE),
        ),
        'required' => array(
          ':input[name="check"]' => array('checked' => FALSE),
        ),
      );

      $form['#submit'][] = 'editor_user_save';
      $form['actions']['submit']['#validate'][] = 'editor_user_validate';
    }

    $form['regional_settings']['#type'] = 'details';
    $form['regional_settings']['#weight'] = 5;
    $form['update_notifications']['#type'] = 'details';
    $form['update_notifications']['#weight'] = 6;
  }
}

function editor_user_save($form, Drupal\Core\Form\FormStateInterface $form_state) {
  if (!empty($form_state->getValue('check'))) {
    $editor_account = $form_state->getValue('editor');
    if(!empty($editor_account['name']) && !empty($editor_account['pass'])) {
      $user_editor = \Drupal\user\Entity\User::create();
      // Mandatory.
      $user_editor->setUsername($editor_account['name']);
      $user_editor->setPassword($editor_account['pass']);
      $user_editor->setEmail($editor_account['mail']);
      $user_editor->enforceIsNew();
      $user_editor->addRole('editor');
      $user_editor->activate();
      $user_editor->save();
    }
  }
}

function editor_user_validate($form, Drupal\Core\Form\FormStateInterface $form_state) {

  if (!empty($form_state->getValue('check'))) {
    $editor_account = $form_state->getValue('editor');

    if (empty($editor_account['name'])) {
      $form_state->setErrorByName('editor][name', t('The Editor username field is required.'));
    }
    if (empty($editor_account['pass'])) {
      $form_state->setErrorByName('editor][pass][pass1', t('Editor password field is required.'));
      $form_state->setErrorByName('editor][pass][pass2', t('Editor password field is required.'));
    }
    if (empty($editor_account['pass'])) {
      $form_state->setErrorByName('editor][mail', t('Editor email field is required.'));
    }
  }
}
