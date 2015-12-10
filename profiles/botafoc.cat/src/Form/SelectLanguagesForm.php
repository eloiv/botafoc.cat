<?php

/**
 * @file
 * Contains \Drupal\Core\Installer\Form\SelectLanguagesForm.
 */

namespace Drupal\botafoc\Form;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\UserAgent;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManager;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the language selection form.
 */
class SelectLanguagesForm extends FormBase {

  public function getFormId() {
    return 'install_select_languages_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $install_state = NULL) {
    if (count($install_state['translations']) > 1) {
      $files = $install_state['translations'];
    }
    else {
      $files = array();
    }
    $standard_languages = LanguageManager::getStandardLanguageList();
    $select_options = array();
    $browser_options = array();

    $form['#title'] = 'Choose languages';

    // Build a select list with language names in native language for the user
    // to choose from. And build a list of available languages for the browser
    // to select the language default from.
    // Select lists based on all standard languages.
    foreach ($standard_languages as $langcode => $language_names) {
      $select_options[$langcode] = $language_names[1];
      $browser_options[$langcode] = $langcode;
    }
    // Add languages based on language files in the translations directory.
    if (count($files)) {
      foreach ($files as $langcode => $uri) {
        $select_options[$langcode] = isset($standard_languages[$langcode]) ? $standard_languages[$langcode][1] : $langcode;
        $browser_options[$langcode] = $langcode;
      }
    }
    asort($select_options);
    $request = Request::createFromGlobals();
    $browser_langcode = UserAgent::getBestMatchingLangcode($request->server->get('HTTP_ACCEPT_LANGUAGE'), $browser_options);
    $form['langcode'] = array(
      '#type' => 'select',
      '#title' => 'Choose default language',
      '#title_display' => 'before',
      '#options' => $select_options,
      // Use the browser detected language as default or English if nothing found.
      '#default_value' => !empty($browser_langcode) ? $browser_langcode : 'en',
    );
    $link_to_english = install_full_redirect_url(array('parameters' => array('langcode' => 'en')));
    $form['help'] = array(
      '#type' => 'item',
      // #markup is XSS admin filtered which ensures unsafe protocols will be
      // removed from the url.
      '#markup' => '<p>Translations will be downloaded from the <a href="http://localize.drupal.org">Drupal Translation website</a>. If you do not want this, select <a href="' . $link_to_english . '">English</a>.</p>',
      '#states' => array(
        'invisible' => array(
          'select[name="langcode"]' => array('value' => 'en'),
        ),
      ),
    );

    $form['langcodes'] = array(
      '#type' => 'select',
      '#title' => 'Choose another languages',
      '#title_display' => 'before',
      '#options' => $select_options,
      '#multiple' => TRUE,
      '#description' => 'Select another languages if your site is multilingual',
    );

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] =  array(
      '#type' => 'submit',
      '#value' => 'Save and continue',
      '#button_type' => 'primary',
    );

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $build_info = $form_state->getBuildInfo();
    $build_info['args'][0]['parameters']['langcode'] = $form_state->getValue('langcode');
    $build_info['args'][0]['parameters']['langcodes'] = $form_state->getValue('langcodes');
    $form_state->setBuildInfo($build_info);
  }

}
