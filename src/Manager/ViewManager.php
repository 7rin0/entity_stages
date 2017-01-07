<?php

namespace Drupal\entity_stages\Manager;

use Drupal\Core\Url;
use Drupal\Core\Site\Settings;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;
use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\ViewExecutable;

/**
 * Handles Drupal Form Elements Override.
 */
class ViewManager {

  /**
   * Implements entity_stages_preprocess_views_view_table().
   */
  static function _preprocessViewsViewTable(&$variables) {
    // Register preprocess only if no page Entity Stage.
    $getRoute = \Drupal::request()->get('_route');

    // Get view.
    $view = $variables['view'];

    // Foreach row add uid and role to validate to easy data access.
    if ($view && $view->result && $getRoute == 'view.entity_stages.default_page') {
      foreach ($variables['rows'] as $key => &$row) {
        // Get comparable revisions values.
        $revisionEntity = $view->result[$key]->_entity;
        $nodeLoad = Node::load($revisionEntity->id());

        // Create Operation URLs.
        $urlCompare = Url::fromRoute(
         'diff.revisions_diff',
         [
           'node' => $nodeLoad->id(),
           'left_revision' => $nodeLoad->getRevisionId(),
           'right_revision' => $revisionEntity->getRevisionId(),
           'filter' => 'split_fields',
         ],
         ['absolute' => TRUE]
        )->toString();

        // Open page.
        $pageUrl = Url::fromRoute(
         'entity.node.canonical',
         ['node' => $nodeLoad->id()],
         ['absolute' => TRUE]
        )->toString();

        // Accepter revision/modification.
        $acceptRevision = Url::fromRoute(
        'node.revision_revert_confirm',
        [
          'node' => $nodeLoad->id(),
          'node_revision' => $revisionEntity->getRevisionId(),
        ],
        ['absolute' => TRUE]
        )->toString();

        // Refuser revision/modification.
        $refuserRevision = Url::fromRoute(
        'node.revision_delete_confirm',
        [
          'node' => $nodeLoad->id(),
          'node_revision' => $revisionEntity->getRevisionId(),
        ],
        ['absolute' => TRUE]
        )->toString();

        // Add Node type to Type column.
        $row['columns']['nothing']['content'][0]['field_output']['#markup'] = ucfirst($nodeLoad->getType());

        // Add return page.
        $contentModerationPage = '?destination=' . Url::fromRoute(
          'view.entity_stages.default_page', [], ['absolute' => TRUE]
        )->toString();

        // Publish button.
        $publishButton = Url::fromRoute(
        'entity_stages.publish.content',
        ['entity_id' => $nodeLoad->id()],
        ['absolute' => TRUE]
        )->toString();

        // Add links.
        $linksStructure = [];
        $linksOutput = '';

        // Conditions.
        $conditionPublished = $nodeLoad->isPublished();
        $conditionCurrent = $revisionEntity->isDefaultRevision();
        $conditionCurrentPublished = !$conditionPublished && $conditionCurrent;

        // Link structures.
        $linksStructure['publish'] = [
          'target' => 'normal',
          'enabled' => $conditionCurrentPublished,
          'label' => t('Publish'),
          'url' => $publishButton,
        ];
        $linksStructure['diff'] = [
          'target' => '_blank',
          'enabled' => !$conditionCurrentPublished,
          'label' => t('Difference'),
          'url' => $urlCompare,
        ];
        $linksStructure['view'] = [
          'target' => 'normal',
          'enabled' => 1,
          'label' => t('View'),
          'url' => $pageUrl,
        ];
        $linksStructure['accept'] = [
          'target' => 'normal',
          'enabled' => !$conditionCurrentPublished,
          'label' => t('Accept'),
          'url' => $acceptRevision . $contentModerationPage,
        ];
        $linksStructure['reject'] = [
          'target' => 'normal',
          'enabled' => !$conditionCurrentPublished,
          'label' => t('Reject'),
          'url' => $refuserRevision . $contentModerationPage,
        ];

        foreach ($linksStructure as $key => $value) {
          if ($value['enabled']) {
            $linksOutput .= '<li><a href="' . $value['url'] . '" target="' . $value['target'] . '">' . $value['label'] . '</a></li>';
          }
        }

        // Add route to accept and refuse.
        $row['columns']['dropbutton']['content'][0]['field_output']['#markup'] =
           '<div class="dropbutton-wrapper">
             <div class="dropbutton-widget">
               <ul class="dropbutton">
                 ' . $linksOutput . '
               </ul>
             </div>
           </div>';
      }
    }
  }

  /**
   * Implements hook_views_post_execute().
   */
  static function _viewsPostExecute(ViewExecutable $view) {
    // Alter only the post query of this view.
    if ($view->storage->get('id') == 'entity_stages') {
      // Filter results before pre render.
      foreach ($view->result as $index => $result) {
        // If some condtions are met ignore the result.
        if (!$result->_entity) {
          unset($view->result[$index]);
          continue;
        }

        // Load entities.
        $nodeLoad = Node::load($result->_entity->nid->value);
        $userLoad = User::load($nodeLoad->uid->target_id);
        $isAdmin = $userLoad->hasRole('administrator');
        $revisionIsOlderThanCurrent = $result->_entity->changed->value < $nodeLoad->changed->value;

        // If some condtions are met ignore the result.
        if (
          $result->_entity->isDefaultRevision() && $nodeLoad->isPublished() ||
          $revisionIsOlderThanCurrent ||
          $isAdmin
        ) {
          unset($view->result[$index]);
        }
      }

      // Update rows number and pager.
      // $view->pager->setItemsPerPage(1000);.
      $nbRows = count($view->result);
      $view->total_items = $nbRows;
      $view->pager->total_items = $nbRows;
      $view->pager->updatePageInfo();
      $view->query->view->pager->total_items = $nbRows;
      $view->query->view->pager->updatePageInfo();
    }
  }

  /**
   * Implements hook_views_query_alter().
   */
  static function _viewsQueryAlter(ViewExecutable $view, QueryPluginBase $query) {
    if ($view->storage->get('id') == 'entity_stages') {
      $settings = Settings::getAll();
      $currentUser = \Drupal::currentUser();
      $getType = \Drupal::request()->get('type');
      $getViewsJoinManager = \Drupal::service('plugin.manager.views.join');

      // Filter by type.
      if ($getType) {
        if ($getType == 'All') {
          $query->where[1]['conditions'] = [];
        }
        else {
          $join = $getViewsJoinManager->createInstance(
            'standard',
            [
              'table' => 'node_field_data',
              'field' => 'nid',
              'left_table' => 'node_revision',
              'left_field' => 'nid',
              'operator' => '=',
              'adjusted' => TRUE,
            ]
          );
          // Add left join for node table from the node_revision table.
          $query->addRelationship('node_field_data', $join, 'node_field_data');
          $query->where[1]['conditions'][0] = [
            'field' => 'node_field_data.type',
            'value' => $query->where[1]['conditions'][0]['value'],
            'operator' => '=',
          ];
        }
      }
    }
  }

  /**
   * Implements hook_views_data_alter().
   */
  static function _viewsDataAlter(array &$data) {
    $data['node_revision']['type'] = $data['node_field_data']['type'];
  }

}
