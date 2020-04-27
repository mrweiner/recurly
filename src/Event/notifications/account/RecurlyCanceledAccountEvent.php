<?php

namespace Drupal\recurly\Event\notifications\account;

use Drupal\recurly\Event\RecurlyWebhookEventBase;

/**
 * Event fired for Canceled [sic] Account notifications.
 *
 * "Canceled" typo on purpose due to associated type
 * defined in \Recurly_PushNotification.
 *
 * @package Drupal\recurly\Event
 */
class RecurlyCanceledAccountEvent extends RecurlyWebhookEventBase {

}
