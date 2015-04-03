<?php

/**
 * @file
 * Contains \Drupal\relation_endpoint\Plugin\Field\FieldFormatter\RelationEndpointFormatter.
 */

namespace Drupal\relation_endpoint\Plugin\Field\FieldFormatter;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Utility\LinkGenerator;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'link' formatter.
 *
 * @FieldFormatter(
 *   id = "relation_endpoint",
 *   label = @Translation("Endpoints table"),
 *   field_types = {
 *     "relation_endpoint"
 *   },
 *   settings = {
 *   }
 * )
 */
class RelationEndpointFormatter extends FormatterBase {
  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $rows = array();

    $header = array(
      array('data' => t('Entity type')),
      array('data' => t('Entity ID')),
      array('data' => t('Label')),
    );

    foreach ($items as $item) {
      $t = array('@entity_type' => $item->entity_type, '@entity_id' => $item->entity_id);

      try {
        if ($entity = entity_load($item->entity_type, $item->entity_id)) {
          $label = $entity->label();
          $label_cell['data'] = array(
            '#type' => 'link',
            '#title' => (!empty($label) && strlen($label) > 0) ? $label : t('Untitled', $t),
          ) + $entity->urlInfo()->toRenderArray();
        }
        else {
          $label_cell = t('Deleted');
        }
      }
      catch (PluginNotFoundException $e) {
        $label_cell = t($e->getMessage());
      }

      $rows[] = array($item->entity_type, $item->entity_id, $label_cell);
    }

    return array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    );
  }

}
