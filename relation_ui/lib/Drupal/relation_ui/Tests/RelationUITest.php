<?php

/**
 * @file
 * Definition of Drupal\relation_ui\Tests\RelationUITest.
 */

namespace Drupal\relation_ui\Tests;

use Drupal\relation\Tests\RelationTestBase;

/**
 * Tests Relation UI.
 *
 * Check that relation administration interface works.
 */
class RelationUITest extends RelationTestBase {

  public static $modules = array('relation_ui', 'field_ui');

  public static function getInfo() {
    return array(
      'name' => 'Relation UI test',
      'description' => 'Tests Relation UI.',
      'group' => 'Relation',
    );
  }

  function setUp() {
    // This is necessary for the ->sort('created', 'DESC') test.
    $this->sleep = TRUE;
    parent::setUp();

    // Defines users and permissions.
    $permissions = array(
      // Relation
      'administer relation types',
      'administer relations',
      // Field UI
      'administer relation fields',
      'administer relation form display',
      'administer relation display',
    );
    $this->web_user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->web_user);
  }

  /**
   * Tests deletion of a relation.
   */
  function testRelationDelete() {
    $relation = entity_load('relation', $this->rid_directional);

    $this->drupalPostForm("relation/" . $relation->id() . "/delete", array(), t('Delete'));
    $arg = array(':rid' => $relation->id());
    $this->assertFalse((bool) db_query_range('SELECT * FROM {relation} WHERE rid = :rid', 0, 1, $arg)->fetchField(), 'Nothing in relation table after delete.');
    $this->assertFalse((bool) db_query_range('SELECT * FROM {relation_revision} WHERE rid = :rid', 0, 1, $arg)->fetchField(), 'Nothing in relation revision table after delete.');

    // @todo: test if field data was deleted.
    // CrudTest::testDeleteField has 'TODO: Also test deletion of the data stored in the field ?'

    // Try deleting the content types.
    $this->drupalGet("admin/structure/relation/manage/$this->relation_type_symmetric/delete");
    $num_relations = 1;

    // See RelationTypeDeleteConfirm buildForm
    $this->assertRaw(format_plural($num_relations, '%type is used by @count relation on your site. You may not remove %type until you have removed the %type relation.', '%type is used by @count relations on your site. You may not remove %type until you have removed all of the %type relations.', array('%type' => $this->relation_types['symmetric']['label'])), 'Correct number of relations found (1) for ' . $this->relation_types['symmetric']['label'] . ' relation type.');
  }

  /**
   * Tests endpoint field settings.
   */
  function testRelationEndpointsField() {
    // Relation type listing
    $this->drupalGet('admin/structure/relation');

    // Change label of relation endpoint field
    $field_label = $this->randomName();
    $edit = array(
      'instance[label]' => $field_label,
    );

    $this->drupalPostForm('admin/structure/relation/manage/symmetric/fields/relation.symmetric.endpoints', $edit, t('Save settings'));
    $this->assertText(t('Saved @label configuration.', array('@label' => $field_label)));

    $this->drupalGet('admin/structure/relation/manage/symmetric/fields');
    $this->assertFieldByXPath('//table[@id="field-overview"]//td[1]', $field_label, t('Endpoints field label appears to be changed in the overview table.'));
  }
}
