<?php

function relation_field_info() {
  return array(
    'relation_endpoint' => array(
      'label' => t('Relation endpoint'),
      'description' => t('This field contains the endpoints of the relation'),
      'default_widget' => 'relation_endpoint',
      'default_formatter' => 'relation_endpoint',
      'entity types' => array('relation'),
    ),
  );
}

function relation_field_validate($entity_type, $entity, $field, $instance, $langcode, $items, &$errors) {
  $relation_type = relation_type_load($entity->predicate);
  // Check that predicate exists.
  if (!$relation_type) {
    $errors[] = t("The !predicate relation type does not exist!", array('!predicate' => $entity->predicate));
  }
  // Check that arity is within acceptable bounds.
  if (count($items) < $relation_type->min_arity && empty($entity->in_progress)) {
    $errors[] = t("Relation has too few end points (:predicate min arity :min_arity)", array(':predicate' => $entity->predicate, ':min_arity' => $relation_type->min_arity));
  }
  if ($relation_type->max_arity && count($items) > $relation_type->max_arity) {
    $errors[] = t("Relation has too many end points (:predicate max arity :max_arity)", array(':predicate' => $entity->predicate, ':max_arity' => $relation_type->max_arity));
  }
  //Check that each entity is has acceptable bundle type and index.
  foreach ($items as $delta => $item) {
    $acceptable = FALSE;
    $directional = $relation_type->directional;
    $endpoint = ($directional && ($delta > 0)) ? 'target' : 'source';
    $end_bundles = $endpoint . '_bundles';
    foreach ($relation_type->$end_bundles as $relation_bundle) {
      if (!isset($item['entity_bundle'])) {
        $entities = entity_load($item['entity_type'], array($item['entity_id']));
        $entity = reset($entities);
        list (, , $item['entity_bundle']) = entity_extract_ids($item['entity_type'], $entity);
      }
      $relation_bundle_array = explode(':', $relation_bundle, 2);
      if (($relation_bundle == $item['entity_type'] . ':' . $item['entity_bundle']) || (($item['entity_type'] == $relation_bundle_array[0]) && ($relation_bundle_array[1] == '*'))) {
        $acceptable = TRUE;
        break;
      }
    }
    if (!$acceptable) {
      $t_arguments = array('%predicate' => $entity->predicate, '@bundle' => $item['entity_bundle']);
      if ($relation_type->directional) {
        if ($endpoint == 'target') {
          $errors[] = t("The %predicate relation type does not allow @bundle entities as target.", $t_arguments);
        }
        else {
          $errors[] = t("The %predicate relation type does not allow @bundle entities as source.", $t_arguments);
        }
      }
      else {
        $errors[] = t("The %predicate relation type does not allow @bundle entities as an endpoint.", $t_arguments);
      }
    }
  }
}

/**
 * Implements hook_field_presave().
 */
function relation_field_presave($entity_type, $entity, $field, $instance, $langcode, &$items) {
  // We need r_index here because EntityFieldQuery can't query on deltas.
  foreach ($items as $delta => &$item) {
    $item['r_index'] = $delta;
  }
}

/**
 * Implements hook_field_update().
 */
function relation_field_update($entity_type, $entity, $field, $instance, $langcode, &$items) {
  // We do not update.
}

/**
 * Implements hook_field_is_empty().
 */
function relation_field_is_empty($item, $field) {
  // We are never empty.
  return FALSE;
}

/**
 * Helper to create an HTML table representing a relation.
 */
function _relation_field_create_html_table($endpoints) {
  $list_items = array();
  foreach ($endpoints as $delta => $endpoint) {
    $entities = entity_load($endpoint['entity_type'], array($endpoint['entity_id']));
    $entity = reset($entities);
    $label = entity_label($endpoint['entity_type'], $entity);
    $uri = entity_uri($endpoint['entity_type'], $entity);
    $list_items[$delta] = array(l($label, $uri['path'], $uri['options']), $endpoint['entity_type']);
  }
  $headers = array('Entity', 'Entity_type');
  return array(
    '#theme' => 'table',
    '#caption' => 'Related Entities',
    '#header' => $headers,
    '#rows' => $list_items,
  );
}

function relation_field_formatter_info() {
  return array(
    'relation_endpoint' => array(

    )
  );
}

function relation_field_widget_info() {
  return array(
    'relation_endpoint' => array(

    )
  );
}

function relation_field_formatter_view($entity_type, $entity, $field, $instance, $langcode, $items, $display) {
  return _relation_field_create_html_table($items);
}

function hook_field_widget_form(&$form, &$form_state, $field, $instance, $langcode, $items, $delta, $element) {
  foreach ($items as $delta => $item) {
    foreach (array('entity_type', 'entity_id') as $column) {
      $element[$delta][$column] = array(
        '#type' => 'value',
        '#value' => $item[$column],
      );
    }
  }
  $element['link_list'] = _relation_field_create_html_table($items);
  return $element;
}