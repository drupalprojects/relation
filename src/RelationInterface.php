<?php

/**
 * @file
 * Contains \Drupal\relation\RelationInterface.
 */

namespace Drupal\relation;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining a Relation entity.
 */
interface RelationInterface extends ContentEntityInterface {

  /**
   * Filters endpoints by entity type.
   *
   * Suitable for direct usage with entity_load_multiple().
   *
   * Example:
   *
   * @code
   * $endpoints = $relation->endpoints();
   * $users = entity_load_multiple('user', $endpoints['user']);
   * @endcode
   *
   * Sample return value:
   *
   * @code
   * array(
   *   "node" => array(5),
   *   "user" => array(2),
   * );
   * @endcode
   *
   * @return array
   *   An array where keys are entity type, and values are arrays containing
   *   entity IDs of endpoints.
   */
  public function endpoints();

  /**
   * Gets the label of the relation type of the given relation.
   *
   * @param $relation
   *   A relation object.
   * @param $reverse
   *   optional: whether to get the reverse label (boolean).
   *
   * @return string|NULL
   *   The label of the relation type, or NULL if the relation type
   *   does not exist.
   */
  function relation_type_label($reverse = FALSE);

}
