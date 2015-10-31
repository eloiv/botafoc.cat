<?php

/**
 * @file
 * Contains functions used for content migration.
 */

use Drupal\Core\Database\Database;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\MigrateMessage;

/**
 * Implements hook_modules_installed().
 */
function multiversion_modules_installed($modules) {
  if (in_array('multiversion', $modules)) {
    // Apply updates. We need to run this before user migration.
    \Drupal::service('entity.definition_update_manager')->applyUpdates();

    // Install required modules for migration.
    $dependencies = array('migrate', 'migrate_drupal', 'file');
    foreach ($dependencies as $module) {
      if (\Drupal::moduleHandler()->moduleExists($module)) {
        unset($dependencies['$module']);
      }
    }
    \Drupal::service('module_installer')->install($dependencies, TRUE);

    $batch = array(
      'title' => t('Change user storage class and migrate content to the new storage.'),
      'operations' => array(
        array('_multiversion_make_user_entity_type_multiversionable', array()),
      ),
      'init_message' => t('Executing migration.'),
      'finished' => '_multiversion_migration_finished',
    );
    batch_set($batch);

    $system_path = \Drupal::service('path.current')->getPath();
    if ($system_path != '/admin/modules/list/confirm' && $system_path != '/admin/modules') {
      $batch =& batch_get();
      $batch['progressive'] = FALSE;
      batch_process();
    }
  }
}

/**
 * Makes user content type multiversionable.
 *
 * Delete all users and applies updates to make user entity type multiversionable.
 */
function _multiversion_make_user_entity_type_multiversionable() {
  // Adds database connection information for migration.
  $connection_info = Database::getConnectionInfo('default');
  foreach ($connection_info as $target => $value) {
    $connection_info[$target]['prefix'] = array(
      'default' => $value['prefix']['default'],
    );
  }
  Database::addConnectionInfo('migrate', 'default', $connection_info['default']);

  $context['message'] = t('Migrate users from database to json file.');
  $migration = entity_load('migration', 'migrate_users_from_drupal8_to_json');
  $message = new MigrateMessage();
  $executable = new MigrateExecutable($migration, $message);
  $executable->import();

  $context['message'] = t('Update the user entity type.');
  $entity_manager = \Drupal::entityManager();
  $entity_type = $entity_manager->getDefinition('user');
  $entity_type_id = $entity_type->id();
  $uids = db_select('users_field_data', 'u')
    ->fields('u', array('uid'))
    ->execute()
    ->fetchCol('uid');
  $entities = entity_load_multiple('user', array_values($uids));
  // Delete all users from the database.
  $entity_manager->getStorage($entity_type_id)->delete($entities);

  // This configuration is used when checking supported entity types. After user
  // migration to the json file we set 'user' as a supported entity type.
  \Drupal::state()->set('multiversion_user_migration_to_json_done', TRUE);

  // Apply updates.
  if (!$entity_manager->getStorage($entity_type_id)->hasData()) {
    \Drupal::service('entity.definition_update_manager')->applyUpdates();
  }

  $context['message'] = t('Migrate users from json file to database.');
  $migration = entity_load('migration', 'migrate_users_from_json_to_drupal8');
  $executable = new MigrateExecutable($migration, $message);
  $executable->import();
}

/**
 * Implements callback_batch_finished().
 *
 * @param bool $success
 *   A boolean indicating whether the re-build process has completed.
 * @param array $results
 *   An array of results information.
 * @param array $operations
 *   An array of function calls (not used in this function).
 */
function _multiversion_migration_finished($success, $results, $operations) {
  if ($success) {
    drupal_set_message(t('Successfully migrated users to the new storage.'));
  }
  else {
    drupal_set_message(t('User migration to the new storage failed.'), 'error');
  }
}
