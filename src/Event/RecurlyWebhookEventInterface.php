<?php

namespace Drupal\recurly\Event;

/**
 * An interface for Recurly Webhook Events.
 *
 * @package Drupal\recurly\Event
 */
interface RecurlyWebhookEventInterface {

  /**
   * Gets the subdomain.
   *
   * @return string
   *   The associated subdomain
   */
  public function getSubDomain();

  /**
   * Gets the type provided by the notification.
   *
   * @return string
   *   The notification type.
   */
  public function getType();

  /**
   * Gets the associated push notification.
   *
   * @return \Recurly_PushNotification
   *   The notification associated with the event.
   */
  public function getNotification();

}
