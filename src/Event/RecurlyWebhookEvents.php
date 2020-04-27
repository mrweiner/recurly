<?php

namespace Drupal\recurly\Event;

/**
 * Defines events related to Recurly Webhooks.
 *
 * Since there are so many webhook response types,
 * it may be good to further organize the events.
 *
 * @see \Recurly_PushNotification
 * @see \Drupal\recurly\Event\RecurlyBillingInfoUpdatedEvent
 * @see \Drupal\recurly\Event\RecurlyCanceledAccountEvent
 * @see \Drupal\recurly\Event\RecurlySuccessfulPaymentEvent
 * @see \Drupal\recurly\Event\RecurlyNewAccountEvent
 * @see \Drupal\recurly\Event\RecurlyNewSubscriptionEvent
 * @see \Drupal\recurly\Event\RecurlyReactivatedAccountEvent
 */
final class RecurlyWebhookEvents {

  // -------------------------------------
  // ------ Account Notifications. ------
  // -------------------------------------

  /**
   * Name of the event fired for reactivated account notification.
   *
   * @Event
   *
   * @var string
   * @see \Drupal\recurly\Event\notifications\account\RecurlyBillingInfoUpdatedEvent
   */
  const BILLING_INFO_UPDATED = 'recurly.billing_info_updated';

  /**
   * Name of the event fired for Billing Info Update Failed notification.
   *
   * @Event
   *
   * @var string
   * @see \Drupal\recurly\Event\notifications\account\RecurlyBillingInfoUpdateFailedEvent
   */
  const BILLING_INFO_UPDATE_FAILED = 'recurly.billing_info_update_failed';

  /**
   * Name of the event fired for a canceled [sic] account notification.
   *
   * @note Typo due to definition in \Recurly_PushNotification
   *
   * @Event
   *
   * @var string
   * @see \Drupal\recurly\Event\notifications\account\RecurlyCanceledAccountEvent
   */
  const CANCELED_ACCOUNT = 'recurly.canceled_account';

  /**
   * Name of the event fired for a Deleted Shipping Address notification.
   *
   * @Event
   *
   * @var string
   * @see \Drupal\recurly\Event\notifications\account\RecurlyDeletedShippingAddressEvent
   */
  const DELETED_SHIPPING_ADDRESS = 'recurly.deleted_shipping_address';

  /**
   * Name of the event fired for a New Account notification.
   *
   * @Event
   *
   * @var string
   * @see \Drupal\recurly\Event\notifications\account\RecurlyNewAccountEvent
   */
  const NEW_ACCOUNT = 'recurly.new_account';

  /**
   * Name of the event fired for a New Shipping Address notification.
   *
   * @Event
   *
   * @var string
   * @see \Drupal\recurly\Event\notifications\account\RecurlyNewShippingAddressEvent
   */
  const NEW_SHIPPING_ADDRESS = 'recurly.new_shipping_address';

  /**
   * Name of the event fired for an Updated Account notification.
   *
   * @Event
   *
   * @var string
   * @see \Drupal\recurly\Event\notifications\account\RecurlyUpdatedAccountEvent
   */
  const UPDATED_ACCOUNT = 'recurly.updated_account';

  /**
   * Name of the event fired for an Updated Shipping Address notification.
   *
   * @Event
   *
   * @var string
   * @see \Drupal\recurly\Event\notifications\account\RecurlyUpdatedShippingAddressEvent
   */
  const UPDATED_SHIPPING_ADDRESS = 'recurly.updated_shipping_address';

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

  /**
   * Name of the event fired for a successful payment notification.
   *
   * @Event
   *
   * @var string
   * @see \Drupal\recurly\Event\notifications\payment\RecurlySuccessfulPaymentEvent
   */
  const SUCCESSFUL_PAYMENT = 'recurly.successful_payment';


  // -----------------------------------------
  // ------ Subscription Notifications ------
  // -----------------------------------------

