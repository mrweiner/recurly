<?php

namespace Drupal\recurly\Event\notifications\subscription;

use Drupal\recurly\Event\RecurlyWebhookEventBase;

/**
 * Event fired for Reactivated Account notifications.
 *
 * Based on naming this seems like it would be a "subscription"
 * notification and not an account one, but it's organized under
 * "Subscription Notifications" in \Recurly_PushNotification.
 *
 * @package Drupal\recurly\Event
 */
class RecurlyReactivatedAccountEvent extends RecurlyWebhookEventBase {

}
