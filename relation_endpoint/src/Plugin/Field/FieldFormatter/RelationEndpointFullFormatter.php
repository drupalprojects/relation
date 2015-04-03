<?php

/**
 * @file
 * Contains \Drupal\relation_endpoint\Plugin\Field\FieldFormatter\RelationEndpointFullFormatter.
 */

namespace Drupal\relation_endpoint\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'link' formatter.
 *
 * @FieldFormatter(
 *   id = "relation_endpoint_full",
 *   label = @Translation("Render endpoints"),
 *   field_types = {
 *     "relation_endpoint"
 *   },
 *   settings = {
 *   }
 * )
 */
class RelationEndpointFullFormatter extends FormatterBase {
  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $build = array();
    $entities = array();

    foreach ($items as $delta => $item) {
      if ($entity = entity_load($item->entity_type, $item->entity_id)) {
        // @todo: allow view mode customisation.
        $build[$delta] = entity_view($entity, 'teaser');
      }
    }

    return $build;
  }

}
