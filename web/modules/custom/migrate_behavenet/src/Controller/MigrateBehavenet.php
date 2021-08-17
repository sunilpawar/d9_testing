<?php

namespace Drupal\migrate_behavenet\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;

class MigrateBehavenet extends ControllerBase {

  public function FixVideoTerms($offset, $range) {
    $old_db = Database::getConnection('default', 'migrate');
    $current = Database::getConnection();

    $query = $old_db->select('content_field_outside_video', 'v');
    $query->addField('t', 'field_tt_extras_term_value');
    $query->addField('v', 'field_outside_video_embed');
    $query->isNotNull('v.field_outside_video_embed');
    $foo = $query->join('content_type_tax_tweaks_extras', 't', 't.nid = v.nid AND t.vid = v.vid');
    $videos = $query->execute()->fetchAll();
    $output = [];
    foreach (array_slice($videos, $offset, $range) as $result) {
      $term = Term::load($result->field_tt_extras_term_value);
      if (empty($term)) {
        $output[] = $this->t('Unable to load term ID %id', ['%id' => $result->field_tt_extras_term_value]);
        continue;
      }
      if (empty($result->field_outside_video_embed) || strpos($result->field_outside_video_embed, 'youtu.be') === FALSE) {
        $output[] = $this->t('Bogus video embed code for %id: %embed', [
          '%id' => $result->field_tt_extras_term_value,
          '%embed' => $result->field_outside_video_embed
        ]);
        continue;
      }
      if (!$term->hasField('field_outside_media')) {
        $output[] = $this->t('Term id %id does not have an outside media field. (Trying to embed %embed)', [
          '%id' => $result->field_tt_extras_term_value,
          '%embed' => $result->field_outside_video_embed
        ]);
        continue;
      }

      // Update YouTube link to https.
      $result->field_outside_video_embed = str_replace('http://', 'https://', $result->field_outside_video_embed);

      // Check that the term has the video associated with it, if not add it.
      $field = $term->get('field_outside_media')->getValue();
      if (empty($field)) {
        $term->set('field_outside_media', $result->field_outside_video_embed);
        $term->save();
        $output[] = $this->t('Updated term id %id with %embed', [
          '%id' => $term->id(),
          '%embed' => $result->field_outside_video_embed,
        ]);
      }
      else {
        $output[] = $this->t('Term id %id already has a video associated with it (%current), did not try to add a new video (%new)', [
          '%id' => $term->id(),
          '%current' => $field[0]['value'],
          '%new' => $result->field_outside_video_embed,
        ]);
      }
    }

    return [
      '#markup' => '<ul><li>' . join('</li><li>', $output) . '</li></ul>',
    ];
  }

  public function FindSelfReferences() {
    $old_db = Database::getConnection('default', 'migrate');
    $current = Database::getConnection();

    $query = db_select('taxonomy_term__field_related_terms', 'r');
    $query->where('r.field_related_terms_target_id = r.entity_id');
    $query->addField('r', 'entity_id');
    $tids = $query->execute()->fetchCol();
    $terms = Term::loadMultiple($tids);
    $output = [];
    /** @var Term $term */
    foreach ($terms as $term) {
      $url = Url::fromRoute('entity.taxonomy_term.canonical', ['taxonomy_term' => $term->id()]);

      // Check term IDs from the original dataset.
      $related = [];
      $query = $old_db->select('term_relation', 'r');
      $query->addField('r', 'tid1', 'related_id');
      $query->condition('r.tid2', $term->id());
      foreach ($query->execute()->fetchAll() as $item) {
        $related[] = $item->related_id;
      }
      $query = $old_db->select('term_relation', 'r');
      $query->addField('r', 'tid2', 'related_id');
      $query->condition('r.tid1', $term->id());
      foreach ($query->execute()->fetchAll() as $item) {
        $related[] = $item->related_id;
      }

      $output[] = $this->t('(Term ID: @tid) <a href="@url">' . $term->getName() . '</a> (original related: @related)', [
        '@url' => $url->toString(),
        '@related' => implode(', ', $related),
        '@tid' => $term->id(),
      ]);
    }
    return [
      '#type' => 'markup',
      '#markup' => '<ul><li>' . join('</li><li>', $output) . '</li></ul>',
    ];

  }

