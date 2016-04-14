<?php

namespace Drupal\relation\Entity;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\relation\RelationTypeRepositoryInterface;

/**
 * Provides mechanism for retrieving available relations types.
 */
class RelationTypeRepository implements RelationTypeRepositoryInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new RelationTypeRepository.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailable($entity_type, $bundle, $endpoint = 'source') {
    $bundle_key = $entity_type . ':' . $bundle;
    $all_bundle_key = $entity_type . ':*';
    $available_types = array();
    $relation_types = $this->entityTypeManager->getStorage('relation_type')->loadMultiple();
    foreach ($relation_types as $relation_type) {
      $available = FALSE;
      if ($endpoint == 'source' || $endpoint == 'both') {
        if (in_array($bundle_key, $relation_type->source_bundles) || in_array($all_bundle_key, $relation_type->source_bundles)) {
          $available = TRUE;
        }
      }
      if ($endpoint == 'target' || $endpoint == 'both') {
        if (in_array($bundle_key, $relation_type->target_bundles) || in_array($all_bundle_key, $relation_type->target_bundles)) {
          $available = TRUE;
        }
      }
      if ($available) {
        $available_types[] = $relation_type;
      }
    }

    return $available_types;
  }

}
