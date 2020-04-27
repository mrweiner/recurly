<?php

namespace Drupal\recurly;

use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Link;

/**
 * Service to abstract preprocess hooks.
 */
class RecurlyPreprocess {

  /**
   * The formatting service.
   *
   * @var \Drupal\recurly\RecurlyFormatManager
   */
  protected $recurlyFormatter;

  /**
   * The Recurly settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $recurlySettings;

  /**
   * Constructs a \Drupal\recurly\RecurlyPreprocess object.
   *
   * @param \Drupal\recurly\RecurlyFormatManager $recurly_formatter
   *   A Recurly formatter object.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config service.
   */
  public function __construct(RecurlyFormatManager $recurly_formatter, ConfigFactoryInterface $config_factory) {
    $this->recurlyFormatter = $recurly_formatter;
    $this->recurlySettings = $config_factory->get('recurly.settings');
  }

  /**
   * Implements hook_preprocess_recurly_subscription_plan_select().
   */
  public function preprocessRecurlySubscriptionPlanSelect(array &$variables) {
    $plans = $variables['plans'];
    $currency = $variables['currency'];
    $entity_type = $variables['entity_type'];
    $entity = $variables['entity'];
    $subscriptions = $variables['subscriptions'];
    $subscription_id = $variables['subscription_id'];

    $current_subscription = NULL;
    foreach ($subscriptions as $subscription) {
      if ($subscription->uuid === $subscription_id) {
        $current_subscription = $subscription;
        break;
      }
    }

    // If currency is undefined, use the subscription currency.
    if ($current_subscription && empty($currency)) {
      $currency = $current_subscription->currency;
      $variables['currency'] = $currency;
    }

    // Prepare an easy to loop-through list of subscriptions.
    $variables['filtered_plans'] = [];
    foreach ($plans as $plan_code => $plan) {
      $setup_fee_amount = NULL;
      foreach ($plan->setup_fee_in_cents as $setup_currency) {
        if ($setup_currency->currencyCode === $currency) {
          $setup_fee_amount = $this->recurlyFormatter->formatCurrency($setup_currency->amount_in_cents, $setup_currency->currencyCode, TRUE);
          break;
        }
      }
      $unit_amount = NULL;
      foreach ($plan->unit_amount_in_cents as $unit_currency) {
        if ($unit_currency->currencyCode === $currency) {
          $unit_amount = $this->recurlyFormatter->formatCurrency($unit_currency->amount_in_cents, $unit_currency->currencyCode, TRUE);
          break;
        }
      }
      $variables['filtered_plans'][$plan_code] = [
        'plan_code' => Html::escape($plan_code),
        'name' => Html::escape($plan->name),
        'description' => Html::escape($plan->description),
        'setup_fee' => $setup_fee_amount,
        'amount' => $unit_amount,
        'plan_interval' => $this->recurlyFormatter->formatPriceInterval($unit_amount, $plan->plan_interval_length, $plan->plan_interval_unit, TRUE),
        'trial_interval' => $plan->trial_interval_length ? $this->recurlyFormatter->formatPriceInterval(NULL, $plan->trial_interval_length, $plan->trial_interval_unit, TRUE) : NULL,
        'signup_url' => recurly_url('subscribe', [
          'entity_type' => $entity_type,
          'entity' => $entity,
          'plan_code' => $plan_code,
          'currency' => $currency,
        ]),
        'change_url' => $current_subscription ? recurly_url('change_plan', [
          'entity_type' => $entity_type,
          'entity' => $entity,
          'subscription' => $current_subscription,
          'plan_code' => $plan_code,
        ]) : NULL,
        'selected' => FALSE,
      ];

      // If we have a pending subscription, make that its shown as selected
      // rather than the current active subscription. This should allow users to
      // switch back to a previous plan after making a pending switch to another
      // one.
      foreach ($subscriptions as $subscription) {
        if (!empty($subscription->pending_subscription)) {
          if ($subscription->pending_subscription->plan->plan_code === $plan_code) {
            $variables['filtered_plans'][$plan_code]['selected'] = TRUE;
          }
        }
        elseif ($subscription->plan->plan_code === $plan_code) {
          $variables['filtered_plans'][$plan_code]['selected'] = TRUE;
        }
      }
    }

    // Check if this is an account that is creating a new subscription.
    $variables['expired_subscriptions'] = FALSE;
    if (!empty($entity) && recurly_account_load(['entity_type' => $entity_type, 'entity_id' => $entity->id()])) {
      $variables['expired_subscriptions'] = empty($subscriptions);
    }
  }

  /**
   * Implements hook_preprocess_recurly_subscription_cancel_confirm().
   */
  public function preprocessRecurlySubscriptionCancelConfirm(array &$variables) {
    $variables['subscription'] = $variables['form']['#subscription'];
    parse_str($this->getRequest()->getQueryString(), $query_array);
    $variables['past_due'] = isset($query_array['past_due']) && $query_array['past_due'] === '1';
  }

