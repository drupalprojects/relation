<?php

/**
 * @file
 * Definition of Drupal\relation\Tests\RelationAPITest.
 */

namespace Drupal\relation\Tests;

use Drupal\Core\Language\Language;

/**
 * Test general API for Relation.
 *
 * Create nodes, add relations and verify that they are related.
 * This test suite also checks all methods available in RelationQuery.
 *
 * @group Relation
 */
class RelationAPITest extends RelationTestBase {

  public static $modules = array('node');

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    // This is necessary for the ->sort('created', 'DESC') test.
    $this->sleep = TRUE;
    parent::setUp();

    // Defines users and permissions.
    $permissions = array(
      // Node.
      'create article content',
      'create page content',
      // Relation.
      'administer relation types',
      'administer relations',
      'access relations',
      'create relations',
      'edit relations',
      'delete relations',
    );
    $this->web_user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->web_user);
  }

  /**
   * Test relation helper functions.
   */
  public function testRelationHelpers() {
    // ## Test relation_relation_exists()
    // Where relation type is set.
    $exists = relation_relation_exists($this->endpoints, $this->relation_type_symmetric);
    $this->verbose(print_r($exists, TRUE));
    $this->assertTrue(isset($exists[$this->rid_symmetric]), 'Relation exists.');

    // Where relation type is not set.
    $exists = relation_relation_exists($this->endpoints_4);
    $this->assertTrue(isset($exists[$this->rid_octopus]), 'Relation exists.');

    // Where endpoints does not exist.
    $endpoints_do_not_exist = $this->endpoints;
    $endpoints_do_not_exist[1]['entity_type'] = $this->randomMachineName();
    $this->assertEqual(array(), relation_relation_exists($endpoints_do_not_exist, $this->relation_type_symmetric), 'Relation with non-existant endpoint not found.');

    // Where there are too many endpoints.
    $endpoints_excessive = $this->endpoints;
    $endpoints_excessive[] = array('entity_type' => $this->randomMachineName(), 'entity_id' => 1000);
    $this->assertEqual(array(), relation_relation_exists($endpoints_do_not_exist, $this->relation_type_symmetric), 'Relation with too many endpoints not found.');

    // Where relation type is invalid.
    $this->assertEqual(array(), relation_relation_exists($this->endpoints, $this->randomMachineName()), 'Relation with invalid relation type not found.');

  }

  /**
   * Tests all available methods in RelationQuery.
   *
   * Creates some nodes, add some relations and checks if they are related.
   */
  public function testRelationQuery() {
    $relations = entity_load_multiple('relation', array_keys(relation_query('node', $this->node1->id())->execute()));

    // Check that symmetric relation is correctly related to node 4.
    $this->assertEqual($relations[$this->rid_symmetric]->endpoints[1]->entity_id, $this->node4->id(), 'Correct entity is related: ' . $relations[$this->rid_symmetric]->endpoints[1]->entity_id . '==' . $this->node4->id());

    // Symmetric relation is Article 1 <--> Page 4
    // @see https://drupal.org/node/1760026
    $endpoints = array(
      array('entity_type' => 'node', 'entity_id' => $this->node4->id()),
      array('entity_type' => 'node', 'entity_id' => $this->node4->id()),
    );
    $exists = relation_relation_exists($endpoints, 'symmetric');
    $this->assertTrue(empty($exists), 'node4 is not related to node4.');

    // Get relations for node 1, should return 3 relations.
    $count = count($relations);
    $this->assertEqual($count, 3);

    // Get number of relations for node 4, should return 6 relations.
    $count = relation_query('node', $this->node4->id())
      ->count()
      ->execute();
    $this->assertEqual($count, 6);

    // Get number of relations for node 5, should return 2 relations.
    $count = relation_query('node', $this->node5->id())
      ->count()
      ->execute();
    $this->assertEqual($count, 2);

    // Get relations between entities 2 and 5 (none).
    $query = relation_query('node', $this->node2->id());
    $count = relation_query_add_related($query, 'node', $this->node5->id())
      ->count()
      ->execute();
    $this->assertFalse($count);

    // Get directed relations for node 3 using index, should return 2 relations.
    // The other node 3 relation has an r_index 0.
    $relations = relation_query('node', $this->node3->id(), 1)
      ->execute();
    $this->assertEqual(count($relations), 3);
    $this->assertTrue(isset($relations[$this->rid_directional]), 'Got the correct directional relation for nid=3.');

    // Get relations between entities 2 and 3 (octopus).
    $query = relation_query('node', $this->node2->id());
    $relations = relation_query_add_related($query, 'node', $this->node3->id())
      ->execute();
    $count = count($relations);
    $this->assertEqual($count, 1);
    // Check that we have the correct relations.
    $this->assertEqual(isset($relations[$this->rid_octopus]), 'Got one correct relation.');

    // Get relations for node 1 (symmetric, directional, octopus), limit to
    // directional and octopus with relation_type().
    $relations = relation_query('node', $this->node1->id())
      ->condition('relation_type', array(
        $this->relation_types['directional']['relation_type'],
        $this->relation_types['octopus']['relation_type'],
      ))
      ->execute();
    $count = count($relations);
    $this->assertEqual($count, 2);
    // Check that we have the correct relations.
    $this->assertTrue(isset($relations[$this->rid_directional]), 'Got one correct relation.');
    $this->assertTrue(isset($relations[$this->rid_octopus]), 'Got a second one.');

    // Get last two relations for node 1.
    $relations = relation_query('node', $this->node1->id())
      ->range(1, 2)
      ->sort('rid', 'ASC')
      ->execute();
    $count = count($relations);
    $this->assertEqual($count, 2);
    // Check that we have the correct relations.
    $this->assertTrue(isset($relations[$this->rid_directional]), 'Got one correct relation.');
    $this->assertTrue(isset($relations[$this->rid_octopus]), 'Got a second one.');

    // Get all relations on node 1 and sort them in reverse created order.
    $relations = relation_query('node', $this->node1->id())
      ->sort('created', 'DESC')
      ->execute();
    $this->assertEqual($relations, array(
      $this->rid_octopus => $this->rid_octopus,
      $this->rid_directional => $this->rid_directional,
      $this->rid_symmetric => $this->rid_symmetric,
    ));

    // Create 10 more symmetric relations and verify that the count works with
    // double digit counts as well.
    for ($i = 0; $i < 10; $i++) {
      $this->createRelationSymmetric();
    }
    $count = relation_query('node', $this->node4->id())
      ->count()
      ->execute();
    $this->assertEqual($count, 16);
  }

  /**
   * Tests relation types.
   */
  public function testRelationTypes() {
    // Symmetric.
    $related = relation_get_related_entity('node', $this->node1->id());
    $this->assertEqual($this->node4->id(), $related->id());

    // Confirm this works once the related entity has been cached.
    $related = relation_get_related_entity('node', $this->node1->id());
    $this->assertEqual($this->node4->id(), $related->id());

    // Directional.
    // From Parent to Grandparent.
    $related = relation_get_related_entity('node', $this->node3->id(), $this->relation_types['directional']['relation_type'], 1);
    $this->assertEqual($this->node1->id(), $related->id());
    // From Parent to Child.
    $related = relation_get_related_entity('node', $this->node3->id(), $this->relation_types['directional']['relation_type'], 0);
    $this->assertEqual($this->node4->id(), $related->id());

    // Delete all relations related to node 4, then confirm that these can
    // no longer be found as related entities.
    $relation_ids = relation_query('node', $this->node4->id())->execute();
    foreach (entity_load_multiple('relation', $relation_ids) as $relation) {
      $relation->delete();
    }
    $this->assertFalse(relation_get_related_entity('node', $this->node4->id()), 'The entity was not loaded after the relation was deleted.');
  }

  /**
   * Tests saving of relations.
   */
  public function testRelationSave() {
    foreach ($this->relation_types as $value) {
      $relation_type = $value['relation_type'];
      $endpoints = $this->endpoints;
      if (isset($value['min_arity'])) {
        $endpoints = $value['min_arity'] == 1 ? $this->endpoints_unary : $this->endpoints_4;
      }
      if ($relation_type == 'directional_entitydifferent') {
        $endpoints = $this->endpoints_entitydifferent;
      }
      $relation = relation_insert($relation_type, $endpoints);
      $this->assertTrue($relation->id(), 'Relation created.');
      $count = count($relation->endpoints);
      $this->assertEqual($count, count($endpoints));
      $this->assertEqual($relation->arity->value, count($endpoints));
      $this->assertEqual($relation->relation_type->value, $relation_type);
      foreach ($relation->endpoints as $endpoint) {
        $need_ids[$endpoint->entity_id] = TRUE;
      }
      foreach ($relation->endpoints as $delta => $endpoint) {
        $this->assertEqual($endpoint->entity_type, $endpoints[$delta]['entity_type'], 'The entity type is ' . $endpoints[$delta]['entity_type'] . ': ' . $endpoint->entity_type);
        $this->assertTrue(isset($need_ids[$endpoint->entity_id]), 'The entity ID is correct: ' . $need_ids[$endpoint->entity_id]);
        unset($need_ids[$endpoint->entity_id]);
      }
      $this->assertFalse($need_ids, 'All ids found.');
      // Confirm the rid in revision table.
      $revision = db_select('relation_revision', 'v')
          ->fields('v', array('rid'))
          ->condition('vid', $relation->vid->value)
          ->execute()
          ->fetchAllAssoc('rid');
      $this->assertTrue(array_key_exists($relation->id(), $revision), 'Relation revision created.');
    }
  }

  /**
   * Tests relation delete.
   */
  public function testRelationDelete() {
    // Invalid relations are deleted when any endpoint entity is deleted.
    // Octopus relation is valid with 3 endpoints, currently it has 4.
    $this->node1->delete();
    $this->assertTrue(entity_load('relation', $this->rid_octopus), 'Relation is not deleted.');
    $this->node2->delete();
    $this->assertFalse(entity_load('relation', $this->rid_octopus), 'Relation is deleted.');
  }

  /**
   * Tests relation revisions.
   *
   * @TODO
   */
  public function todoTestRelationRevision() {
    $first_user = $this->drupalCreateUser(array('edit relations'));
    $second_user = $this->drupalCreateUser(array('edit relations'));

    $this->drupalLogin($first_user);
    $relation = relation_insert($this->relation_type_octopus, $this->endpoints_4);
    $relation->save();
    $rid = $relation->id();
    $this->assertEqual($relation->id(), $first_user->id(), 'Relation uid set to logged in user.');
    $vid = $relation->getRevisionId();

    // Relation should still be owned by the first user.
    $this->drupalLogin($second_user);
    $relation = entity_load('relation', $rid);
    $relation->save();
    $this->assertEqual($relation->id(), $first_user->id(), 'Relation uid did not get changed to a user different to original.');

    // Relation revision authors should not be identical though.
    $first_revision = entity_revision_load('relation', $vid);
    $second_revision = entity_revision_load('relation', $relation->vid);
    $this->assertNotIdentical($first_revision->revision_uid, $second_revision->revision_uid, 'Each revision has a distinct user.');
  }

}
