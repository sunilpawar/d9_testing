<?php

namespace Drupal\migrate_behavenet\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\node\Plugin\migrate\source\d6\Node;

/**
 * Source plugin for pulling DSM criteria data out of multifield in D6 for
 * import into Field Collections in D8.
 *
 * @MigrateSource(
 *   id = "behavenet_dsm_fc"
 * )
 */
class DsmNodesFC extends Node {

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = parent::fields();
    $fields += [
      'host_entity_id' => $this->t('NID of the node these fields are attached to.'),
      'field_dsm_body' => $this->t('The field body value'),
      'field_dsm_version' => $this->t('The DSM term reference'),
      'delta' => $this->t('The delta of this body and version in the source node'),
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // query() should return a simple query that will accurately report the
    // number of rows in the source. We'll add.
    $query = $this->select('content_field_dsm_version', 'v');
    $query->innerJoin('node', 'n', 'n.vid = v.vid');
    $query->fields('v', ['nid', 'vid', 'delta', 'field_dsm_version_value'])
      ->condition('n.type', 'dsm')
      ->condition('n.status', 1)
      ->orderBy('v.delta', 'ASC');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'nid' => [
        'type' => 'integer',
        'alias' => 'n',
      ],
      'delta' => [
        'type' => 'integer',
        'alias' => 'v',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    if (parent::prepareRow($row) === FALSE) {
      return FALSE;
    }

    // The "host" node that this field collection will be attached to.
    $row->setDestinationProperty('host_entity_id', $row->getSourceProperty('nid'));

    // Add the body field for this NID and delta.
    $query = $this->select('content_field_dsm_body', 'body');
    $query
      ->fields('body', ['field_dsm_body_value'])
      ->condition('body.nid', $row->getSourceProperty('nid'))
      ->condition('body.vid', $row->getSourceProperty('vid'))
      ->condition('body.delta', $row->getSourceProperty('delta'));
    $body = $query->execute()->fetchCol();
    if (!empty($body)) {
      $row->setSourceProperty('field_dsm_body', $body[0]);
    }

    // Clean up field name.
    $row->setSourceProperty('field_dsm_version', $row->getSourceProperty('field_dsm_version_value'));

    return TRUE;
  }

}
