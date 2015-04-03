<?php

/**
 * @file
 * Contains \Drupal\relation\Entity\RelationType.
 */

namespace Drupal\relation\Entity;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\relation\RelationTypeInterface;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\Annotation\EntityType;

/**
 * Defines relation type entity.
 *
 * Properties.
 *
 *  - relation_type (required): Relation type machine name (string).
 *  - label: Relation type human-readable name (string). Defaults to
 *    duplicating relation_type.
 *  - directional: whether relation is directional (boolean). Defaults to
 *    FALSE.
 *  - transitive: whether relation is transitive (boolean). Defaults to FALSE.
 *  - r_unique: whether relations of this type are unique (boolean). Defaults
 *    to FALSE.
 *  - min_arity: minimum number of entities in relations of this type
 *    (int >= 2). Defaults to 2.
 *  - max_arity: maximum number of entities in relations of this type
 *    (int >= min_arity). Defaults to 2.
 *  - source_bundles: array containing allowed bundle keys. This is used for
 *    both directional and non-directional relations. Bundle key arrays are
 *    of the form 'entity:bundle', eg. 'node:article', or 'entity:*' for all
 *    bundles of the type.
 *  - target_bundles: array containing arrays allowed target bundle keys.
 *    This is the same format as source_bundles, but is only used for
 *    directional relations.
 *
 * @ConfigEntityType(
 *   id = "relation_type",
 *   label = @Translation("Relation type"),
 *   module = "relation",
 *   handlers = {
 *     "storage" = "Drupal\Core\Config\Entity\ConfigEntityStorage",
 *     "render" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\relation\RelationTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\relation\RelationTypeForm",
 *       "edit" = "Drupal\relation\RelationTypeForm",
 *       "delete" = "Drupal\relation\Form\RelationTypeDeleteConfirm"
 *     },
 *   },
 *   admin_permission = "administer relation types",
 *   config_prefix = "type",
 *   bundle_of = "relation",
 *   entity_keys = {
 *     "id" = "relation_type",
 *     "label" = "label"
 *   },
 *   links = {
 *     "edit-form" = "/admin/structure/relation/manage/{relation_type}",
 *     "delete-form" = "/admin/structure/relation/manage/{relation_type}/delete",
 *     "collection" = "/admin/structure/relation",
 *   }
 * )
 */
class RelationType extends ConfigEntityBundleBase implements RelationTypeInterface {

  /**
   * The machine name of this relation type.
   *
   * @var string
   */
  public $relation_type;

  /**
   * The human-readable name of this type.
   *
   * @var string
   */
  public $label;

  /**
   * The reverse human-readable name of this type.
   *
   * Only used for directional relations.
   *
   * @var string
   */
  public $reverse_label;

  /**
   * Whether this relation type is directional. If not, all indexes are ignored.
   *
   * @var bool
   */
  public $directional  = FALSE;

  /**
   * Whether this relation type is transitive.
   *
   * @var bool
   */
  public $transitive  = FALSE;

  /**
   * Whether relations of this type are unique.
   *
   * @var bool
   */
  public $r_unique  = FALSE;

  /**
   * The minimum number of rows that can make up a relation of this type.
   *
   * @var int
   */
  public $min_arity  = 2;

  /**
   * The maximum number of rows that can make up a relation of this type. Similar to field cardinality.
   *
   * @var int
   */
  public $max_arity  = 2;

  /**
   * List of entity bundles that can be used as relation sources.
   *
   * @var array
   */
  public $source_bundles = array();

  /**
   * List of entity bundles that can be used as relation targets.
   *
   * @var array
   */
  public $target_bundles = array();

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->relation_type;
  }

  /**
   * {@inheritdoc}
   */
  public function label($langcode = NULL) {
    return $this->label;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    if (!isset($this->relation_type)) {
      throw new EntityMalformedException('Bundle property must be set on relation_type entities.');
    }

    if (empty($this->label)) {
      $this->label = $this->relation_type;
    }

    // Directional relations should have a reverse label, but if they don't,
    // or if they are symmetric:
    if (empty($this->reverse_label)) {
      $this->reverse_label = $this->label;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // Ensure endpoints field is attached to relation type.
    if (!$update) {
      relation_add_endpoint_field($this);
    }
    else {
      // Clear the cached field definitions as some settings affect the field
      // definitions.
      $this->entityManager()->clearCachedFieldDefinitions();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getBundles($direction = NULL) {
    $pairs = array();

    if ((!$direction || $direction == 'source') && is_array($this->source_bundles)) {
      $pairs += $this->source_bundles;
    }

    if ((!$direction || $direction == 'target') && is_array($this->target_bundles)) {
      $pairs += $this->target_bundles;
    }

    $bundles = array();
    foreach ($pairs as $pair) {
      list($entity_type_id, $bundle) = explode(':', $pair, 2);
      $bundles[$entity_type_id][$bundle] = $bundle;
    }
    return $bundles;
  }

}