  /**
   * Name of the event fired for a Cancelled Subscription notification.
   *
   * @Event
   *
   * @var string
   * @see \Drupal\recurly\Event\notifications\subscription\RecurlyCanceledSubscriptionEvent
   */
  const CANCELLED_SUBSCRIPTION = 'recurly.cancelled_subscription';

  /**
   * Name of the event fired for a Expired Subscription notification.
   *
   * @Event
   *
   * @var string
   * @see \Drupal\recurly\Event\notifications\subscription\RecurlyExpiredSubscriptionEvent
   */
  const EXPIRED_SUBSCRIPTION = 'recurly.expired_subscription';

  /**
   * Name of the event fired for a new subscription notification.
   *
   * @Event
   *
   * @var string
   * @see \Drupal\recurly\Event\notifications\subscription\RecurlyNewSubscriptionEvent
   */
  const NEW_SUBSCRIPTION = 'recurly.new_subscription';

  /**
   * Name of the event fired for a Paused Subscription Renewal notification.
   *
   * @Event
   *
   * @var string
   * @see \Drupal\recurly\Event\notifications\subscription\RecurlyPausedSubscriptionRenewalEvent
   */
  const PAUSED_SUBSCRIPTION_RENEWAL = 'recurly.paused_subscription_renewal';

  /**
   * Name of the event fired for a reactivated account notification.
   *
   * @Event
   *
   * @var string
   * @see \Drupal\recurly\Event\notifications\subscription\RecurlyReactivatedAccountEven
   */
  const REACTIVATED_ACCOUNT = 'recurly.reactivated_account';

  /**
   * Name of the event fired for a Renewed Subscription notification.
   *
   * @Event
   *
   * @var string
   * @see \Drupal\recurly\Event\notifications\subscription\RecurlyRenewedSubscriptionEvent
   */
  const RENEWED_SUSBSCRIPTION = 'recurly.renewed_subscription';

  /**
   * Name of the event fired for a Scheduled Subscription Pause notification.
   *
   * @Event
   *
   * @var string
   * @see \Drupal\recurly\Event\notifications\subscription\RecurlyScheduledSubscriptionPauseEvent
   */
  const SCHEDULED_SUBSCRIPTION_PAUSE = 'recurly.scheduled_subscription_pause';

  /**
   * Name of the event fired for a Subscription Pause Canceled notification.
   *
   * @Event
   *
   * @var string
   * @see \Drupal\recurly\Event\notifications\subscription\RecurlySubscriptionPauseCanceledEvent
   */
  const SUBSCRIPTION_PAUSE_CANCELED = 'recurly.subscription_pause_canceled';

  /**
   * Name of the event fired for a Subscription Paused notification.
   *
   * @Event
   *
   * @var string
   * @see \Drupal\recurly\Event\notifications\subscription\RecurlySubscriptionPausedEvent
   */
  const SUBSCRIPTION_PAUSED = 'recurly.subscription_paused';

  /**
   * Name of the event fired for a Subscription Pause Modified notification.
   *
   * @Event
   *
   * @var string
   * @see \Drupal\recurly\Event\notifications\subscription\RecurlySubscriptionPauseModifiedEvent
   */
  const SUBSCRIPTION_PAUSE_MODIFIED = 'recurly.subscription_pause_modified';

  /**
   * Name of the event fired for a Subscription Resumed notification.
   *
   * @Event
   *
   * @var string
   * @see \Drupal\recurly\Event\notifications\subscription\RecurlySubscriptionResumedEvent
   */
  const SUBSCRIPTION_RESUMED = 'recurly.subscription_resumed';

  /**
   * Name of the event fired for an Updated Subscription notification.
   *
   * @Event
   *
   * @var string
   * @see \Drupal\recurly\Event\notifications\subscription\RecurlySubscriptionResumedEvent
   */
  const UPDATED_SUBSCRIPTION = 'recurly.updated_subscription';

  // ----------------------------------
  // ------ Usage Notifications ------
  // ----------------------------------
  //
  // None yet defined.
  //
}
