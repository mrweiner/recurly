<?php

namespace Drupal\recurly\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Recurly redeem coupon form.
 */
class RecurlyRedeemCouponForm extends RecurlyNonConfigForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'recurly_redeem_coupon_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, RouteMatchInterface $route_match = NULL) {
    $entity_type_id = $this->config('recurly.settings')->get('recurly_entity_type');
    $entity = $route_match->getParameter($entity_type_id);
    $entity_type = $entity->getEntityType()->getLowercaseLabel();
    $form['#entity_type'] = $entity_type;
    $form['#entity'] = $entity;

    // Check the user's account for any current coupons.
    $account = recurly_account_load(['entity_type' => $form['#entity_type'], 'entity_id' => $entity->id()]);
    $confirming_replacement_coupon = ($form_state->get('confirm') && $form_state->get('coupon') && $form_state->get('existing_coupon'));
    $form_state->set('account', $account);

    // The output of this form varies based on if the user has an existing
    // coupon, and if they need to confirm replacing their existing coupon with
    // a new one.
    if ($confirming_replacement_coupon) {
      $form_state->set('confirmed', TRUE);
      $help = '<p>' . $this->t('Your account already has a coupon that will be applied to your next invoice. Are you sure you want to replace your existing coupon "@old_coupon" with "@new_coupon"? You may not be able to use your previous coupon again.',
        [
          '@old_coupon' => $this->recurlyFormatter->formatCoupon($form_state->get('existing_coupon'), $form_state->get('existing_redemption')->currency),
          '@new_coupon' => $this->recurlyFormatter->formatCoupon($form_state->get('coupon'), $form_state->getValue('coupon_currency')),
        ]) . '</p>';
    }
    elseif ($account->redemption) {
      $existing_coupon_redemption = $account->redemption->get();
      $form_state->set('existing_redemption', $existing_coupon_redemption);
      $form_state->set('existing_coupon', $existing_coupon_redemption->coupon->get());

      $help = '<p>' . $this->t('Your next invoice will have the following coupon applied:') . ' <strong>' . $this->recurlyFormatter->formatCoupon($form_state->get('existing_coupon'), $form_state->get('existing_redemption')->currency) . '</strong></p>';
      $help .= '<p>' . $this->t('Please note that only one coupon can be redeemed per invoice.') . '</p>';
    }
    else {
      $help = '<p>' . $this->t('Enter a coupon code below and it will be applied to your next invoice.') . '</p>';
    }

    $form['help'] = [
      '#markup' => $help,
    ];
    $form['coupon_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Coupon code'),
      '#required' => TRUE,
      '#default_value' => $form_state->get('coupon') ? $form_state->get('coupon')->coupon_code : '',
      '#access' => !$confirming_replacement_coupon,
      '#size' => 20,
    ];
    $form['coupon_currency'] = [
      '#type' => 'select',
      '#title' => $this->t('Coupon currency'),
      '#options' => array_combine(array_keys(recurly_currency_list()), array_keys(recurly_currency_list())),
      '#default_value' => $this->config('recurly.settings')->get('recurly_default_currency'),
      '#description' => $this->t('If your coupon specifies a currency, select it here. Not all coupons work in all currencies.'),
      '#access' => !$confirming_replacement_coupon,
    ];
    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $confirming_replacement_coupon ? $this->t('Replace previous coupon') : $this->t('Redeem coupon'),
    ];
    $form['actions']['cancel'] = [
      '#type' => 'markup',
      '#markup' => '<a href="' . recurly_url('redeem_coupon', ['entity_type' => $entity_type, 'entity' => $entity])->toString() . '">' . $this->t('Cancel') . '</a>',
      '#access' => $confirming_replacement_coupon,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Query Recurly to make sure this is a valid coupon code.
    try {
      $coupon = \Recurly_Coupon::get($form_state->getValue('coupon_code'));
      $form_state->set('coupon', $coupon);
    }
    catch (\Recurly_NotFoundError $e) {
      $form_state->setErrorByName('coupon_code', $this->t('The coupon code you have entered is not valid.'));
      return;
    }

    // Check that the coupon is available in the specified currency.
    if ($form_state->get('coupon') && !in_array($form_state->get('coupon')->discount_type, ['percent', 'free_trial'])) {
      if (!$form_state->get('coupon')->discount_in_cents->offsetExists($form_state->getValue('coupon_currency'))) {
        $form_state->setErrorByName('coupon_currency', $this->t('The coupon code you have entered is not valid in @currency currency.', ['@currency' => $form_state->getValue('coupon_currency')]));
        return;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $coupon = $form_state->get('coupon');
    $account = $form_state->get('account');
    if (empty($account->redemption)) {
      $account = recurly_account_load(['entity_type' => $form['#entity_type'], 'entity_id' => $form['#entity']->id()]);
    }
    if ($account && $coupon && $account->redemption) {
      // If the user already has a coupon, rebuild the form and ask for
      // confirmation.
      if (!$form_state->get('confirmed')) {
        $this->messenger()->addWarning($this->t('You already have an active coupon, are you sure you want to replace it?'));
        $form_state->set('confirm', TRUE);
        $form_state->setRebuild(TRUE);
        return;
      }
      // If confirmed, delete the existing coupon before redeeming the new one.
      else {
        if ($existing_coupon_redemption = $form_state->get('existing_redemption')) {
          try {
            $existing_coupon_redemption = $account->redemption->get();
            $existing_coupon_redemption->delete();
          }
          catch (\Recurly_NotFoundError $e) {
            $this->logger('recurly')->error('Unable to remove existing coupon redemption: @error', ['@error' => $e->getMessage()]);
            $this->messenger()->addError('Unable to remove existing coupon.');
            return;
          }
        }
      }
    }

    // Now redeem the new coupon.
    try {
      $response = $coupon->redeemCoupon($account->account_code, $form_state->getValue('coupon_currency'));
    }
    catch (\Exception $e) {
      $this->logger('recurly')->error('@error', ['@error' => $e->getMessage()]);
      $this->messenger()->addError($e->getMessage());
      return;
    }

    // If the response is NULL that means for one reason or another the coupon
    // could not be applied. This is most likely because the code has already
    // reached the maximum number of redemptions or has expired.
    if (is_null($response)) {
      $this->messenger()->addError($this->t('Unable to redeem the coupon @code, the coupon may no longer be valid.', ['@code' => $coupon->coupon_code]));
    }
    else {
      $this->messenger()->addMessage($this->t('The coupon @coupon has been applied to your account and will be redeemed the next time your subscription renews.', [
        '@coupon' => $this->recurlyFormatter->formatCoupon($coupon, $form_state->getValue(['coupon_currency']), FALSE),
      ]));
    }
  }

}
