<?php

/**
 * @file
 * Contains \Drupal\multiversion\MultiversionServiceProvider.
 */

namespace Drupal\multiversion;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

/**
 * Defines a service profiler for the multiversion module.
 */
class MultiversionServiceProvider extends ServiceProviderBase {

  public function alter(ContainerBuilder $container) {
    // Override the password_migrate class with a new class.
    try {
      $definition = $container->getDefinition('password_migrate');
      $definition->setClass('Drupal\multiversion\MigratePassword');
    }
    catch (InvalidArgumentException $e) {
      // Do nothing, migrate module is not installed.
    }

    // Override the comment.statistics class with a new class.
    try {
      $definition = $container->getDefinition('comment.statistics');
      $definition->setClass('Drupal\multiversion\CommentStatistics');
    }
    catch (InvalidArgumentException $e) {
      // Do nothing, comment module is not installed.
    }
  }

}