  /**
   * Implements hook_preprocess_recurly_invoice_list().
   */
  public function preprocessRecurlyInvoiceList(array &$variables) {
    $invoices = $variables['invoices'];
    $entity_type = $variables['entity_type'];
    $entity = $variables['entity'];

    $header = [t('Number'), t('Date'), t('Total')];
    $rows = [];
    foreach ($invoices as $invoice) {
      $status = ' ';
      if ($invoice->state === 'past_due') {
        $status .= t('(Past due)');
      }
      elseif ($invoice->state === 'failed') {
        $status .= t('(Failed)');
      }

      $row = [];
      $row[] = Link::createFromRoute($invoice->invoice_number . $status, "entity.$entity_type.recurly_invoice", [
        $entity_type => $entity->id(),
        'invoice_number' => $invoice->invoice_number,
      ]);

      $row[] = $this->recurlyFormatter->formatDate($invoice->created_at);
      $row[] = $this->recurlyFormatter->formatCurrency($invoice->total_in_cents, $invoice->currency);
      $rows[] = [
        'data' => $row,
        'class' => [Html::escape($invoice->state)],
      ];
    }

    $variables['table'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => ['class' => ['invoice-list']],
      '#sticky' => FALSE,
    ];
  }

  /**
   * Implements hook_preprocess_recurly_invoice().
   */
  public function preprocessRecurlyInvoice(array &$variables) {
    $entity_type_id = $this->recurlySettings->get('recurly_entity_type');
    $invoice = $variables['invoice'];
    $invoice_account = $variables['invoice_account'];
    $entity = $variables['entity'];
    $billing_info = isset($invoice->billing_info) ? $invoice->billing_info->get() : NULL;

    $due_amount = $invoice->state !== 'collected' ? $invoice->total_in_cents : 0;
    $paid_amount = $invoice->state === 'collected' ? $invoice->total_in_cents : 0;
    $variables += [
      'invoice_date' => $this->recurlyFormatter->formatDate($invoice->created_at),
      'pdf_link' => Link::createFromRoute(t('View PDF'), "entity.$entity_type_id.recurly_invoicepdf", [
        $entity_type_id => $entity->id(),
        'invoice_number' => $invoice->invoice_number,
      ]),
      'subtotal' => $this->recurlyFormatter->formatCurrency($invoice->subtotal_in_cents, $invoice->currency),
      'total' => $this->recurlyFormatter->formatCurrency($invoice->total_in_cents, $invoice->currency),
      'due' => $this->recurlyFormatter->formatCurrency($due_amount, $invoice->currency),
      'paid' => $this->recurlyFormatter->formatCurrency($paid_amount, $invoice->currency),
      'billing_info' => isset($billing_info),
      'line_items' => [],
      'transactions' => [],
    ];
    if ($billing_info) {
      $variables += [
        'first_name' => Html::escape($billing_info->first_name),
        'last_name' => Html::escape($billing_info->last_name),
        'address1' => Html::escape($billing_info->address1),
        'address2' => isset($billing_info->address2) ? Html::escape($billing_info->address2) : NULL,
        'city' => Html::escape($billing_info->city),
        'state' => Html::escape($billing_info->state),
        'zip' => Html::escape($billing_info->zip),
        'country' => Html::escape($billing_info->country),
      ];
    }
    foreach ($invoice->line_items as $line_item) {
      $variables['line_items'][$line_item->uuid] = [
        'start_date' => $this->recurlyFormatter->formatDate($line_item->start_date),
        'end_date' => $this->recurlyFormatter->formatDate($line_item->end_date),
        'description' => Html::escape($line_item->description),
        'amount' => $this->recurlyFormatter->formatCurrency($line_item->total_in_cents, $line_item->currency),
      ];
    }
    $transaction_total = 0;
    foreach ($invoice->transactions as $transaction) {
      $variables['transactions'][$transaction->uuid] = [
        'date' => $this->recurlyFormatter->formatDate($transaction->created_at),
        'description' => $this->recurlyFormatter->formatTransactionStatus($transaction->status),
        'amount' => $this->recurlyFormatter->formatCurrency($transaction->amount_in_cents, $transaction->currency),
      ];
      if ($transaction->status == 'success') {
        $transaction_total += $transaction->amount_in_cents;
      }
      else {
        $variables['transactions'][$transaction->uuid]['amount'] = '(' . $variables['transactions'][$transaction->uuid]['amount'] . ')';
      }
    }
    $variables['transactions_total'] = $this->recurlyFormatter->formatCurrency($transaction_total, $invoice->currency);
  }

}
