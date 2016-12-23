<?php

namespace Drupal\entity_stages\Controller;

use Drupal\Core\Controller\ControllerBase;
use \Symfony\Component\HttpFoundation\Request;

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

}
