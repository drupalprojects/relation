<?php

namespace Drupal\relation\Tests;

use Drupal\Core\Database\Database;
use Drupal\relation\Entity\Relation;

/**
 * Relation UI.
 *
 * Check that relation administration interface works.
 *
 * @group Relation
 */
class RelationUITest extends RelationTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    // This is necessary for the ->sort('created', 'DESC') test.
    $this->sleep = TRUE;
    parent::setUp();

    // Defines users and permissions.
    $permissions = array(
      // Relation.
      'administer relation types',
      'administer relations',
      // Field UI.
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
  public function testRelationDelete() {
    $relation = Relation::load($this->rid_directional);

    $this->drupalPostForm("relation/" . $relation->id() . "/delete", array(), t('Delete'));
    $arg = [':relation_id' => $relation->id()];
    $this->assertFalse((bool) Database::getConnection('default')->queryRange('SELECT * FROM {relation} WHERE relation_id = :relation_id', 0, 1, $arg)->fetchField(), 'Nothing in relation table after delete.');
    $this->assertFalse((bool) Database::getConnection('default')->queryRange('SELECT * FROM {relation_revision} WHERE relation_id = :relation_id', 0, 1, $arg)->fetchField(), 'Nothing in relation revision table after delete.');
    // @todo: test if field data was deleted.
    // CrudTest::testDeleteField has 'TODO: Also test deletion of the data
    // stored in the field ?'
    // Try deleting the content types.
    $this->drupalGet("admin/structure/relation/manage/$this->relation_type_symmetric/delete");
    $num_relations = 1;

    // See RelationTypeDeleteConfirm buildForm.
    $this->assertRaw(
      t('%type is used by @count relation. You can not remove this relation type until you have removed all %type relations.', ['@count' => $num_relations, '%type' => $this->relation_types['symmetric']['label']]),
      'Correct number of relations found (1) for ' . $this->relation_types['symmetric']['label'] . ' relation type.'
    );
  }

  /**
   * Tests endpoint field settings.
   */
  public function testRelationEndpointsField() {
    /* todo Field is currently locked in relation_add_endpoint_field
    // Relation type listing.
    $this->drupalGet('admin/structure/relation');

    // Change label of relation endpoint field.
    $field_label = $this->randomMachineName();
    $edit = [
      'label' => $field_label,
    ];

    $this->drupalPostForm('admin/structure/relation/manage/symmetric/fields/relation.symmetric.endpoints', $edit, t('Save settings'));
    $this->assertText(t('Saved @label configuration.', array('@label' => $field_label)));

    $this->drupalGet('admin/structure/relation/manage/symmetric/fields');
    $this->assertFieldByXPath('//table[@id="field-overview"]//td[1]', $field_label, t('Endpoints field label appears to be changed in the overview table.'));
    */
  }

}
