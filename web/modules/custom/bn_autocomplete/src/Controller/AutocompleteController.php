<?php

namespace Drupal\bn_autocomplete\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class AutocompleteController.
 *
 * @package Drupal\bn_autocomplete\Controller
 */
class AutocompleteController extends ControllerBase {

  /**
   * Handles Behavenet search autocomplete requests..
   *
   * @param string $input
   *   String to build autocomplete results on.
   *
   * @return string
   *   JSON encoded response.
   */
  public function handleAutocomplete() {
    $querystring = \Drupal::request()->getQueryString();
    if (empty($querystring)) {
      return;
    }
    parse_str($querystring, $query);
    $input = $query['q'];
    $results = [];

    // First, those with the search term as a slang term. Note there are two
    // slang term fields so we use an OR entity query instead.
    $query = \Drupal::entityQuery('node', 'OR');
    $nids = $query
      ->condition('field_slang', "%$input%", 'LIKE')
      //->condition('field_slang_terms', "%$input%", 'LIKE')
      ->sort('title', 'ASC')
      ->range(0, 10)
      ->execute();
    $nodes = Node::loadMultiple($nids);
    /** @var \Drupal\node\Entity\Node $node*/
    foreach ($nodes as $node) {
      $terms = $node->hasField('field_slang') ? $node->get('field_slang')->getValue() : []; //$node->get('field_slang_terms')->getValue();
      $slangs = [];
      foreach ($terms as $slang_term) {
        if (strpos(strtolower($slang_term['value']), strtolower($input)) !== FALSE) {
          $slangs[] = $slang_term['value'];
        }
      }
      $results[] = [
        'fields' => ['title' => $node->getTitle() . $this->t('(slang: @slang)', ['@slang' => implode(', ', $slangs)])],
        'value' => $node->getTitle(),
      ];
    }

    // Next gather terms that start with the input string.
    $query = \Drupal::entityQuery('taxonomy_term');
    $tids = $query
      ->condition('name', $input, 'STARTS_WITH')
      ->condition('vid', ['locations'], 'NOT IN')
      ->sort('name', 'ASC')
      ->range(0, 10)
      ->execute();
    $terms = Term::loadMultiple($tids);
    /** @var \Drupal\taxonomy\Entity\Term $term */
    foreach ($terms as $term) {
      $results[] = [
        'fields' => ['title' => $term->getName()],
        'value' => $term->getName(),
      ];
    }

    // Next terms that use the entered text as a synonym.
    $query = \Drupal::entityQuery('taxonomy_term');
    $tids = $query
      ->condition('field_term_synonyms', $input, 'STARTS_WITH')
      ->sort('name', 'ASC')
      ->range(0, 10)
      ->execute();
    $terms = Term::loadMultiple($tids);
    /** @var \Drupal\taxonomy\Entity\Term $term */
    foreach ($terms as $term) {
      $synonyms = [];
      foreach ($term->field_term_synonyms->getValue() as $synonym) {
        $synonyms[] = $synonym['value'];
      }
      $results[] = [
        'fields' => ['title' => $term->getName() . ' (synonyms: ' . implode(', ', $synonyms) . ')'],
        'value' => $term->getName(),
      ];
    }

    // Now query nodes that contain the input string, order by the location the
    // target string appears in the title.
    $query = \Drupal::database()->select('node_field_data', 'n');
    $query->addField('n', 'nid');
    $query->condition('title', "%$input%", 'LIKE');
    $query->range(0, 10);
    $query->addExpression("LOCATE('$input', title)", 'pos');
    $query->orderBy('pos');
    $nids = $query->execute()->fetchCol();
    $nodes = Node::loadMultiple($nids);
    /** @var \Drupal\node\Entity\Node $node*/
    foreach ($nodes as $node) {
      $results[] = [
        'fields' => ['title' => $node->getTitle()],
        'value' => $node->getTitle(),
      ];
    }

    return new JsonResponse($results);
  }

}
