<?php

namespace Drupal\entity_stages\Manager;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Handles Drupal Form Elements Override.
 */
class ElementManager {

  /**
   * Implements hook_theme_registry_alter().
   */
  public function _themeRegistryAlter(&$theme_registry) {
    // Moderate Content.
    $theme_registry['views_view_table__entity_stages'] = $theme_registry['views_view_table'];
    $theme_registry['views_view_table__entity_stages']['preprocess functions'][] = 'entity_stages_preprocess_views_view_table';
  }

  /**
   * Implements hook_entity_operation_alter().
   */
  public function _entityOperationAlter(array &$operations, EntityInterface $entity) {
    if ($entity->getEntityTypeId() == 'node') {
      // Service Node Stages Checker.
      $accountProxy = \Drupal::currentUser();
      $entityStagesService = \Drupal::service('entity_stages.main.service');
      $allowedToModerate = $entityStagesService->allowedToModerate($entity->getType());

      if ($allowedToModerate) {
        $operations['entity_stages_moderate'] = [
          'title' => t('Moderate'),
          'url' => Url::fromRoute('view.entity_stages.default_page', ['nid' => $entity->id()], ['absolute' => TRUE]),
          'weight' => 3,
        ];
      }
    }
  }

  /**
   * Handles Drupal Module Related Form Alter.
   */
  public function _menuLocalTasksAlter(&$data, $route_name) {
    $serviceLocalTask = \Drupal::service('plugin.manager.menu.local_task');

    // Routes were entity stages should be present as a task.
    // Content tasks.
    $nodeDefaultTasks = $serviceLocalTask->getLocalTasksForRoute('system.admin_content')[0];
    foreach ($nodeDefaultTasks as $task_route => $value) {
      $nodeDefaultTasksRouteNames[$value->getRouteName()] = array(
        '#theme' => 'menu_local_task',
        '#active' => $value->getRouteName() == $route_name,
        '#link' => [
          'title' => $value->getTitle(),
          'url' => Url::fromRoute($value->getRouteName(), [], ['absolute' => TRUE]),
          'localized_options' => array('attributes' => array($value->getTitle())),
        ],
      );
    }
    $nodeDefaultTasksRouteNames['view.entity_stages.default_page'] = 'view.entity_stages.default_page';
    if (in_array($route_name, array_keys($nodeDefaultTasksRouteNames))) {
      // Add Moderate content tab.
      $entityStagesTitle = isset($data['tabs'][0]['view.entity_stages.default_page']['#link']['title']) ? $data['tabs'][0]['view.entity_stages.default_page']['#link']['title'] : t('Entity Stages');
      $data['tabs'][0] = $nodeDefaultTasksRouteNames;
      $data['tabs'][0]['view.entity_stages.default_page'] = [
        '#theme' => 'menu_local_task',
        '#active' => $route_name == 'view.entity_stages.default_page',
        '#link' => [
          'title' => $entityStagesTitle,
          'url' => Url::fromRoute(
            'view.entity_stages.default_page', [], ['absolute' => TRUE]
          ),
        ],
      ];
    }
  }

}
