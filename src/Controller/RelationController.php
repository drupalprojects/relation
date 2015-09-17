<?php

/**
 * @file
 * Contains \Drupal\relation\Controller\RelationController.
 */

namespace Drupal\relation\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\relation\RelationInterface;
use Drupal\relation\RelationTypeInterface;
use Drupal\Core\Url;

/**
 * Returns responses for Relation routes.
 */
class RelationController extends ControllerBase {
  /**
   * Displays a relation.
   *
   * @param \Drupal\relation\RelationInterface $relation
   *   The relation to display.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function page(RelationInterface $relation) {
    return $this->entityManager()->getViewBuilder('relation')->view($relation);
  }

  /**
   * Displays add relation links for available relation types.
   *
   * Redirects to relation/add/[type] if only one relation type is available.
   *
   * @return array
   *   A render array for a list of the relation types that can be added;
   *   however, if there is only one relation type defined for the site, the
   *   function redirects to the relation add page for that one relation type
   *   and does not return at all.
   *
   * @see \Drupal\node\Controller\NodeController::addPage()
   */
  public function addPage() {
    $relation_types = relation_get_relation_types_options();

    // Bypass the relation/add listing if only one relation type is available.
    if (count($relation_types) == 1) {
      return $this->redirect('relation.add', ['relation_type' => key($relation_types)]);
    }

    foreach ($relation_types as $relation_type => $title) {
      $links[] = array(
        'title' => $title,
        'url' => Url::fromRoute('relation.add', ['relation_type' => $relation_type]),
        'attributes' => array(),
      );
    }

    return array(
      '#theme' => 'links',
      '#links' => $links,
    );
  }

  /**
   * Provides the relation submission form.
   *
   * @param \Drupal\relation\RelationTypeInterface $relation_type
   *   The relation type entity for the relation.
   *
   * @return array
   *   A relation submission form.
   */
  public function add(RelationTypeInterface $relation_type) {
    $relation = $this->entityManager()->getStorage('relation')->create(array(
      'relation_type' => $relation_type->relation_type,
    ));

    $form = $this->entityFormBuilder()->getForm($relation);

    return $form;
  }

  /**
   * The _title_callback for the entity.relation.canonical route.
   *
   * @param RelationInterface $relation
   *   A relation entity.
   *
   * @return string
   *   The page title.
   */
  public function pageTitle(RelationInterface $relation) {
    return $relation->label();
  }

}
