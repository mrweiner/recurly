<?php

namespace Drupal\recurly;

use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Drupal\recurly\Event\notifications\account\RecurlyBillingInfoUpdatedEvent;
use Drupal\recurly\Event\notifications\account\RecurlyBillingInfoUpdateFailedEvent;
use Drupal\recurly\Event\notifications\account\RecurlyCanceledAccountEvent;
use Drupal\recurly\Event\notifications\account\RecurlyDeletedShippingAddressEvent;
use Drupal\recurly\Event\notifications\account\RecurlyNewAccountEvent;
use Drupal\recurly\Event\notifications\account\RecurlyNewShippingAddressEvent;
use Drupal\recurly\Event\notifications\account\RecurlyUpdatedAccountEvent;
use Drupal\recurly\Event\notifications\account\RecurlyUpdatedShippingAddressEvent;
use Drupal\recurly\Event\notifications\payment\RecurlySuccessfulPaymentEvent;
use Drupal\recurly\Event\notifications\subscription\RecurlyCanceledSubscriptionEvent;
use Drupal\recurly\Event\notifications\subscription\RecurlyExpiredSubscriptionEvent;
use Drupal\recurly\Event\notifications\subscription\RecurlyNewSubscriptionEvent;
use Drupal\recurly\Event\notifications\subscription\RecurlyPausedSubscriptionRenewalEvent;
use Drupal\recurly\Event\notifications\subscription\RecurlyReactivatedAccountEvent;
use Drupal\recurly\Event\notifications\subscription\RecurlyRenewedSubscriptionEvent;
use Drupal\recurly\Event\notifications\subscription\RecurlyScheduledSubscriptionPauseEvent;
use Drupal\recurly\Event\notifications\subscription\RecurlySubscriptionPauseCanceledEvent;
use Drupal\recurly\Event\notifications\subscription\RecurlySubscriptionPausedEvent;
use Drupal\recurly\Event\notifications\subscription\RecurlySubscriptionPauseModifiedEvent;
use Drupal\recurly\Event\notifications\subscription\RecurlySubscriptionResumedEvent;
use Drupal\recurly\Event\notifications\subscription\RecurlyUpdatedSubscriptionEvent;
use Drupal\recurly\Event\RecurlyWebhookEvents;

/**
 * Helps to determine which events should be dispatched for notifications.
 *
 * @package Drupal\recurly
 */
class RecurlyWebhookEventDispatcher {

  /**
   * The event dispatcher.
   *
   * @var \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher
   */
  protected $eventDispatcher;

