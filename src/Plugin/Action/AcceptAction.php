<?php

namespace Drupal\entity_stages\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Accept pending modifications.
 *
 * @Action(
 *   id = "entity_stages_accept_action",
 *   label = @Translation("Accept pending modifications"),
 *   type = "entity"
 * )
 */
class AcceptAction extends ActionBase {

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
