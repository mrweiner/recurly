<?php

namespace Drupal\recurlyjs\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\recurlyjs\RecurlyJsEvents;
use Drupal\recurlyjs\Event\SubscriptionAlter;
use Drupal\recurlyjs\Event\SubscriptionCreated;

/**
 * RecurlyJS subscribe form.
 */
class RecurlyJsSubscribeForm extends RecurlyJsFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'recurlyjs_subscribe';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type = NULL, $entity = NULL, $plan_code = NULL, $currency = NULL) {
    if (!$entity_type || !$entity || !$plan_code) {
      // @TODO: Replace exception.
      throw new Exception();
    }

    $form = parent::buildForm($form, $form_state);
    $form['#entity_type'] = $entity_type;
    $form['#entity'] = $entity;
    $form['#plan_code'] = $plan_code;
    $form['#currency'] = $currency ?: $this->config('recurly.settings')->get('recurly_default_currency') ?: 'USD';

    if ($this->config('recurlyjs.settings')->get('recurlyjs_enable_coupons')) {
      $form['coupon_code'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Coupon Code'),
        '#description' => $this->t('Recurly coupon code to be applied to subscription.'),
        '#element_validate' => ['::validateCouponCode'],
        '#weight' => -250,
      ];
    }
    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Purchase'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity_type = $form['#entity_type'];
    $entity = $form['#entity'];
    $plan_code = $form['#plan_code'];
    $currency = $form['#currency'];
    $recurly_token = $form_state->getValue('recurly-token');
    $coupon_code = $form_state->getValue('coupon_code');
    $recurly_account = recurly_account_load(['entity_type' => $entity_type, 'entity_id' => $entity->id()]);
    if (!$recurly_account) {
      $recurly_account = new \Recurly_Account();
      $recurly_account->first_name = Html::escape($form_state->getValue('first_name'));
      $recurly_account->last_name = Html::escape($form_state->getValue('last_name'));

      if ($entity_type == 'user') {
        $recurly_account->email = $entity->getEmail();
        $recurly_account->username = $entity->getAccountName();
      }

      // Account code is the only property required for account creation.
      // https://dev.recurly.com/docs/create-an-account.
      $recurly_account->account_code = $entity_type . '-' . $entity->id();
    }

    $subscription = new \Recurly_Subscription();
    $subscription->account = $recurly_account;
    $subscription->plan_code = $plan_code;
    $subscription->currency = $currency;
    $subscription->coupon_code = $coupon_code;

    // Allow other modules the chance to alter the new Recurly Subscription
    // object before it is saved.
    $event = new SubscriptionAlter($subscription, $entity, $plan_code);
    $this->eventDispatcher->dispatch(RecurlyJsEvents::SUBSCRIPTION_ALTER, $event);
    $subscription = $event->getSubscription();

    // Billing info is based on the token we retrieved from the Recurly JS API
    // and should only contain the token in this case. We add this after the
    // above alter hook to ensure it's not modified.
    $subscription->account->billing_info = new \Recurly_BillingInfo();
    $subscription->account->billing_info->token_id = $recurly_token;

    try {
      // This saves all of the data assembled above in addition to creating a
      // new subscription record.
      $subscription->create();
    }
    catch (\Recurly_ValidationError $e) {
      // There was an error validating information in the form. For example,
      // credit card was declined. We don't need to log these in Drupal, you can
      // find the errors logged within Recurly.
      $this->messenger()->addError($this->t('<strong>Unable to create subscription:</strong><br/>@error', ['@error' => $e->getMessage()]));
      $form_state->setRebuild(TRUE);
      return;
    }
    catch (\Recurly_Error $e) {
      // Catch any non-validation errors. This will be things like unable to
      // contact Recurly API, or lower level errors. Display a generic message
      // to the user letting them know there was an error and then log the
      // detailed version. There's probably nothing a user can do to correct
      // these errors so we don't need to display the details.
      $this->logger('recurlyjs')->error('Unable to create subscription. Received the following error: @error', ['@error' => $e->getMessage()]);
      $this->messenger()->addError($this->t('Unable to create subscription.'));
      $form_state->setRebuild(TRUE);
      return;
    }

    // Allow other modules to react to the new subscription being created.
    $event = new SubscriptionCreated($subscription, $entity, $plan_code);
    $this->eventDispatcher->dispatch(RecurlyJsEvents::SUBSCRIPTION_CREATED, $event);
    $subscription = $event->getSubscription();

    $this->messenger()->addMessage($this->t('Account upgraded to @plan!', ['@plan' => $subscription->plan->name]));
    // Save the account locally immediately so that subscriber information may
    // be retrieved when the user is directed back to the /subscription tab.
    try {
      $account = $subscription->account->get();
      recurly_account_save($account, $entity_type, $entity->id());
    }
    catch (\Recurly_Error $e) {
      $this->logger('recurlyjs')->error('New subscriber account could not be retreived from Recurly. Received the following error: @error', ['@error' => $e->getMessage()]);
    }
    return $form_state->setRedirect("entity.$entity_type.recurly_subscriptionlist", [
      $entity->getEntityType()->getLowercaseLabel() => $entity->id(),
    ]);
  }

  /**
   * Element validate callback.
   */
  public function validateCouponCode($element, &$form_state, $form) {
    $coupon_code = $form_state->hasValue('coupon_code') ? $form_state->getValue('coupon_code') : NULL;
    if (!$coupon_code) {
      return;
    }
    $currency = $form['#currency'];
    $plan_code = $form['#plan_code'];

    // Query Recurly to make sure this is a valid coupon code.
    try {
      $coupon = \Recurly_Coupon::get($coupon_code);
    }
    catch (\Recurly_NotFoundError $e) {
      $form_state->setError($element, $this->t('The coupon code you have entered is not valid.'));
      return;
    }
    // Check that the coupon is available in the specified currency.
    if ($coupon && !in_array($coupon->discount_type, ['percent', 'free_trial'])) {
      if (!$coupon->discount_in_cents->offsetExists($currency)) {
        $form_state->setError($element, $this->t('The coupon code you have entered is not valid in @currency.', ['@currency' => $currency]));
        return;
      }
    }
    // Check the the coupon is valid for the specified plan.
    if ($coupon && !$this->couponValidForPlan($coupon, $plan_code)) {
      $form_state->setError($element, $this->t('The coupon code you have entered is not valid for the specified plan.'));
      return;
    }
  }

  /**
   * Validate Recurly coupon against a specified plan.
   *
   * @todo Move to recurly.module?
   *
   * @param \Recurly_Coupon $recurly_coupon
   *   A Recurly coupon object.
   * @param string $plan_code
   *   A Recurly plan code.
   *
   * @return BOOL
   *   TRUE if the coupon is valid for the specified plan, else FALSE.
   */
  protected function couponValidForPlan(\Recurly_Coupon $recurly_coupon, $plan_code) {
    return ($recurly_coupon->applies_to_all_plans || in_array($plan_code, $recurly_coupon->plan_codes));
  }

}
