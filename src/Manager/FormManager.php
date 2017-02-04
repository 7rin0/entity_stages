<?php

namespace Drupal\entity_stages\Manager;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Handles Drupal Form Elements Override.
 */
class FormManager {

  /**
   * Handles Drupal Module Related Form Alter.
   */
  public function _hookFormAlter(&$form, FormStateInterface $form_state, $form_id) {
    // Alter redirection to return to entity stages validation page.
    if (
        $form_id == 'node_revision_revert_confirm' ||
        $form_id == 'node_revision_delete_confirm'
      ) {
      $form['#after_build'][] = 'Drupal\entity_stages\Manager\FormManager::_redirect_to_moderation_entity_stages_page';
      $form['#submit'][] = 'Drupal\entity_stages\Manager\FormManager::_redirect_to_moderation_entity_stages_page';
    }

    // Update submit button if we are about to made a registration.
    // Current User.
    if (
      isset($form_state->getBuildInfo()['base_form_id']) &&
      $form_state->getBuildInfo()['base_form_id'] == 'node_form'
      ) {

      // Get Node Type.
      $nodeType = str_replace('node_', '', str_replace(['_edit_form', '_form'], '', $form['#form_id']));

      // If user hasnt enough rights alter label.
      $entityStagesService = \Drupal::service('entity_stages.main.service');
      $allowedToPublish = $entityStagesService->allowedToPublish($nodeType);

      // From registration to sumit.
      if (!$allowedToPublish) {
        $form['actions']['submit']['#value'] = t('Submit');
        $form['actions']['submit']['#hook'] = 'entity_stages';
      }
    }
  }

  /**
   * Alter default form redirection.
   */
  public function _redirect_to_moderation_entity_stages_page($form, FormStateInterface $form_state) {
    if ($getDestination = $_GET['destination']) {
      $submitLabel = $form['#form_id'] == 'node_revision_revert_confirm' ? t('Accept') : t('Reject');
      $form_state->setRedirectUrl(Url::fromUri($getDestination));
      $form['actions']['submit']['#value'] = $submitLabel;
      $form['actions']['cancel']['#url'] = Url::fromUri($getDestination);
    }
    return $form;
  }

}
