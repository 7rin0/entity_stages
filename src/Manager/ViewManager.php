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
        if(!method_exists($revisionEntity, 'id')) {
          // kpr($view->result[$key]);
          // kpr($revisionEntity);
          // die();
        }
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
    // kpr($view->build_info['query']->__toString());die();
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
      // $nbRows = count($view->result);
      // $view->total_items = $nbRows;
      // $view->pager->total_items = $nbRows;
      // $view->pager->updatePageInfo();
      // $view->query->view->pager->total_items = $nbRows;
      // $view->query->view->pager->updatePageInfo();
    }
  }

  /**
   * Implements hook_views_query_alter().
   */
  public function _viewsQueryAlter(ViewExecutable $view, QueryPluginBase $query) {
    // Mysql 5.7.16 -> SET SQL_MODE="";.
    if ($view->storage->get('id') == 'entity_stages') {
      // Services.
      $settings = Settings::getAll();
      $currentUser = \Drupal::currentUser();
      $getRequest = \Drupal::request();
      $getViewsJoinManager = \Drupal::service('plugin.manager.views.join');

      // Request.
      $getType = $getRequest->get('type');
      $getNid = $getRequest->get('nid');
      $getOrder = $getRequest->get('order');

      // Update query definitions.
      $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
      $query->fields['langcode']['table'] = 'node_field_revision';
      $query->addField('node_field_revision', 'nid', 'node_field_revision_nid', ['functions' => 'groupby']);

      // Update sort by type table.
      $query->orderby[0]['field'] = $getOrder == 'type' ? 'nfd.type' : $query->orderby[0]['field'];
      $query->orderby[0]['field'] = str_replace(
        'node_field_revision.nid',
        'node_field_revision.vid',
        $query->orderby[0]['field']
      );

      // Filter by type.
      foreach($query->where[1]['conditions'] as &$condition) {
        if($condition['field'] == 'node_field_revision.type'){
          if($getType == 'All' || empty($getType)){
            $condition = [
              'field' => 'nfd.type',
              'value' => "NOT NULL",
              'operator' => '=',
            ];
          }
          else {
            $condition = [
              'field' => 'nfd.type',
              'value' => $query->where[1]['conditions'][0]['value'],
              'operator' => '=',
            ];
          }
        }
      }

      // Join's.
      $join = $getViewsJoinManager->createInstance(
      'standard',
      [
        'type' => 'LEFT',
        'table' => 'node_field_data',
        'field' => 'nid',
        'left_table' => 'node_field_revision',
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
          2 => array(
            'left_field' => 'uid',
            'field' => 'uid',
            'operator' => '=',
          ),
          1 => array(
            'field' => 'langcode',
            'value' => $language,
            'operator' => '=',
          ),
        ),
        ]
      );

      // Add left join for node table from the node_revision table.
      $query->addRelationship('nfd', $join, 'node_field_data');

      // Add condition based on allowed revisions.
      $query->where[1]['conditions'][] = [
        'field' => 'node_field_revision.vid',
        'value' => $this->getPendingRevisions(),
        'operator' => 'IN',
      ];
    }
  }

  /**
   * Return allowed Revisions pending validation.
   */
  public function getPendingRevisions($args = []) {
    // Ignore validated revisions.
    // Ignore revisions created by anonymous users.
    $entityQuery = \Drupal::entityQuery('user');
    $entityQuery->condition('roles', ['administrator']);
    $entity_ids = $entityQuery->execute() + [0];
    $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();

    // Filter Node Field Revision Data.
    $query = \Drupal::database()->select('node_field_revision', 'nfr');
    // Joins.
    $query->leftJoin('node_revision', 'nr', 'nfr.vid = nr.vid');
    $query->leftJoin('node_field_data', 'nfd', 'nfr.nid = nfd.nid AND nfr.vid != nfd.vid ');
    // Fields.
    // -- Node field Data.
    $query->addField('nfd', 'title', 'nfd_title');
    $query->addField('nfd', 'type', 'nfd_title');
    // -- Node Revision.
    $query->addField('nr', 'vid', 'nr_vid');
    $query->addField('nr', 'revision_uid', 'nr_revision_uid');
    $query->addField('nr', 'revision_log', 'nfr_revision_log');
    // -- Node Field Revision.
    $query->addField('nfr', 'title', 'nfr_title');
    $query->addField('nfr', 'nid', 'nfr_nid');
    $query->addField('nfr', 'vid', 'nfr_vid');
    // Conditions.
    $query->condition('nr.revision_uid', $entity_ids, 'NOT IN');
    $query->condition('nr.langcode', $langcode);
    $query->condition('nfr.langcode', $langcode);
    $query->condition(
      db_or()
        ->condition('nfr.entity_stages_revision_status', NULL, 'IS NULL')
        ->condition('nfr.entity_stages_revision_status', '4')
    );
    $query->condition('nfd.langcode', $langcode);
    // Order by.
    $query->orderBy('nr.revision_timestamp', 'DESC');
    // Execute and fetch results.
    $pendingValidationRevisions = $query->execute()->fetchAll();
    // Extract valid vid.
    $trueVid = [];
    // Do not change this! Reverse order to override last value to a given vid.
    krsort($pendingValidationRevisions);
    foreach ($pendingValidationRevisions as $key => $value) {
      $trueVid[$value->nfr_nid] = $value->nfr_vid;
    }
    // Array values;
    $trueVid = array_values($trueVid);

    return $trueVid;
  }

  /**
   * Implements hook_views_data_alter().
   */
  public function _viewsDataAlter(array &$data) {
    $data['node_field_revision']['type'] = $data['node_field_data']['type'];
  }

}
