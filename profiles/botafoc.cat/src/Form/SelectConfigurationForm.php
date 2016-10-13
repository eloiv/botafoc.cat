<?php

/**
 * @file
 * Contains \Drupal\Core\Installer\Form\SelectConfigurationForm.
 */

namespace Drupal\botafoc\Form;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\UserAgent;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManager;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the Configuration selection form.
 */
class SelectConfigurationForm extends FormBase {

  public function getFormId() {
    return 'install_select_configuration_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $install_state = NULL) {
    $form['#title'] = 'Choose configuration';

    $form['content_types'] = array(
      '#type' => 'details',
      '#title' => t('Content types'),
      '#description' => t('Select the content types you want to create'),
      '#open' => TRUE,
    );

    $form['content_types']['article'] = array(
      '#type' => 'checkbox',
      '#title' => t('Article'),
      '#description' => t('Use <em>articles</em> for time-sensitive content like news, press releases or blog posts.'),
      '#default_value' => FALSE,
    );
    $form['content_types']['page'] = array(
      '#type' => 'checkbox',
      '#title' => t('Page'),
      '#description' => t('Use <em>basic pages</em> for your static content, such as an \'About us\' page.'),
      '#default_value' => TRUE,
    );

    $form['roles'] = array(
      '#type' => 'details',
      '#title' => t('Roles'),
    );

    $form['roles']['editor'] = array(
      '#type' => 'checkbox',
      '#title' => t('Editor'),
      '#description' => t('Add this role if you need role that only create and edit and delete content'),
      '#default_value' => TRUE,
    );
    $form['roles']['administrator'] = array(
      '#type' => 'checkbox',
      '#title' => t('Administrator'),
      '#description' => t('Add this role if you need role that have all permissions'),
      '#default_value' => TRUE,
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
    $this->_move_all_config_files_directory('general');
    $this->_move_all_config_files_directory('seven');
    $this->_move_all_config_files_directory('bfc_admin_theme');
    $this->_move_all_config_files_directory('bootstrap');
    $this->_move_all_config_files_directory('text_formats_editors');
    $this->_move_all_config_files_directory('user');
    $this->_move_all_config_files_directory('linkit');

    if(!empty($_GET['langcodes'])){
      # Enabled multilanguage modules
      $this->_move_all_config_files_directory('multilanguage');
    }

    if ($form_state->getValue('editor') == 1) {
      $this->_move_config_file('user.role.editor.yml', 'roles');
    }
    if ($form_state->getValue('administrator') == 1) {
      $this->_move_config_file('user.role.administrator.yml', 'roles');
    }
    if ($form_state->getValue('article') == 1) {
      $this->_move_all_config_files_directory('article');
    }
    if ($form_state->getValue('page') == 1) {
      $this->_move_all_config_files_directory('page');
    }

    $build_info = $form_state->getBuildInfo();
    $build_info['args'][0]['parameters']['configuration'] = TRUE;
    $form_state->setBuildInfo($build_info);
  }

  public function _move_config_file($file_name, $directory) {
    $optional_dir = 'optional';
    $install_dir = 'install';

    rename(
      getcwd() . '/profiles/botafoc.cat/config/' . $optional_dir . '/'. $directory . '/' .$file_name,
      getcwd() . '/profiles/botafoc.cat/config/' . $install_dir . '/' .$file_name
    );
  }

  public function _move_all_config_files_directory($directory) {
    $srcDir = getcwd() . '/profiles/botafoc.cat/config/optional/' . $directory;
    $destDir = getcwd() . '/profiles/botafoc.cat/config/install';

    if (file_exists($destDir)) {
      if (is_dir($destDir)) {
        if (is_writable($destDir)) {
          if ($handle = opendir($srcDir)) {
            while (false !== ($file = readdir($handle))) {
              if (is_file($srcDir . '/' . $file)) {
                rename($srcDir . '/' . $file, $destDir . '/' . $file);
              }
            }
            closedir($handle);
          } else {
            echo "$srcDir could not be opened.\n";
          }
        } else {
          echo "$destDir is not writable!\n";
        }
      } else {
        echo "$destDir is not a directory!\n";
      }
    } else {
      echo "$destDir does not exist\n";
    }
  }

}
