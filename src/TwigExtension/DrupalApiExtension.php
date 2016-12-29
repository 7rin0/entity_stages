<?php

namespace Drupal\entity_stages\TwigExtension;

use Drupal\Core\Template\TwigExtension;
use Drupal\node\Entity\Node;

/**
 * Class DrupalApiExtension.
 */
class DrupalApiExtension extends TwigExtension {

  /**
   * The getFunctions method.
   */
  public function getFunctions() {
    return [
      new \Twig_SimpleFunction('node_load', [$this, 'nodeLoad']),
    ];
  }

  /**
   * The getFilters method.
   */
  public function getFilters() {
    return [];
  }

  /**
   * The nodeLoad method.
   */
  public function nodeLoad($nid) {
    if ($nid) {
      $nodeLoad = Node::load($nid);
      return $nodeLoad;
    }
    return $nid;
  }

  /**
   * The getName method.
   */
  public function getName() {
    return 'entity_stages.twig.node_load';
  }

}
