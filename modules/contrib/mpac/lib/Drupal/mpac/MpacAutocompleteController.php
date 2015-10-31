<?php

/**
 * @file
 * Contains \Drupal\mpac\MpacAutocompleteController.
 */
namespace Drupal\mpac;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\Core\ControllerInterface;

/**
 * Controller routines for mpax autocomplete routes.
 */
class MpacAutocompleteController implements ControllerInterface {

  /**
   * The mpac autocomplete helper class to find matching items.
   *
   * @var \Drupal\mpac\MpacAutocomplete
   */
  protected $mpacAutocomplete;

  /**
   * Constructs an MpacAutocompleteController object.
   *
   * @param \Drupal\mpac\MpacAutocomplete $mpac_autocomplete
   *   The mpac autocomplete helper class to find matching items.
   */
  public function __construct(MpacAutocomplete $mpac_autocomplete) {
    $this->mpacAutocomplete = $mpac_autocomplete;
  }

  /**
   * Implements \Drupal\Core\ControllerInterface::create().
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('mpac.autocomplete')
    );
  }

  /**
   * Returns response for the mpac autocompletion.
   *
   * @param string $type
   *   The type of data to find (i.e. "path" or "shortcut").
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object containing the search string.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the autocomplete suggestions for existing users.
   *
   * @see \Drupal\mpac\MpacAutocomplete::getMatches()
   */
  public function autocompleteItems($type, Request $request) {
    $matches = $this->mpacAutocomplete->getMatches($type, $request->query->get('q'));

    return new JsonResponse($matches);
  }

}
