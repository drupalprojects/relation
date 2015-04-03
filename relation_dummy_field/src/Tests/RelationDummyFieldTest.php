<?php

/**
 * @file
 * Definition of Drupal\relation_dummy_field\Tests\RelationDummyFieldTest.
 */

namespace Drupal\relation_dummy_field\Tests;

use Drupal\relation\Tests\RelationTestBase;

/**
 * Functional test of Relation's integration with the Dummy Field.
 */
class RelationDummyFieldTest extends RelationTestBase {

  public static $modules = array('relation_dummy_field');

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Relation Dummy Field test',
      'description' => 'Tests the Relation Dummy Field.',
      'group' => 'Relation',
    );
  }

  /**
   * {@inheritdoc}
   */
  function setUp() {
    parent::setUp();
  }

  /**
   * Create a relation field on the Article node type, and check if it displays
   * the relations correctly on the node page.
   *
   * @TODO
   */
  function todoTestDummyFieldDisplayed() {
    $this->drupalGet('node/' . $this->node1->nid);
    $this->assertNoRaw($this->node4->title, 'Node 4 title is not found');
    $this->field_name = drupal_strtolower($this->randomMachineName()) . '_field_name';
    $field = array(
      'field_name' => $this->field_name,
      'type' => 'relation',
      'entity_types' => array('node'),
    );
    field_create_field($field);
    $instance = array(
      'field_name' => $this->field_name,
      'entity_type' => 'node',
      'bundle' => 'article',
      'label' => $this->randomString(),
      'widget_type' => 'relation_default',
      'display' => array(
        'default' => array(
          'label' => 'hidden',
          'type' => 'relation_default',
        ),
        'teaser' => array(
          'label' => 'hidden',
          'type' => 'hidden',
        ),
      ),
    );
    field_create_instance($instance);
    $this->drupalGet('node/' . $this->node1->nid);
    // As we have asserted Node 4 title not being on the page the only way this
    // click can succeed if the field formatter put it there.
    $this->clickLink($this->node4->title);
  }

}
