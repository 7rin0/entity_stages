<?php

namespace Drupal\entity_stages\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;
use Drupal\system\Entity\Action;

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

    // Redirect.
    return new RedirectResponse($this->getEntityStagesViewUrl());
  }

  /**
   * Accept action.
   */
  public function acceptAction($entity_id, $revision_id) {

    // Update node info.
    $acceptAction = Action::load('entity_stages_accept_action');
    $loadEntity = Node::load($entity_id);
    $acceptAction->execute([$loadEntity]);

    // Redirect.
    return new RedirectResponse($this->getEntityStagesViewUrl());
  }

  /**
   * Reject action.
   */
  public function rejectAction($entity_id, $revision_id) {
    // Update node info.
    $rejectAction = Action::load('entity_stages_reject_action');
    $loadEntity = Node::load($entity_id);
    $rejectAction->execute([$loadEntity]);

    // Redirect.
    return new RedirectResponse($this->getEntityStagesViewUrl());
  }

  /**
   * Get Entity Stages View Url.
   */
  function getEntityStagesViewUrl() {
    return Url::fromRoute(
      'view.entity_stages.default_page', [], ['absolute' => TRUE]
      )->toString();
  }

}
