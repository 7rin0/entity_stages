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
  public $nodeStorage;

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
    $this->nodeStorage = $entityTypeManager->getStorage('node');
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
    $loadRevision = $this->nodeStorage->loadRevision($revisionId);
    $nodeLoad = $this->nodeStorage->load($loadRevision->id());

    // Return boolean.
    return
      !$loadRevision ||
      ($loadRevision && $loadRevision->isDefaultRevision()) ||
      $loadRevision->changed->value < $nodeLoad->changed->value ||
      !$this->currentUserEntity->hasRole('administrator');
  }

  /**
   *
   */
  public function moderateRevision($entity, $revision_id, $action) {
    $moderateThisRevision = $this->nodeStorage->loadRevision($revision_id);

    // Do not moderate this node => revision
    // if nid dont match for some reason to avoid security issues.
    if ($moderateThisRevision->id() == $entity->id()) {
      if ($action == 'accept') {
        $moderateThisRevision->isDefaultRevision(TRUE);
        // And field must be set to accepted.
        $moderateThisRevision->set('entity_stages_revision_status', 1);
      }
      elseif ($action == 'reject') {
        // And field must be set to rejected.
        $moderateThisRevision->set('entity_stages_revision_status', 0);
      }
      // Save Modifications.
      $moderateThisRevision->setNewRevision(FALSE);
      $moderateThisRevision->save();
    }
  }

}
