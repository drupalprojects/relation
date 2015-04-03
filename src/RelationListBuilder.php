<?php

/**
 * @file
 * Contains \Drupal\relation\RelationListBuilder.
 */

namespace Drupal\relation;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides a listing of relation types.
 *
 * @todo: add filters
 */
class RelationListBuilder extends EntityListBuilder {

  /**
   * The url generator service.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = t('Title');
    $header['relation_type'] = t('Type');
    $header['endpoints'] = t('Endpoints');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label']['data'] = array(
      '#type' => 'link',
      '#title' => $this->getLabel($entity),
    ) + $entity->urlInfo()->toRenderArray();

    $bundle = entity_load($entity->getEntityType()->getBundleEntityType(), $entity->bundle());
    $row['relation_type']['data'] = array(
      '#type' => 'link',
      '#title' => $this->getLabel($bundle),
    ) + $bundle->urlInfo()->toRenderArray();

    // Sort entities by their type.
    foreach ($entity->endpoints as $endpoint) {
      $entities[$endpoint->entity_type][] = $endpoint->entity_id;
    }

    $relation_entities = array();
    $entity_count_total = 0;
    $entity_count = 0;
    foreach ($entity->endpoints() as $type => $ids) {
      $entity_count_total += count($ids);
      $entities = entity_load_multiple($type, $ids);
      foreach ($ids as $id) {
        $entity_count++;
        $relation_entities[] = array(
          '#type' => 'link',
          '#title' => $this->getLabel($entities[$id]),
        ) + $entities[$id]->urlInfo()->toRenderArray();
      }
    }

    if ($entity_count_total != $entity_count) {
      $relation_entities[] = \Drupal::translation()->formatPlural(
        $entity_count_total - $entity_count,
        'Missing @count entity',
        'Missing @count entities'
      );
    }

    $row['endpoints']['data']['list'] = array(
      '#theme' => 'item_list',
      '#items' => $relation_entities,
    );

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $build['#empty'] = t('No relations exist.');
    return $build;
  }

}
