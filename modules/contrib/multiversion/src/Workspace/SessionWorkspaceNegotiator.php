<?php

/**
 * @file
 * Contains \Drupal\multiversion\Workspace\SessionWorkspaceNegotiator.
 */

namespace Drupal\multiversion\Workspace;

use Drupal\Core\Url;
use Drupal\multiversion\Entity\WorkspaceInterface;
use Symfony\Component\HttpFoundation\Request;

class SessionWorkspaceNegotiator extends WorkspaceNegotiatorBase implements WorkspaceSwitcherInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(Request $request) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getWorkspaceId(Request $request) {
    $workspace_id = $request->query->get('workspace') ?: NULL;
    if (!$workspace_id && isset($_SESSION['workspace'])) {
      // @todo: {@link https://www.drupal.org/node/2597464 Review from a
      // security perspective.}
      $workspace_id = $_SESSION['workspace'];
    }
    return $workspace_id ?: $this->container->getParameter('workspace.default');
  }

  /**
   * {@inheritdoc}
   */
  public function persist(WorkspaceInterface $workspace) {
    $_SESSION['workspace'] = $workspace->id();
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getWorkspaceSwitchLinks(Request $request, Url $url) {
    $links = array();
    $active_workspace_id = $this->workspaceManager->getActiveWorkspace($request)->id();
    $query = array();
    parse_str($request->getQueryString(), $query);

    // If we have an error on the requested page, set links URL to be <front>.
    if (!empty($query['_exception_statuscode'])) {
      if (isset($query['workspace'])) {
        $query = array(
          'workspace' => $query['workspace'],
        );
      }
      $url = URL::fromRoute('<front>');
    }

    $workspaces = $this->workspaceManager->loadMultiple();
    ksort($workspaces);
    foreach ($workspaces as $workspace) {
      // @todo {@link https://www.drupal.org/node/2600382 Access check.}
      $workspace_id = $workspace->id();
      $links[$workspace_id] = array(
        'url' => $url,
        'title' => $workspace_id,
        'query' => $query,
      );
      $links[$workspace_id]['query']['workspace'] = $workspace_id;
      if ($workspace_id == $active_workspace_id) {
        $links[$workspace_id]['attributes']['class'][] = 'session-active';
      }
    }

    return $links;
  }

}
