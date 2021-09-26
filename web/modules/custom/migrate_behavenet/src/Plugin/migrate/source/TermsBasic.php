<?php

namespace Drupal\migrate_behavenet\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\taxonomy\Plugin\migrate\source\d6\Term;

/**
 * Source plugin for basic term import.
 *
 * @MigrateSource(
 *   id = "behavenet_terms_basic"
 * )
 */
class TermsBasic extends Term {

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = parent::fields();
    $fields += [
      'synonyms' => $this->t('Term synonyms.'),
      'related' => $this->t('Related terms.'),
      'is_acronym' => $this->t('Is this term an acronym for another term?'),
      'is_list' => $this->t('Is this term a list term?'),
      'credit' => $this->t('The nid of the credit node.'),
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    parent::prepareRow($row);
    $source = $row->getSource();

    // Handle some bad data in the system: skip terms with empty names.
    if ($source['name'] == '') {
      return FALSE;
    }

    // Load Taxonomy Tweaks for Behavenet Terms.
    if ($row->getSourceProperty('vid') == 5) {
      $tid = $row->getSourceProperty('tid');

      // Collect synonyms. (Base functionality not handled by
      // Drupal\taxonomy\Plugin\migrate\source\Term.)
      $query = $this->select('term_synonym', 'ts')
        ->fields('ts', ['name'])
        ->condition('tid', $tid);
      $synonyms = $query->execute()
        ->fetchCol();
      $synonyms = array_filter($synonyms);
      $row->setSourceProperty('synonyms', $synonyms);

      // Collect related terms. (Base functionality not handled by
      // Drupal\taxonomy\Plugin\migrate\source\Term.)
      $query = $this->select('term_relation', 'tr')
        ->fields('tr', ['tid2'])
        ->condition('tid1', $tid);
      $related = $query->execute()
        ->fetchCol();
      $query = $this->select('term_relation', 'tr')
        ->fields('tr', ['tid1'])
        ->condition('tid2', $tid);
      $more_related = $query->execute()
        ->fetchCol();
      $related = array_filter(array_merge($related, $more_related));

      $row->setSourceProperty('related', $related);

      // Is acronym? (Added by tax_tweaks module in D6.)
      $acronym = $this->select('tax_tweaks', 'tt')
        ->fields('tt', ['is_acronym'])
        ->condition('tid', $tid)
        ->execute()
        ->fetchField();
      $row->setSourceProperty('is_acronym', empty($acronym) ? 0 : 1);

      // List status is stored as a serialized list of tids.
      // @todo store statically?
      $lists = $this->select('variable', 'v')
        ->fields('v', ['value'])
        ->condition('name', 'behave_lists_tids')
        ->execute()
        ->fetchField();
      $lists = unserialize($lists);
      $row->setSourceProperty('is_list', in_array($tid, $lists) ? 1 : 0);

      // Credit field.
      $query = $this->select('content_type_tax_tweaks_extras', 'tt')
        ->condition('tt.field_tt_extras_term_value', $tid)
        ->fields('ttc', ['field_tt_extras_credit_nid']);
      $query->join('content_field_tt_extras_credit', 'ttc', 'ttc.vid = tt.vid');
      $credit = $query->execute()
        ->fetchField();
      if (!empty($credit)) {
        $row->setSourceProperty('credit', $credit);
      }

      // Outside media field.
      $query = $this->select('content_type_tax_tweaks_extras', 'tt')
        ->condition('tt.field_tt_extras_term_value', $tid);
      $query->join('content_field_outside_video', 'v', 'v.vid = tt.vid AND v.nid = tt.nid');
      $value = $query->fields('v', ['field_outside_video_value'])
        ->execute()
        ->fetchField();
      if (!empty($value)) {
        // Update older embeds to the new youtu.be domain.
        $value = 'https://youtu.be/' . $value;
        $row->setSourceProperty('field_outside_video', $value);
      }
    }
    print "\nImporting TID: " . $row->getSourceProperty('tid') . ' from VID: ' . $row->getSourceProperty('vid');
    return TRUE;
  }

}
