<?php

namespace Drupal\entity_stages\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;

/**
 * Entity Stages Main Controller.
 */
class EntityStagesController extends ControllerBase {

  /**
   * Entity Stages Main Action.
   */
  public function indexAction(Request $request) {
    $build = [
      'type' => 'html',
      '#markup' => 'Entity Stages Administration Page',
    ];

    return $build;
  }

  /**
   * Publish action.
   */
  public function publishAction($entity_id) {
    // Update node info.
    $nodeLoad = Node::load($entity_id);
    $nodeLoad->set('status', 1)->save();
    // Redirect.
    $targetUrl = Url::fromRoute(
    'view.entity_stages.default_page',
    [],
    ['absolute' => TRUE]
    )->toString();

    return new RedirectResponse($targetUrl);
  }

}
