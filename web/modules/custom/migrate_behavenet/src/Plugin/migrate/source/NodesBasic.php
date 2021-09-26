<?php

namespace Drupal\migrate_behavenet\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\node\Plugin\migrate\source\d6\Node;
use Drupal\Component\Utility\UrlHelper;
use Drupal\migrate\MigrateSkipRowException;

/**
 * Source plugin for basic term import.
 *
 * @MigrateSource(
 *   id = "behavenet_nodes_basic"
 * )
 */
class NodesBasic extends Node {

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = parent::fields();
    $fields += [
      'all_related_terms' => $this->t('Collects all terms related to this node for importing into a single field.'),
      'publication_year' => $this->t('Publication year of the book.'),
      'incoming_related_content' => $this->t('Nodes that point related content to this node. Used to maintain two-way entity references.'),
    ];
    return $fields;
  }

  /**
   * Gets CCK field values for a node, removing backref module fields.
   *
   * @see \Drupal\node\Plugin\migrate\source\d6\Node::getFieldValues
   *
   * @param \Drupal\migrate\Row $node
   *   The node.
   *
   * @return array
   *   CCK field values, keyed by field name.
   */
  protected function getFieldValues(Row $node) {
    $values = [];
    foreach ($this->getFieldInfo($node->getSourceProperty('type')) as $field => $info) {
      if (strpos($field, 'backref') === FALSE) {
        $values[$field] = $this->getCckData($info, $node);
      }
    }
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    if (parent::prepareRow($row) === FALSE) {
      return FALSE;
    }

    // We managed to get some null node titles in the D6 site. Clean that up.
    if ($row->hasSourceProperty('title') && empty($row->getSourceProperty('title'))) {
      $row->setSourceProperty('title', 'Missing title in original dataset');
    }

    // This particular Link field was the only one that allowed link text to be
    // entered. Preserve the URI and title.
    // if ($row->hasSourceProperty('field_directory_url')) {
    //  $link = $row->getSourceProperty('field_directory_url');
    //  $link = $link[0];
    //  if (isset($link['uri'])) {
    //    $row->setSourceProperty('field_directory_url', [
    //      'uri' => $link['url'],
    //      'title' => $link['title'],
    //    ]);
    //  }
    // }
    //
    // Clean basic (single entry) CCK fields that for some reason aren't working
    // with the CckFieldBase processor. Also clean up some fields that were
    // never expected to work with CckFieldBase.
    $fields = [
      'field_outside_video',
      'field_company_url',
      'field_generic_medline_url',
      'field_device_url',
      'field_device_manual_url',
      'field_drug_pi_url',
      'field_drug_url',
    ];
    foreach ($fields as $field) {
      if ($row->hasSourceProperty($field)) {
        $value = $row->getSourceProperty($field);
        $value = $value[0]['value'];
        if (!empty($value)) {

          // Handle special cases on a per-field basis.
          switch ($field) {
            case 'field_outside_video':
              $value = 'https://youtu.be/' . $value;
              break;

            case 'field_company_url':
            case 'field_generic_medline_url':
            case 'field_drug_pi_url':
            case 'field_device_url':
            case 'field_device_manual_url':
            case 'field_directory_url':
            case 'field_drug_url':

              // Clean up poorly formed URLs.
              if (strpos($value, 'http://') === FALSE && strpos($value, 'https://') === FALSE) {
                // Asssume they forgot the protocol and assume it's non-SSL.
                $value = 'http://' . $value;
                if (!UrlHelper::isValid($value, TRUE)) {
                  throw new MigrateSkipRowException(sprintf('Invalid URL is source for nid %s: %s', $row->getSourceProperty('nid'), $value));
                }
              }
              break;
          }

          $row->setSourceProperty($field, $value);
        }
        else {
          $row->setSourceProperty($field, NULL);
        }
      }
    }

    // Collect outbound related content references.
    $related = [];
    if ($row->hasSourceProperty('field_general_related_content')) {
      foreach ($row->getSourceProperty('field_general_related_content') as $item) {
        $related[] = $item['nid'];
      }
    }

    // All field_general_related_content entries that references this node.
    $inbound = $this->select('content_field_general_related_content', 'r')
      ->fields('r', ['nid'])
      ->condition('field_general_related_content_nid', $row->getSourceProperty('nid'))
      ->execute()
      ->fetchCol();
    $related = array_merge($related, array_filter($inbound));

    // Fill in Backreference fields for Source content and add it to the
    // incoming_related_content destination field.
    if ($row->getSourceProperty('type') == 'sources') {
      // Sources field in drug combinations.
      $backref = $this->select('content_type_combinations', 'c')
        ->fields('c', ['nid'])
        ->condition('field_combo_source_nid', $row->getSourceProperty('nid'))
        ->execute()
        ->fetchCol();
      $related = array_merge($related, array_filter($backref));

      // Sources field in generic drugs and preparations.
      $backref = $this->select('content_field_generic_sources', 'c')
        ->fields('c', ['nid'])
        ->condition('field_generic_sources_nid', $row->getSourceProperty('nid'))
        ->execute()
        ->fetchCol();
      $related = array_merge($related, array_filter($backref));
    }

    if ($row->getSourceProperty('type') == 'combinations') {
      // Sources listed in the "is a combo of" field need to get related to this
      // combination. Other options for this field have an associated
      // Combinations field which CER keeps sync'ed up. Sources do not so we add
      // it to field_related_content via incoming_related_content.
      $query = $this->select('content_field_combo_drugs', 'c');
      $query->join('node', 'n', 'n.nid = c.field_combo_drugs_nid');
      $sources = $query->fields('n', ['nid'])
        ->condition('c.nid', $row->getSourceProperty('nid'))
        ->condition('n.type', 'sources')
        ->execute()
        ->fetchCol();
      $sources = array_unique(array_filter($sources));
      $related = array_merge($related, array_filter($sources));
    }

    // Add chemical class from the movie content type to related terms.
    // if ($row->getSourceProperty('type') == 'movie') {
    //  $backref = $this->select('content_field_chemical_class', 'c')
    //    ->fields('c', ['field_chemical_class_value'])
    //    ->condition('nid', $row->getSourceProperty('nid'))
    //    ->execute()
    //    ->fetchCol();
    //  $related = array_merge($related, array_filter($backref));
    // }
    if (!empty($related)) {
      $related = array_values(array_unique($related));
      $row->setSourceProperty('incoming_related_content', $related);
      // Print "\n    has " . count($related) . ' incoming references';.
    }

    // All terms that reference this node.
    $tids = $this->select('term_node', 'tn')
      ->fields('tn', ['tid'])
      ->condition('nid', $row->getSourceProperty('nid'))
      ->condition('vid', $row->getSourceProperty('vid'))
      ->execute()
      ->fetchCol();
    $terms = array_filter($tids);
    $row->setSourceProperty('all_related_terms', $terms);

    // Convert date formats: D6 allowed us to store only the year portion (eg:
    // 1996-00-00T00:00:00) but D8 does not accept that value. So we strip
    // everything but the year and use a plain text field in the D8 version.
    foreach (['field_book_pubdate', 'field_movie_release'] as $field) {
      if ($row->hasSourceProperty($field)) {
        $dates = [];
        foreach ($row->getSourceProperty($field) as $date) {
          $date = $date['value'];
          if (empty($date)) {
            continue;
          }
          else {
            $year = substr($date, 0, 4);
            if (!is_numeric($year)) {
              throw new MigrateSkipRowException(sprintf("Illegal date: %s in nid: %d", $date, $row->getSourceProperty('nid')));
            }

            else {
              $dates[] = $year;
            }
          }
        }
        if (!empty($dates)) {
          $row->setSourceProperty($field, $dates);
        }
        else {
          $row->setSourceProperty($field, NULL);
        }
      }
    }

    // Convert date fields to YYYY-MM-DD.
    foreach (['field_indication_approval', 'field_people_dob', 'field_people_dod'] as $field) {
      if ($row->hasSourceProperty($field)) {
        $date = $row->getSourceProperty($field);
        $date = date('Y-m-d', strtotime($date[0]['value']));
        if (!empty($date)) {
          $row->setSourceProperty($field, $date);
        }
        else {
          $row->setSourceProperty($field, NULL);
        }
      }
    }

    // Destination plugin expects the ASIN to be in the value field, not the
    // asin field.
    foreach (['field_general_additional_asin', 'field_general_asin'] as $field) {
      if ($row->hasSourceProperty($field)) {
        $amazon = $row->getSourceProperty($field);
        foreach ($amazon as $index => $item) {
          $amazon[$index]['value'] = $item['asin'];
        }
        $row->setSourceProperty($field, $amazon);
      }
    }

    // Massage node reference fields to just be a collection of NIDs and don't
    // bother using the migration process plugin since we're forcing NIDs to
    // remain the same between D6 and D8.
    $fields = [
      // 'field_general_related_content',    // No longer needed since we handle
      // this above... See incoming_related_content.
      'field_indication_drug',
      'field_device_indication',
      'field_generic_precursor',
      'field_generic_active_metabolite',
      'field_tt_extras_credit',
      'field_generic_sources',
      'field_compound',
      'field_drug_combo',
      'field_combo_drugs',
      'field_combo_tradename',
      'field_combo_source',
      'field_drug_company',
      'field_drug_generic',
      'field_drug_indications',
      'field_compound_drug',
    ];
    foreach ($fields as $field) {
      if ($row->hasSourceProperty($field)) {
        $related = [];
        foreach ($row->getSourceProperty($field) as $item) {
          $related[] = $item['nid'];
        }
        if (!empty($related)) {
          $row->setSourceProperty($field, $related);
        }
        else {
          $row->setSourceProperty($field, NULL);
        }
      }
    }

    // Massage term reference fields to just be a collection of TIDs and don't
    // bother using the migration process plugin since we're forcing TIDs to
    // remain the same between D6 and D8. Note this is only for fields in D6
    // that had specific term references such as chemical class or isomer. All
    // other term relations will fall into the all_related_terms field and are
    // dumped into the field_related_terms field in D8.
    $fields = [
      'field_chemical_class',
      'field_isomer',
      'field_clinical_class',
      'field_effect_class',
      'field_actions',
      'field_location',
    ];
    $found = [];
    foreach ($fields as $field) {
      if ($row->hasSourceProperty($field)) {
        if ($row->getSourceProperty('type') == 'movie' && $field == 'field_chemical_class') {
          // Removing the chemical class field from the movie content type and
          // combining it with the general related terms field. So let this term
          // reference through.
          continue;
        }
        $related = [];
        foreach ($row->getSourceProperty($field) as $item) {
          $related[] = $item['value'];
        }
        if (!empty($related)) {
          $row->setSourceProperty($field, $related);
          $found = array_merge($found, $related);
        }
        else {
          $row->setSourceProperty($field, NULL);
        }
      }
    }

    // Remove any field specific terms from the all terms field so we don't
    // duplicate.
    if (!empty($found)) {
      $all_terms = $row->getSourceProperty('all_related_terms');
      foreach ($found as $tid) {
        if (($index = array_search($tid, $all_terms)) !== FALSE) {
          unset($all_terms[$index]);
        }
      }
      $all_terms = array_values($all_terms);
      if (!empty($all_terms)) {
        $row->setSourceProperty('all_related_terms', $all_terms);
      }
      else {
        $row->setSourceProperty('all_related_terms', NULL);
      }
    }

    // File fields just want a list of fids to associate.
    foreach (['field_generic_graphic', 'field_people_image', 'field_drug_image', 'field_general_upload'] as $field) {
      if ($row->hasSourceProperty($field)) {
        $fids = [];
        foreach ($row->getSourceProperty($field) as $item) {
          $fids[] = $item['fid'];
        }
        if (!empty($fids)) {
          $row->setSourceProperty($field, $fids);
          print "\n   has files.";
        }
        else {
          $row->setSourceProperty($field, NULL);
        }
      }
    }

    // Print "\n";.
    print "\nImported nid: " . $row->getSourceProperty('nid');
    // Print "\n";.
    return TRUE;
  }

}
