<?php

namespace Drupal\recurly\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides local task definitions for all entity bundles.
 */
class RecurlyLocalTask extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The config service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Creates a RecurlyLocalTask object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The translation manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config service.
   */
  public function __construct(
    EntityManagerInterface $entity_manager,
    TranslationInterface $string_translation,
    ConfigFactoryInterface $config_factory) {
    $this->entityManager = $entity_manager;
    $this->stringTranslation = $string_translation;
    $this->config = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity.manager'),
      $container->get('string_translation'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    // We are creating our local menu tasks here.
    // $base_plugin_definition contains our base class
    // (\Drupal\Core\Menu\LocalTaskDefault), the deriver class (this),
    // provider (recurly) and id (recurly.entities). See recurly.links.task.yml.
    $this->derivatives = [];
    // Get the entity type associated with recurly.
    $entity_type_id = $this->config->get('recurly.settings')->get('recurly_entity_type');
    // Get all the plugins available to the entity (block, menu, user, etc.).
    $entity_manager_definitions = $this->entityManager->getDefinitions();
    // Pull our entity type.
    $entity_type = $entity_manager_definitions[$entity_type_id];
    // Get the canonical path for this entity and recurly.
    $has_canonical_path = $entity_type->hasLinkTemplate('recurly-subscriptionlist');
    // If we have the canonical path for this entity type, add tabs.
    if ($has_canonical_path) {
      $this->derivatives["$entity_type_id.recurly_tab"] = [
        'route_name' => "entity.$entity_type_id.recurly_subscriptionlist",
        'title' => $this->t('Subscription'),
        'base_route' => "entity.$entity_type_id.canonical",
        'weight' => 100,
      ];
      $this->derivatives["$entity_type_id.recurly_signup_tab"] = [
        'route_name' => "entity.$entity_type_id.recurly_signup",
        'title' => $this->t('Signup'),
        'parent_id' => "recurly.entities:$entity_type_id.recurly_tab",
        'weight' => 50,
      ];
      $this->derivatives["$entity_type_id.invoices_tab"] = [
        'route_name' => "entity.$entity_type_id.recurly_invoices",
        'title' => $this->t('Invoices'),
        'parent_id' => "recurly.entities:$entity_type_id.recurly_tab",
        'weight' => 300,
      ];
      $this->derivatives["$entity_type_id.coupon_tab"] = [
        'route_name' => "entity.$entity_type_id.recurly_coupon",
        'title' => $this->t('Redeem Coupon'),
        'parent_id' => "recurly.entities:$entity_type_id.recurly_tab",
        'weight' => 400,
      ];
      $this->derivatives["$entity_type_id.reactivate_tab"] = [
        'route_name' => "entity.$entity_type_id.recurly_reactivatelatest",
        'title' => $this->t('Reactivate'),
        'parent_id' => "recurly.entities:$entity_type_id.recurly_tab",
        'weight' => 500,
      ];
    }
    // Add the tabs to $base_plugin_definition.
    foreach ($this->derivatives as &$entry) {
      $entry += $base_plugin_definition;
    }
    return $this->derivatives;
  }

}
