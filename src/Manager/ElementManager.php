<?php

namespace Drupal\entity_stages\Manager;

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
    // $theme_registry['views_view_table__entity_stages']['template'] = 'views-view-table--entity-stages';
    // $theme_registry['views_view_table__entity_stages']['path'] = 'src/Template/views';.
    $theme_registry['views_view_table__entity_stages']['preprocess functions'][] = 'entity_stages_preprocess_views_view_table';
  }

  /**
   * Handles Drupal Module Related Form Alter.
   */
  public function _menuLocalTasksAlter(&$data, $route_name) {

    // Routes were entity stages should be present as a task.
    $allowedRoutes = [
      'system.admin_content',
      'entity.user.collection',
      'view.entity_stages.default_page',
    ];

    // If entity stages is the current route.
    if ($route_name == 'view.entity_stages.default_page') {
      $data['tabs'][0][] = [
        '#theme' => 'menu_local_task',
        '#active' => $route_name == 'system.admin_content',
        '#link' => [
          'title' => t('Content'),
          'url' => Url::fromRoute(
        'system.admin_content',
        [],
        ['absolute' => TRUE]
          ),
        ],
      ];
      $data['tabs'][0][] = [
        '#theme' => 'menu_local_task',
        '#active' => $route_name == 'entity.user.collection',
        '#link' => [
          'title' => t('People'),
          'url' => Url::fromRoute(
        'entity.user.collection',
        [],
        ['absolute' => TRUE]
          ),
        ],
      ];
    }

    if (in_array($route_name, $allowedRoutes)) {
      // Add Entity Stages task.
      $data['tabs'][0][] = [
        '#theme' => 'menu_local_task',
        '#active' => $route_name == 'view.entity_stages.default_page',
        '#link' => [
          'title' => t('Entity stages'),
          'url' => Url::fromRoute(
            'view.entity_stages.default_page', [], ['absolute' => TRUE]
          ),
        ],
      ];
    }
  }

}
