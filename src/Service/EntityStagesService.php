<?php

namespace Drupal\entity_stages\Service;

use Drupal\Core\Entity\EntityTypeManager;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Session\AccountProxy;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;

/**
 * Entity Stages Main Service.
 */
class EntityStagesService {

  public $request;
  public $currentUser;
  public $currentUserEntity;
  public $entityTypeManager;

  /**
   * Entity Stages Service's Constructor.
   */
  public function __construct(
    RequestStack $requestStack,
    AccountProxy $currentUser,
    EntityTypeManager $entityTypeManager
    ) {
    $currentUserEntity = User::load($currentUser->id());
    $this->request = $requestStack->getCurrentRequest();
    $this->currentUser = $currentUser;
    $this->currentUserEntity = $currentUserEntity;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Evaluate if the passed Node need Moderation.
   */
  public function needModeration(Node $currentEntity, Node $revisionEntity = NULL) {
    $needModeration = FALSE;
    // Compare current Node and revision Node.
    if ($currentEntity && $revisionEntity) {

    }
    else {
      $getNotModerateContents = $this->getImmoderateRevisions($currentEntity);
    }
    // Be able to scan on Node if needs validation or not using custom fields.
    return $getNotModerateContents;
  }

  /**
   * Gets a list of node revision IDs for a specific node.
   */
  protected function getRevisionIds(Node $node) {
    $result = $this->entityTypeManager->getStorage('node')->getQuery()
      ->allRevisions()
      ->condition($node->getEntityType()->getKey('id'), $node->id())
      ->execute();
    return $result;
  }

  /**
   * Get versions waiting to be validated.
   */
  public function getImmoderateRevisions(Node $node) {

    // Get existing revisions.
    $getNodeRevisions = $this->getRevisionIds($node);

    // Check expecting to be validated versions.
    if ($getNodeRevisions) {
      foreach ($getNodeRevisions as $key => $value) {
        if ($this->isRevisionModerated($key)) {
          unset($getNodeRevisions[$key]);
        }
      }
    }

    return $getNodeRevisions;
  }

  /**
   * Return True is revision is moderated False otherwise.
   */
  public function isRevisionModerated($revisionId) {
    // Get Storage.
    $getNodeStorage = $this->entityTypeManager->getStorage('node');

    // Load Node and Revision.
    $loadRevision = $getNodeStorage->loadRevision($revisionId);
    $nodeLoad = $getNodeStorage->load($loadRevision->id());

    // Return boolean.
    return
      !$loadRevision ||
      ($loadRevision && $loadRevision->isDefaultRevision()) ||
      $loadRevision->changed->value < $nodeLoad->changed->value ||
      !$this->currentUserEntity->hasRole('administrator');
  }

}
