<?php

namespace Drupal\entity_stages\Manager;

use Drupal\Core\Url;
use Drupal\Core\Site\Settings;
use Drupal\node\Entity\Node;
use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\ViewExecutable;

/**
 * Handles Drupal Form Elements Override.
 */
class ViewManager {

  /**
   * Implements entity_stages_preprocess_views_view_table().
   */
  public function _preprocessViewsViewTable(&$variables) {
    // Register preprocess only if no page Entity Stage.
    $getRoute = \Drupal::request()->get('_route');

    // Get view.
    $view = $variables['view'];

    // Foreach row add uid and role to validate to easy data access.
    if ($view && $view->result && $getRoute == 'view.entity_stages.default_page') {
      foreach ($variables['rows'] as $key => &$row) {
        // Get comparable revisions values.
        $revisionEntity = $view->result[$key]->_entity;
        $currentEntity = Node::load($revisionEntity->id());

        // Create Operation URLs.
        $urlCompare = Url::fromRoute(
         'diff.revisions_diff',
         [
           'node' => $currentEntity->id(),
           'left_revision' => $currentEntity->getRevisionId(),
           'right_revision' => $revisionEntity->getRevisionId(),
           'filter' => 'split_fields',
         ],
         ['absolute' => TRUE]
        )->toString();

        // Open page.
        $pageUrl = Url::fromRoute(
         'entity.node.canonical',
         ['node' => $currentEntity->id()],
         ['absolute' => TRUE]
        )->toString();

        // Edit page.
        $editPageUrl = $pageUrl . '/edit';

        // Accepter revision.
        $acceptRevision = Url::fromRoute(
        'entity_stages.accept.content',
        [
          'entity_id' => $currentEntity->id(),
          'revision_id' => $revisionEntity->getRevisionId(),
        ],
        ['absolute' => TRUE]
        )->toString();

        // Refuser revision.
        $refuserRevision = Url::fromRoute(
        'entity_stages.reject.content',
        [
          'entity_id' => $currentEntity->id(),
          'revision_id' => $revisionEntity->getRevisionId(),
        ],
        ['absolute' => TRUE]
        )->toString();

        // Add return page.
        $contentModerationPage = '?destination=' . Url::fromRoute(
          'view.entity_stages.default_page', [], ['absolute' => TRUE]
        )->toString();

        // Publish button.
        $publishButton = Url::fromRoute(
        'entity_stages.publish.content',
        ['entity_id' => $currentEntity->id()],
        ['absolute' => TRUE]
        )->toString();

        // Add links.
        $linksStructure = [];
        $linksOutput = '';

        // Conditions.
        $conditionPublished = $currentEntity->isPublished();
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
        $linksStructure['edit'] = [
          'target' => 'normal',
          'enabled' => 1,
          'label' => t('Edit'),
          'url' => $editPageUrl,
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
  public function _viewsPostExecute(ViewExecutable $view) {
    // Alter only the post query of this view.
    if ($view->storage->get('id') == 'entity_stages') {
      // Service Node Stages Checker.
      $entityStagesService = \Drupal::service('entity_stages.main.service');
      // Filter results before pre render.
      foreach ($view->result as $index => $result) {
        // If some condtions are met ignore the result.
        if (
          !$result->_entity ||
          $entityStagesService->isRevisionModerated($result->_entity->vid->value)
        ) {
          // unset($view->result[$index]);.
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
  public function _viewsQueryAlter(ViewExecutable $view, QueryPluginBase $query) {
    if ($view->storage->get('id') == 'entity_stages') {
      $settings = Settings::getAll();
      $currentUser = \Drupal::currentUser();
      $getRequest = \Drupal::request();
      $getType = $getRequest->get('type');
      $getNid = $getRequest->get('nid');
      $getOrder = $getRequest->get('order');
      $getViewsJoinManager = \Drupal::service('plugin.manager.views.join');
      $language = \Drupal::languageManager()->getCurrentLanguage()->getId();

      // Update query definitions.
      $query->fields['langcode']['table'] = 'node_revision';
      $query->addTable('node_revision');
      $query->addTable('node_field_revision');

      // Update sort by type table.
      $query->orderby[0]['field'] = $getOrder == 'type' ? 'nfd.type' : $query->orderby[0]['field'];
      $query->orderby[0]['field'] = str_replace(
        'node_field_revision.nid',
        'node_field_revision.vid',
        $query->orderby[0]['field']
      );
      // Distinct values by user uid and entity nid.
      $query->addGroupBy('nfd.nid');

      // Get User Admin and User where content is auto validated.
      $entityQuery = \Drupal::entityQuery('user');
      $entityQuery->condition('roles', 'administrator');
      $entity_ids = $entityQuery->execute();

      // Join's.
      $join = $getViewsJoinManager->createInstance(
        'standard',
        [
          'type' => 'INNER',
          'table' => 'node_field_data',
          'field' => 'nid',
          'left_table' => 'node_revision',
          'left_field' => 'nid',
          'operator' => '=',
          'adjusted' => TRUE,
          'extraOperator' => 'AND',
          'extra' => array(
            0 => array(
              'left_field' => 'vid',
              'field' => 'vid',
              'operator' => '!=',
            ),
            1 => array(
              'field' => 'langcode',
              'value' => $language,
              'operator' => '=',
            ),
          ),
        ]
      );

      $joinUserRoles = $getViewsJoinManager->createInstance(
        'standard',
        [
          'type' => 'INNER',
          'table' => 'user__roles',
          'field' => 'entity_id',
          'left_table' => 'node_revision',
          'left_field' => 'revision_uid',
          'operator' => '=',
          'adjusted' => TRUE,
          'extraOperator' => 'AND',
          'extra' => array(
            0 => array(
              'field' => 'entity_id',
              'value' => $entity_ids,
              'operator' => 'NOT IN',
            ),
          ),
        ]
      );

      // Add left join for node table from the node_revision table.
      $query->addRelationship('nfd', $join, 'node_field_data');
      $query->addRelationship('ur', $joinUserRoles, 'user__roles');

      // Filter by type.
      if ($getType == 'All' || empty($getType)) {
        $query->where[1]['conditions'] = [];
      }
      else {
        $query->where[1]['conditions'][0] = [
          'field' => 'nfd.type',
          'value' => $query->where[1]['conditions'][0]['value'],
          'operator' => '=',
        ];
      }
      $query->where[1]['conditions'][] = [
        'field' => 'node_field_revision.entity_stages_revision_status',
        'value' => NULL,
        'operator' => 'IS NULL',
      ];

      // Filter by node id.
      if ($getNid && (int) $getNid) {
        $query->where[1]['conditions'][] = [
          'field' => 'nfd.nid',
          'value' => $getNid,
          'operator' => '=',
        ];
      }

    }
  }

  /**
   * Implements hook_views_data_alter().
   */
  public function _viewsDataAlter(array &$data) {
    $data['node_revision']['type'] = $data['node_field_data']['type'];
  }

}
