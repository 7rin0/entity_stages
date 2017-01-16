<?php

namespace Drupal\entity_stages\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Reject pending modifications.
 *
 * @Action(
 *   id = "entity_stages_reject_action",
 *   label = @Translation("Reject pending modifications"),
 *   type = "entity"
 * )
 */
class RejectAction extends ActionBase {

  /**
   * Class constructor.
   */
  public function __construct() {
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    // kpr($entity);die();
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $entity = NULL, $return_as_object = FALSE) {
    /* @var \Drupal\entity\entityInterface $object */
    $access = $object->status->access('edit', $entity, TRUE)
      ->andif($object->access('update', $entity, TRUE));

    return $return_as_object ? $access : $access->isAllowed();
  }

}