  /**
   * RecurlyWebhookEventDispatcher constructor.
   *
   * @param \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(ContainerAwareEventDispatcher $event_dispatcher) {
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * Dispatches the event associated with the given notification.
   *
   * @param RecurlyPushNotification $notification
   *   The Recurly push notification for which we are
   *   firing an event.
   * @param string $subdomain
   *   The recurly subdomain associated with the notification.
   */
  public function dispatchEvent(RecurlyPushNotification $notification, $subdomain) {

    // Dispatch appropriate event based on notification type.
    switch ($notification->type) {

      // -------------------------------------
      // ------ Account Notifications. ------
      // -------------------------------------
      case 'billing_info_updated_notification':
        $this->eventDispatcher->dispatch(
          RecurlyWebhookEvents::BILLING_INFO_UPDATED,
          new RecurlyBillingInfoUpdatedEvent($notification, $subdomain)
        );
        break;

      case 'billing_info_update_failed_notification':
        $this->eventDispatcher->dispatch(
          RecurlyWebhookEvents::BILLING_INFO_UPDATE_FAILED,
          new RecurlyBillingInfoUpdateFailedEvent($notification, $subdomain)
        );
        break;

      case 'canceled_account_notification':
        $this->eventDispatcher->dispatch(
          RecurlyWebhookEvents::CANCELED_ACCOUNT,
          new RecurlyCanceledAccountEvent($notification, $subdomain)
        );
        break;

      case 'deleted_shipping_address_notification':
        $this->eventDispatcher->dispatch(
          RecurlyWebhookEvents::DELETED_SHIPPING_ADDRESS,
          new RecurlyDeletedShippingAddressEvent($notification, $subdomain)
        );
        break;

      case 'new_account_notification':
        $this->eventDispatcher->dispatch(
          RecurlyWebhookEvents::NEW_ACCOUNT,
          new RecurlyNewAccountEvent($notification, $subdomain)
        );
        break;

      case 'new_shipping_address_notification':
        $this->eventDispatcher->dispatch(
          RecurlyWebhookEvents::NEW_SHIPPING_ADDRESS,
          new RecurlyNewShippingAddressEvent($notification, $subdomain)
        );
        break;

      case 'updated_account_notification':
        $this->eventDispatcher->dispatch(
          RecurlyWebhookEvents::UPDATED_ACCOUNT,
          new RecurlyUpdatedAccountEvent($notification, $subdomain)
        );
        break;

      case 'updated_shipping_address_notification':
        $this->eventDispatcher->dispatch(
          RecurlyWebhookEvents::UPDATED_SHIPPING_ADDRESS,
          new RecurlyUpdatedShippingAddressEvent($notification, $subdomain)
        );
        break;

      // -------------------------------------------
      // ------ Charge Invoice Notifications ------
      // -------------------------------------------
      //
      // None yet defined.
      //
      // -------------------------------------------
      // ------ Credit Invoice Notifications ------
      // -------------------------------------------
      //
      // None yet defined.
      //
      // --------------------------------------
      // ------ Gift Card Notifications ------
      // --------------------------------------
      //
      // None yet defined.
      //
      // ------------------------------------
      // ------ Invoice Notifications ------
      // ------------------------------------
      //
      // None yet defined.
      //
      // ------------------------------------
      // ------ Payment Notifications ------
      // ------------------------------------
      case 'successful_payment_notification':
        $this->eventDispatcher->dispatch(
          RecurlyWebhookEvents::SUCCESSFUL_PAYMENT,
          new RecurlySuccessfulPaymentEvent($notification, $subdomain)
        );
        break;

      // -----------------------------------------
      // ------ Subscription Notifications ------
      // -----------------------------------------
      case 'canceled_subscription_notification':
        $this->eventDispatcher->dispatch(
          RecurlyWebhookEvents::CANCELLED_SUBSCRIPTION,
          new RecurlyCanceledSubscriptionEvent($notification, $subdomain)
        );
        break;

      case 'expired_subscription_notification':
        $this->eventDispatcher->dispatch(
          RecurlyWebhookEvents::EXPIRED_SUBSCRIPTION,
          new RecurlyExpiredSubscriptionEvent($notification, $subdomain)
        );
        break;

      case 'new_subscription_notification':
        $this->eventDispatcher->dispatch(
          RecurlyWebhookEvents::NEW_SUBSCRIPTION,
          new RecurlyNewSubscriptionEvent($notification, $subdomain)
        );
        break;

      case 'paused_subscription_renewal_notification':
        $this->eventDispatcher->dispatch(
          RecurlyWebhookEvents::PAUSED_SUBSCRIPTION_RENEWAL,
          new RecurlyPausedSubscriptionRenewalEvent($notification, $subdomain)
        );
        break;

      case 'reactivated_account_notification':
        $this->eventDispatcher->dispatch(
          RecurlyWebhookEvents::REACTIVATED_ACCOUNT,
          new RecurlyReactivatedAccountEvent($notification, $subdomain)
        );
        break;

      case 'renewed_subscription_notification':
        $this->eventDispatcher->dispatch(
          RecurlyWebhookEvents::RENEWED_SUSBSCRIPTION,
          new RecurlyRenewedSubscriptionEvent($notification, $subdomain)
        );
        break;

      case 'scheduled_subscription_pause_notification':
        $this->eventDispatcher->dispatch(
          RecurlyWebhookEvents::SCHEDULED_SUBSCRIPTION_PAUSE,
          new RecurlyScheduledSubscriptionPauseEvent($notification, $subdomain)
        );
        break;

      case 'subscription_pause_canceled_notification':
        $this->eventDispatcher->dispatch(
          RecurlyWebhookEvents::SUBSCRIPTION_PAUSE_CANCELED,
          new RecurlySubscriptionPauseCanceledEvent($notification, $subdomain)
        );
        break;

      case 'subscription_paused_notification':
        $this->eventDispatcher->dispatch(
          RecurlyWebhookEvents::SUBSCRIPTION_PAUSED,
          new RecurlySubscriptionPausedEvent($notification, $subdomain)
        );
        break;

      case 'subscription_pause_modified_notification':
        $this->eventDispatcher->dispatch(
          RecurlyWebhookEvents::SUBSCRIPTION_PAUSE_MODIFIED,
          new RecurlySubscriptionPauseModifiedEvent($notification, $subdomain)
        );
        break;

      case 'subscription_resumed_notification':
        $this->eventDispatcher->dispatch(
          RecurlyWebhookEvents::SUBSCRIPTION_RESUMED,
          new RecurlySubscriptionResumedEvent($notification, $subdomain)
        );
        break;

      case 'updated_subscription_notification':
        $this->eventDispatcher->dispatch(
          RecurlyWebhookEvents::UPDATED_SUBSCRIPTION,
          new RecurlyUpdatedSubscriptionEvent($notification, $subdomain)
        );
        break;

      // ----------------------------------
      // ------ Usage Notifications ------
      // ----------------------------------
      //
      // None yet defined.
      //
    }
  }

}
