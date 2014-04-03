<?php

/**
 * @file
 * Definition of Drupal\relation\Tests\RelationTestBase.
 */

namespace Drupal\relation\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Provides common helper methods for Taxonomy module tests.
 */
abstract class RelationTestBase extends WebTestBase {
  public static $modules = array('relation'
  // Loading all dependencies since d.o testbot is fussy.
    ,'relation_endpoint', 'field', 'field_ui', 'relation_ui', 'block', 'relation_dummy_field'
  );

  protected $sleep = FALSE;

  function setUp() {
    parent::setUp();
    // Create Basic page and Article node types.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType(array('type' => 'page', 'name' => 'Basic page'));
      $this->drupalCreateContentType(array('type' => 'article', 'name' => 'Article'));
    }

    // Defines entities.
    $this->createRelationNodes();
    $this->createRelationUsers();

    // Defines relation types.
    $this->createRelationTypes();

    // Defines end points.
    $this->createRelationEndPoints();

    // Defines relations.
    $this->createRelationSymmetric();
    $this->createRelationDirectional();
    $this->createRelationOctopus();
    $this->createRelationUnary();
  }

  /**
   * Creates nodes.
   */
  function createRelationNodes() {
    $this->node1 = $this->drupalCreateNode(array('type' => 'article', 'promote' => 1, 'title' => 'Grandparent'));
    $this->node2 = $this->drupalCreateNode(array('type' => 'article', 'promote' => 0));
    $this->node3 = $this->drupalCreateNode(array('type' => 'page', 'promote' => 1, 'title' => 'Parent'));
    $this->node4 = $this->drupalCreateNode(array('type' => 'page', 'promote' => 0, 'title' => 'Child'));
    $this->node5 = $this->drupalCreateNode(array('type' => 'page', 'promote' => 0));
    $this->node6 = $this->drupalCreateNode(array('type' => 'page', 'promote' => 0, 'title' => 'Unrelated'));
  }

  function createRelationUsers() {
    $this->user1 = $this->drupalCreateUser();
  }

  /**
   * Creates end points.
   */
  function createRelationEndPoints() {
    $this->endpoints = array(
      array('entity_type' => 'node', 'entity_id' => $this->node1->id()),
      array('entity_type' => 'node', 'entity_id' => $this->node4->id()),
    );
    $this->endpoints_4 = array(
      array('entity_type' => 'node', 'entity_id' => $this->node1->id()),
      array('entity_type' => 'node', 'entity_id' => $this->node2->id()),
      array('entity_type' => 'node', 'entity_id' => $this->node3->id()),
      array('entity_type' => 'node', 'entity_id' => $this->node4->id()),
    );
    $this->endpoints_entitysame = array(
      array('entity_type' => 'node', 'entity_id' => $this->node3->id()),
      array('entity_type' => 'node', 'entity_id' => $this->node4->id()),
    );
    $this->endpoints_entitydifferent = array(
      array('entity_type' => 'user', 'entity_id' => $this->user1->id()),
      array('entity_type' => 'node', 'entity_id' => $this->node3->id()),
    );
    $this->endpoints_unary = array(
      array('entity_type' => 'node', 'entity_id' => $this->node5->id()),
    );
  }

  /**
   * Creates a set of standard relation types.
   */
  function createRelationTypes() {
    $this->relation_types['symmetric'] = array(
      'relation_type' => 'symmetric',
      'label' => 'symmetric',
      'source_bundles' => array('node:article', 'node:page', 'taxonomy_term:*', 'user:*'),
    );
    $this->relation_types['directional'] = array(
      'relation_type' => 'directional',
      'label' => 'directional',
      'directional' => TRUE,
      'source_bundles' => array('node:*'),
      'target_bundles' => array('node:page'),
    );
    $this->relation_types['directional_entitysame'] = array(
      'relation_type' => 'directional_entitysame',
      'label' => 'directional_entitysame',
      'directional' => TRUE,
      'source_bundles' => array('node:page'),
      'target_bundles' => array('node:page'),
    );
    $this->relation_types['directional_entitydifferent'] = array(
      'relation_type' => 'directional_entitydifferent',
      'label' => 'directional_entitydifferent',
      'directional' => TRUE,
      'source_bundles' => array('user:*'),
      'target_bundles' => array('node:page'),
    );
    $this->relation_types['octopus'] = array(
      'relation_type' => 'octopus',
      'label' => 'octopus',
      'min_arity' => 3,
      'max_arity' => 5,
      'source_bundles' => array('node:article', 'node:page'),
    );
    $this->relation_types['unary'] = array(
      'relation_type' => 'unary',
      'label' => 'unary',
      'min_arity' => 1,
      'max_arity' => 1,
      'source_bundles' => array('node:page'),
    );
    foreach ($this->relation_types as $values) {
      $relation_type = entity_create('relation_type', $values);
      $relation_type->save();
    }
  }

  /**
   * Creates a Symmetric relation.
   */
  function createRelationSymmetric() {
    // Article 1 <--> Page 4
    $this->relation_type_symmetric = $this->relation_types['symmetric']['relation_type'];
    $this->rid_symmetric = $this->saveRelation($this->relation_type_symmetric, $this->endpoints);
  }

  /**
   * Creates a Directional relation.
   */
  function createRelationDirectional() {
    // Article 1 --> Page 3
    $this->endpoints_directional = $this->endpoints;
    $this->endpoints_directional[1]['entity_id'] = $this->node3->id();
    $this->endpoints_directional[1]['r_index'] = 1;
    $this->relation_type_directional = $this->relation_types['directional']['relation_type'];
    $this->rid_directional = $this->saveRelation($this->relation_type_directional, $this->endpoints_directional);

    // Page 3 --> Page 4
    $this->endpoints_directional2 = $this->endpoints;
    $this->endpoints_directional2[0]['entity_id'] = $this->node3->id();
    $this->endpoints_directional2[1]['entity_id'] = $this->node4->id();
    $this->saveRelation($this->relation_type_directional, $this->endpoints_directional2);

    // Page 3 --> Page 4
    $this->endpoints_entitysame[1]['r_index'] = 1;
    $this->relation_type_directional_entitysame = $this->relation_types['directional_entitysame']['relation_type'];
    $this->saveRelation($this->relation_type_directional_entitysame, $this->endpoints_entitysame);
    // Page 3 --> Page 5
    $this->endpoints_entitysame[1]['entity_id'] = $this->node5->id();
    $this->saveRelation($this->relation_type_directional_entitysame, $this->endpoints_entitysame);
    // Page 4 --> Page 3
    $this->endpoints_entitysame[0]['entity_id'] = $this->node4->id();
    $this->endpoints_entitysame[1]['entity_id'] = $this->node3->id();
    $this->saveRelation($this->relation_type_directional_entitysame, $this->endpoints_entitysame);

    // User 1 --> Page 3
    $this->endpoints_entitydifferent[1]['r_index'] = 1;
    $this->relation_type_directional_entitydifferent = $this->relation_types['directional_entitydifferent']['relation_type'];
    $this->saveRelation($this->relation_type_directional_entitydifferent, $this->endpoints_entitydifferent);
    // User 1 --> Page 4
    $this->endpoints_entitydifferent[1]['entity_id'] = $this->node4->id();
    $this->saveRelation($this->relation_type_directional_entitydifferent, $this->endpoints_entitydifferent);
  }

  /**
   * Creates an Octopus (4-ary) relation.
   */
  function createRelationOctopus() {
    // Nodes 1, 2, 3, 4 are related.
    $this->relation_type_octopus = $this->relation_types['octopus']['relation_type'];
    $this->rid_octopus = $this->saveRelation($this->relation_type_octopus, $this->endpoints_4);
  }

  /**
   * Creates an Unary relation.
   */
  function createRelationUnary() {
    // Page 5 <--> Page 5
    $this->relation_type_unary = $this->relation_types['unary']['relation_type'];
    $this->rid_unary = $this->saveRelation($this->relation_type_unary, $this->endpoints_unary);
  }

  /**
   * Saves a relation.
   */
  function saveRelation($relation_type, $endpoints) {
    $relation = relation_insert($relation_type, $endpoints);
    if ($this->sleep) {
      sleep(1);
    }
    return $relation->id();
  }
}