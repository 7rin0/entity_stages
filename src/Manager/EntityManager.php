<?php

namespace Drupal\entity_stages\Manager;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\node\Entity\Node;

/**
 * Handles Drupal Form Elements Override.
 */
class EntityManager {

  /**
   * Handles Drupal Module Related Form Alter.
   */
  public function _entityBaseFieldInfoAlter(&$fields, EntityTypeInterface $entity_type) {
    if ($entity_type->id() == 'node') {
      $fields['entity_stages_validation_state'] = BaseFieldDefinition::create('string')
          ->setComputed(TRUE);
    }
  }

  /**
   * Implements hook_node_presave().
   */
  public function _nodePresave(Node $node) {
    // Current User.
    $loadCurrentUser = User::load(\Drupal::currentUser()->id());
    $requireValidation =
    !$loadCurrentUser->hasRole('administrator') &&
    !$loadCurrentUser->hasPermission('publish entity stages');

    // If User hasnt permission to publish or modify without validation
    // the content status is either unpublished or
    // published and waiting for validation.
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
