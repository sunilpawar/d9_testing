<?php

namespace Drupal\bn_general\Plugin\views\area;

use Drupal\taxonomy\Entity\Term;
use Drupal\views\Plugin\views\area\AreaPluginBase;
use Drupal\Core\Url;

/**
 * Provides Terms results for content-based search pages.
 *
 * @ingroup views_area_handlers
 *
 * @ViewsArea("term_search")
 */
class TermSearch extends AreaPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render($empty = FALSE) {
    if (empty($this->view->exposed_data['keys'])) {
      return;
    }

    // Collect Terms that match the keys entered.
    $keys = $this->view->exposed_data['keys'];
    /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
    $query = \Drupal::entityQuery('taxonomy_term');
    $tids = $query
      ->condition('name', $keys, 'LIKE')
      ->range(0, 20)
      ->execute();
    $terms = Term::loadMultiple($tids);

    /** @var \Drupal\taxonomy\Entity\Term $term */
    foreach ($terms as $term) {
      $output[] = [
        '#type' => 'link',
        '#url' => Url::fromRoute('entity.taxonomy_term.canonical', ['taxonomy_term' => $term->id()]),
        '#title' => $term->getName(),
      ];
    }
    if (!empty($output)) {
      return [
        '#theme' => 'term_search',
        '#items' => $output,
      ];
    }
  }

}
