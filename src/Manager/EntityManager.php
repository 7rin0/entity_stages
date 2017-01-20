<?php

namespace Drupal\entity_stages\Manager;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;

/**
 * Handles Drupal Form Elements Override.
 */
class EntityManager {

  /**
   * Handles Drupal Module Related Form Alter.
   */
  public function _entityBaseFieldInfo(EntityTypeInterface $entity_type) {
    $fields = [];

    if ($entity_type->id() == 'node' || $entity_type->id() == 'user') {
      $fields['entity_stages_current_status'] =
        BaseFieldDefinition::create('integer')
          ->setLabel(t('Entity Stages - Current Status'))
          ->setDefaultValue(NULL)
          ->setRevisionable(TRUE)
          ->setTranslatable(TRUE);

      $fields['entity_stages_revision_status'] =
        BaseFieldDefinition::create('integer')
          ->setLabel(t('Entity Stages - Revision Status'))
          ->setDefaultValue(NULL)
          ->setRevisionable(TRUE)
          ->setTranslatable(TRUE);

    }

    return $fields;
  }

  /**
   * Implements hook_node_presave().
   */
  public function _nodePresave(Node $node) {
    // TODO: content type must be defined as entity stages type.
    // Current User.
    $accountProxy = \Drupal::currentUser();
    $loadCurrentUser = User::load($accountProxy->id());
    $requireValidation = !$loadCurrentUser->hasRole('administrator') && !$loadCurrentUser->hasPermission('publish entity stages');

    // Default entity stages status.
    // Use entity_stages_current_status to node transictions.
    // Use entity_stages_revision_status to revision transictions.
    // If User hasnt permission to publish or modify without validation
    // the content status is either unpublished or
    // published and waiting for validation.
    if ($accountProxy->isAuthenticated()) {
      if ($requireValidation) {
        // If new starts as unpublished.
        if ($node->isNew()) {
          $node->set('status', 0);
        }
        // Else keep current revision.
        else {
          $node->set('status', $node->original->isPublished());
          $node->isDefaultRevision(FALSE);
          $node->original->isDefaultRevision(TRUE);
        }
      }
    }
  }

}
