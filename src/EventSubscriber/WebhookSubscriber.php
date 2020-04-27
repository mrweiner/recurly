<?php

namespace Drupal\recurly\EventSubscriber;

use Drupal\recurly\Event\RecurlySuccessfulPaymentEvent;
use Drupal\recurly\Event\RecurlyWebhookEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Sample Event Subscriber.
 *
 * @package Drupal\recurly\EventSubscriber
 */
class WebhookSubscriber implements EventSubscriberInterface {

  /**
   * Get subscribed events.
   *
   * @inheritdoc
   */
  public static function getSubscribedEvents() {
    $events[RecurlyWebhookEvents::SUCCESSFUL_PAYMENT][] = ['onSuccessfulPayment'];
    return $events;
  }

  /**
   * Successful payment event handler.
   *
   * @param \Drupal\recurly\Event\RecurlySuccessfulPaymentEvent $event
   *   The subscribed event.
   */
  public function onSuccessfulPayment(RecurlySuccessfulPaymentEvent $event) {

  }

}
