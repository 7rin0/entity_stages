<?php

namespace Drupal\entity_stages\Event;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Entity Stages Main Subscriber.
 */
class EntityStagesSubscriber implements EventSubscriberInterface {

  /**
   * Session Handler.
   */
  public function requestHandler(GetResponseEvent $event) {
    // Verify valid connections on same databases.
  }

  /**
   * Controller Handler.
   */
  public function controllerHandler(FilterControllerEvent $event) {
    // Verify valid connections on same databases.
  }

  /**
   * Response Handler.
   */
  public function responseHandler(FilterResponseEvent $event) {
    // Verify valid connections on same databases.
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['requestHandler'];
    $events[KernelEvents::RESPONSE][] = ['responseHandler'];
    $events[KernelEvents::CONTROLLER][] = ['controllerHandler'];
    return $events;
  }

}
