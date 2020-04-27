<?php

namespace Drupal\recurly\Controller;

use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Recurly cancel subscription controller.
 */
class RecurlySubscriptionCancelController extends RecurlyController {

  /**
   * Cancel the specified subscription.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   A RouteMatch object.
   *   Contains information about the route and the entity being acted on.
   * @param string $subscription_id
   *   The UUID of the current subscription if changing the plan on an existing
   *   subscription.
   *
   * @return mixed
   *   Returns \Drupal\Core\Form\FormBuilderInterface or a string.
   */
  public function subscriptionCancel(RouteMatchInterface $route_match, $subscription_id) {
    $entity_type_id = $this->config('recurly.settings')->get('recurly_entity_type');
    $entity = $route_match->getParameter($entity_type_id);

    $entity_type = $entity->getEntityType()->getLowercaseLabel();
    // Load the subscription.
    if ($subscription_id === 'latest') {
      $local_account = recurly_account_load([
        'entity_type' => $entity_type,
        'entity_id' => $entity->id(),
      ], TRUE);
      $subscriptions = recurly_account_get_subscriptions($local_account->account_code, 'active');
      $subscription = reset($subscriptions);
    }
    else {
      try {
        $subscription = \Recurly_Subscription::get($subscription_id);
      }
      catch (\Recurly_NotFoundError $e) {
        $this->messenger()->addMessage($this->t('Subscription not found'));
        throw new NotFoundHttpException();
      }
    }

    return $this->formBuilder()->getForm('Drupal\recurly\Form\RecurlySubscriptionCancelConfirmForm', $entity_type, $entity, $subscription);
  }

}
