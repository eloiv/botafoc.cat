<?php

/**
 * @file
 * Contains \Drupal\anonymous_login\AnonymousLoginSubscriber.
 */

namespace Drupal\anonymous_login\EventSubscriber;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\Event;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\State\State;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Path\PathMatcher;

/**
 * Class AnonymousLoginSubscriber.
 *
 * @package Drupal\anonymous_login
 */
class AnonymousLoginSubscriber implements EventSubscriberInterface {

  /**
   * Drupal\Core\Path\CurrentPathStack definition.
   *
   * @var Drupal\Core\Path\CurrentPathStack
   */
  protected $path_current;

  /**
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var Drupal\Core\Config\ConfigFactory
   */
  protected $config_factory;

  /**
   * Drupal\Core\State\State definition.
   *
   * @var Drupal\Core\State\State
   */
  protected $state;

  /**
   * Drupal\Core\Session\AccountProxy definition.
   *
   * @var Drupal\Core\Session\AccountProxy
   */
  protected $current_user;

  /**
   * Drupal\Core\Path\PathMatcher definition.
   *
   * @var Drupal\Core\Path\PathMatcher
   */
  protected $path_matcher;

  /**
   * Constructor.
   */
  public function __construct(CurrentPathStack $path_current, ConfigFactory $config_factory, State $state, AccountProxy $current_user, PathMatcher $path_matcher) {
    $this->path_current = $path_current;
    $this->config_factory = $config_factory;
    $this->state = $state;
    $this->current_user = $current_user;
    $this->path_matcher = $path_matcher;
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('redirect');
    return $events;
  }

  /**
   * Perform the anonymous user redirection, if needed.
   *
   * This method is called whenever the KernelEvents::REQUEST event is
   * dispatched.
   *
   * @param GetResponseEvent $event
   */
  public function redirect(GetResponseEvent $event) {
    // Skip if maintenance mode is enabled.
    if ($this->state->get('system.maintenance_mode')) {
      return;
    }

    // Skip if running from the command-line.
    if (PHP_SAPI === 'cli') {
      return;
    }

    // Skip if no paths are configured for redirecting.
    if (!($paths = $this->paths()) || empty($paths['include'])) {
      return;
    }

    // Skip if the user is not anonymous.
    if (!$this->current_user->isAnonymous()) {
      return;
    }

    // Determine the current path and alias.
    $current = [
      'path' => $this->path_current->getPath(),
      'alias' => \Drupal::request()->getRequestUri(),
    ];
  
    // Ignore PHP file requests.
    if (substr($current['path'], -4) == '.php') {
      return;
    }

    // Ignore the user login page.
    if ($current['path'] == '/user/login') {
      return;
    }

    // Convert the path to the front page token, if needed.
    $current['path'] = ($current['path'] != '/') ? $current['path'] : '<front>';

    // Track if we should redirect.
    $redirect = FALSE;

    // Iterate the current path and alias.
    foreach ($current as &$check) {
      // Remove the leading slash.
      $check = substr($check, 1);

      // Check if there is a trailer slash.
      if (substr($check, -1) == '/') {
        // Remove it.
        $check = substr($check, 0, strlen($check) - 1);
      }

      // Redirect if the path is a match for included paths.
      if ($this->path_matcher->matchPath($check, implode("\n", $paths['include']))) {
        $redirect = TRUE;
      }
      // Do not redirect if the path is a match for excluded paths.
      if ($this->path_matcher->matchPath($check, implode("\n", $paths['exclude']))) {
        $redirect = FALSE;
        // Matching an excluded path is a hard-stop.
        break;
      }
    }

    // See if we're going to redirect.
    if ($redirect) {
      // See if we have a message to display.
      if ($message = $this->config_factory->get('anonymous_login.settings')->get('message')) {
        // @todo: translation?
        // @todo: This does not show after the redirect..
        drupal_set_message($message);
      }
            
      // Redirect to the login, keeping the requested alias as the destination.
      $response = new RedirectResponse('/user/login?destination=' . $current['alias']);
      $response->send();
      exit();
    }
  }

  /**
   * Fetch the paths that should be used when determining when to force
   * anonymous users to login.
   * 
   * @return
   *   An array of paths, keyed by "include", paths that should force a 
   *   login, and "exclude", paths that should be ignored.
   */ 
  public function paths() {  
    // Initialize the paths array.
    $paths = ['include' => [], 'exclude' => []];
    
    // Fetch the stored paths set in the admin settings.
    if ($setting = $this->config_factory->get('anonymous_login.settings')->get('paths')) {
      // Split by each newline.
      $setting = explode("\n", $setting);
  
      // Iterate each path and determine if the path should be included
      // or excluded.
      foreach ($setting as $path) {
        if (substr($path, 0, 1) == '~') {
          $paths['exclude'][] = substr($path, 1);
        }
        else {
          $paths['include'][] = $path;
        }
      }
    }
    
    return $paths;
  }

}
