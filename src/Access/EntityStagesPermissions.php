<?php

namespace Drupal\entity_stages\Access;

use Drupal\Core\Routing\UrlGeneratorTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\Entity\NodeType;

/**
 * Provides dynamic permissions for nodes of different types.
 */
class EntityStagesPermissions {

  use StringTranslationTrait;
  use UrlGeneratorTrait;

  /**
   * Returns an array of node type permissions.
   */
  public function nodeTypePermissions() {
    $premissions = [];
    // Generate node permissions for all node types.
    foreach (NodeType::loadMultiple() as $type) {
      $premissions += $this->buildPermissions($type);
    }

    return $premissions;
  }

  /**
   * Returns a list of node permissions for a given node type.
   */
  protected function buildPermissions(NodeType $type) {
    $type_id = $type->id();
    $type_params = ['%type_name' => $type->label()];
    return [
      "moderate $type_id content modifications" => [
        'title' => $this->t('%type_name: Allowed to moderate %type_name content modifications.', $type_params),
      ],
      "moderate $type_id content publish" => [
        'title' => $this->t('%type_name: Allowed to publish %type_name bypassing moderation.', $type_params),
      ],
    ];
  }

}
