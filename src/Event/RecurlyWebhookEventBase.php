<?php

namespace Drupal\recurly\Event;

use Drupal\recurly\RecurlyPushNotification;
use Symfony\Component\EventDispatcher\Event;

/**
 * Base class for Recurly Webhook Events.
 *
 * @package Drupal\recurly\Event
 */
class RecurlyWebhookEventBase extends Event implements RecurlyWebhookEventInterface {

  /**
   * The push notification associated with the event.
   *
   * @var RecurlyPushNotification
   */
  protected $notification;

  /**
   * The type of notification.
   *
   * @var string
   */
  protected $type;

  /**
   * The associated subdomain.
   *
   * @var string
   */
  protected $subdomain;

  /**
   * RecurlySuccessfulPaymentEvent constructor.
   *
   * @param RecurlyPushNotification $notification
   *   Recurly Push Notification object provided by the webhook.
   * @param string $subdomain
   *   The associated Recurly subdomain.
   */
  public function __construct(RecurlyPushNotification $notification, $subdomain) {
    $this->notification = $notification;
    $this->type = $notification->type;
    $this->subdomain = $subdomain;
  }

  /**
   * Gets the subdomain.
   *
   * @return string
   *   The associated subdomain.
   */
  public function getSubDomain() {
    return $this->subdomain;
  }

  /**
   * Gets the type provided by the notification.
   *
   * @return string
   *   The type of notification.
   */
  public function getType() {
    return $this->type;
  }

  /**
   * Gets the associated push notification.
   *
   * @return \Recurly_PushNotification
   *   The associated push notification.
   */
  public function getNotification() {
    return $this->notification;
  }

}