  public function FixAliases() {
    $old_db = Database::getConnection('default', 'migrate');
    $current = Database::getConnection();

    $output = [];
    for ($i = 1; $i < 54; $i++) {
      $query = $current->select('url_alias', 'url')
        ->fields('url', ['source', 'pid'])
        ->condition('url.alias', '%-' .  $i, 'LIKE');
      $results = $query->execute();

      foreach ($results as $result) {
        $query = $old_db->select('url_alias', 'url')
          ->fields('url', ['dst'])
          ->condition('url.src', substr($result->source, 1), 'LIKE');
        $alias = $query->execute()->fetchField();
        if ($alias) {
          $alias = '/' . $alias;
          $updated = $current->update('url_alias')
            ->condition('pid', $result->pid)
            ->fields(['alias' => $alias])
            ->execute();

          if ($updated) {
            $output[] = "Updated $result->source to $alias";
          }
          else {
            $output[] = "Unable to update anything for $result->source";
          }
        }
        else {
          $output[] = "Unable to find an alias for $result->source.";
        }
      }
    }

    return [
      '#type' => 'markup',
      '#markup' => '<ul><li>' . join('</li><li>', $output) . '</li></ul>',
    ];
  }

  public function FixCredits($offset, $range) {
    // Array in the form of tid => credit_nid.
    $tid_to_credit_nid = [];
    if (($handle = fopen(__DIR__ . '/term_id_to_credit_nid.csv', 'r')) !== FALSE) {
      while ($data = fgetcsv($handle)) {
        $tid_to_credit_nid[$data[0]] = $data[1];
      }
      fclose($handle);
    }

    $tids = array_keys($tid_to_credit_nid);
    $nids = array_values($tid_to_credit_nid);
    $results = [];
    for ($index = $offset; $index < $offset + $range; $index++) {
      if (!isset($tids[$index])) {
        // All done.
        break;
      }
      $term = Term::load($tids[$index]);
      if (!empty($term)) {
        $term->field_credit->setValue($nids[$index]);
        $term->save();
        $results[] = 'Working on ' . $tids[$index] . ' and ' . $nids[$index] . " ($index)";
      }
      else {
        $results[] = "Bogus term id at $index.";
      }
    }

    return [
      '#type' => 'markup',
      '#markup' => '<ul><li>' . join('</li><li>', $results) . '</li></ul>',
    ];
  }

  public function FixUrls() {
    $fields = [
      'field_directory_url',
      'field_combo_medline_url',
      'field_company_url',
      'field_device_manual_url',
      'field_device_url',
      'field_drug_pi_url',
      'field_drug_url',
      'field_medline_url',
      'field_movie_url',
    ];
    $results = [];
    foreach ($fields as $field) {
      $nids = \Drupal::entityQuery('node')
        ->condition($field, 'http%', 'NOT LIKE')
        ->execute();
      if (!empty($nids)) {
        $nodes = Node::loadMultiple($nids);
        foreach ($nodes as $node) {
          $current = $node->$field->getValue();
          $current[0]['uri'] = 'http://' . $current[0]['uri'];
          $node->set($field, $current[0]);
          $node->save();
          $results[] = "Fixed $field in node/" . $node->id();
        }
      }
    }

    return [
      '#type' => 'markup',
      '#markup' => '<p>Fixed the following: <ul><li>' . join('</li><li>', $results)  . '</li></ul>',
    ];
  }

  /**
   * Cleans up Directory import. For whatever reason, the node term relationship
   * wasn't being saved on import. We just load/save the entities and that fixes
   * it.
   */
  public function FixDirectories($offset, $range) {
    $nids = db_select('node', 'n')
      ->fields('n', ['nid'])
      ->condition('type', 'directory')
      ->execute()
      ->fetchCol();
    $nids = array_slice($nids, $offset, $range);
    $nodes = Node::loadMultiple($nids);
    /** @var Node $node */
    foreach ($nodes as $node) {
      $node->save();
    }

    $html = [
      '#type' => 'markup',
      '#markup' => '<p>Fixed up ' . count($nodes) . ' directory entries.</p>',
    ];

    return $html;
  }

  public function FixSource() {
    $sources = db_select('node', 'n')
      ->fields('n', ['nid'])
      ->condition('type', 'source')
      ->execute()
      ->fetchCol();

    $results = [];
    foreach ($sources as $nid) {
      $entity = Node::load($nid);
      $related = $entity->get('field_related_content');
      do {
        $found = [];
        $finished = TRUE;
        foreach ($related->getValue() as $index => $item) {
          $id = $item['target_id'];
          if (!isset($found[$id])) {
            $found[$id] = TRUE;
          }
          else {
            $related->removeItem($index);
            $results[] = sprintf('Removed duplicate reference to %d from %d', $id, $nid);
            // removeItem() rekeys the array of values so we have to requery
            // them otherwise our $index will be off.
            $finished = FALSE;
            break;
          }
        }
      } while (!$finished);

      $entity->save();
    }
    $html = [
      '#type' => 'markup',
      '#markup' => '<ul><li>'
        . implode('</li><li>', $results)
        . '</li></ul>',
    ];

    return $html;
  }

}
