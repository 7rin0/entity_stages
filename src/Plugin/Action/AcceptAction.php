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

  public $entityStagesService;

  protected $entityRevisionId;

  /**
   * Class constructor.
   */
  public function __construct() {
    $currentRequest = \Drupal::request();
    $entityStagesService = \Drupal::service('entity_stages.main.service');
    $this->entityRevisionId = $currentRequest->get('revision_id');
    $this->entityStagesService = $entityStagesService;
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $this->entityStagesService->moderateRevision(
      $entity, $this->entityRevisionId, 'accept'
    );
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
