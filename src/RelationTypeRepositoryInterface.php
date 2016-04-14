<?php

namespace Drupal\relation;

/**
 * Relation Type Repository Interface.
 */
interface RelationTypeRepositoryInterface {

  /**
   * Returns the relation types that can have the given entity as an endpoint.
   *
   * @param string $entity_type
   *   The entity type of the endpoint.
   * @param string $bundle
   *   The bundle of the endpoint.
   * @param string $endpoint
   *   (optional) the type of endpoint. This is only used for directional
   *   relation types. Possible options are 'source', 'target', or 'both'.
   *
   * @return array
   *   An array of relation types
   */
  public function getAvailable($entity_type, $bundle, $endpoint = 'source');

}
