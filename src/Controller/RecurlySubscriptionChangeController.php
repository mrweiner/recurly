<?php

namespace Drupal\recurly\Controller;

use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Recurly change subscription controller.
 */
class RecurlySubscriptionChangeController extends RecurlyController {

  /**
   * Change the existing to the specified subscription.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   A RouteMatch object.
   *   Contains information about the route and the entity being acted on.
   *
   * @return mixed
   *   Returns \Drupal\Core\Form\FormBuilderInterface or a string.
   */
  public function changePlan(RouteMatchInterface $route_match) {
    $entity_type_id = $this->config('recurly.settings')->get('recurly_entity_type');
    $entity = $route_match->getParameter($entity_type_id);
    $subscription_id = $route_match->getParameter('subscription_id');
    $new_plan_code = $route_match->getParameter('new_plan_code');

    // Load the subscription.
    try {
      $subscription = \Recurly_Subscription::get($subscription_id);
    }
    catch (\Recurly_NotFoundError $e) {
      $this->messenger()->addMessage($this->t('Subscription not found.'));
      throw new NotFoundHttpException();
    }

    // Load the old plan.
    try {
      $previous_plan = \Recurly_Plan::get($subscription->plan->plan_code);
    }
    catch (\Recurly_NotFoundError $e) {
      $this->messenger()->addMessage($this->t('Plan code "@plan" not found.', ['@plan' => $subscription->plan->plan_code]));
      throw new NotFoundHttpException();
    }

    // Load the new plan.
    try {
      $new_plan = \Recurly_Plan::get($new_plan_code);
    }
    catch (\Recurly_NotFoundError $e) {
      $this->messenger()->addMessage($this->t('Plan code "@plan" not found.', ['@plan' => $new_plan_code]));
      throw new NotFoundHttpException();
    }

    $entity_type = $entity->getEntityType()->getLowercaseLabel();
    return $this->formBuilder()->getForm('Drupal\recurly\Form\RecurlySubscriptionChangeConfirmForm', $entity_type, $entity, $subscription, $previous_plan, $new_plan);
  }

}
