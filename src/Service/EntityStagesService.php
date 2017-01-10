<?php

namespace Drupal\entity_stages\Service;

use Drupal\Core\Entity\EntityTypeManager;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Session\AccountProxy;
use Drupal\node\Entity\Node;

/**
 * Entity Stages Main Service.
 */
class EntityStagesService {

  public $request;
  public $currentUser;
  public $entityTypeManager;

  /**
   * Entity Stages Service's Constructor.
   */
  public function __construct(
    RequestStack $requestStack,
    AccountProxy $currentUser,
    EntityTypeManager $entityTypeManager
    ) {
    $this->request = $requestStack->getCurrentRequest();
    $this->currentUser = $currentUser;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Evaluate if the passed Node need Moderation.
   */
  public function needModeration(Node $currentEntity, Node $revisionEntity = NULL) {
    // Compare current Node and revision Node.
    if ($currentEntity && $revisionEntity) {

    }
    else {
      $getNodeRevisions = $this->getRevisionIds($currentEntity);
    }
    // Be able to scan on Node if needs validation or not using custom fields.
    return FALSE;
  }

  /**
   * Gets a list of node revision IDs for a specific node.
   */
  protected function getRevisionIds(Node $node) {
    $result = $this->entityTypeManager->getStorage('node')->getQuery()
      ->allRevisions()
      ->condition($node->getEntityType()->getKey('id'), $node->id())
      ->sort($node->getEntityType()->getKey('revision'), 'DESC')
      ->execute();
    return array_keys($result);
  }

}
