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
