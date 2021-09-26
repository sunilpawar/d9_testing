<?php

namespace Drupal\bn_general;

use Drupal\Core\Render\Element;

/**
 * Class for Twig Extension.
 */
class BehavenetTwigExtensions extends \Twig_Extension {

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'beheavenet_twig_extensions';
  }

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    return [
      new \Twig_SimpleFunction(
        'sort_terms',
        [$this, 'sortTerms'],
        ['is_safe' => ['html']]
      ),
      new \Twig_SimpleFunction(
        'process_related_content',
        [$this, 'processRelatedContent'],
        ['is_safe' => ['html']]
      ),
      new \Twig_SimpleFunction(
        'element_children',
        [$this, 'elementChildren'],
        ['is_safe' => ['html']]
      ),
    ];
  }

  /**
   * Sorts a terms render array alphabetically.
   *
   * @param array $terms
   *   Array of terms (as render arrays) to sort.
   *
   * @return array
   *   The sorted render arrays.
   */
  public function sortTerms(array $terms) {
    $isRenderArray = FALSE;
    if (count(array_keys($terms)) != count(Element::children($terms))) {
      // We were passed a full render array, but we only care about sorting the
      // renderable children.
      $temp = [];
      foreach (Element::children($terms) as $child) {
        $temp[] = $terms[$child];
      }
      $original = $terms;
      $terms = $temp;
      $isRenderArray = TRUE;
    }

    // Collect all terms keyed by title.
    $sorted = [];
    foreach ($terms as $term) {
      $title = $isRenderArray ? $term['#title'] : $term['content']['#title'];
      if (!isset($sorted[$title])) {
        $sorted[$title] = $term;
      }
      else {
        // Avoid collisions if there are two different terms with the same title
        // while still maintaining sort.
        $dedup = 0;
        while (isset($sorted[$title . $dedup])) {
          $dedup++;
        }
        $sorted[$title . $dedup] = $term;
      }
    }
    ksort($sorted, SORT_STRING | SORT_FLAG_CASE);

    // Strip array key that were used for sorting.
    $sorted = array_values($sorted);

    if ($isRenderArray) {
      // Repack the items in the render array so it displays correctly.
      $index = 0;
      foreach ($sorted as $item) {
        $original[$index] = $item;
        $index++;
      }
      return $original;
    }
    else {
      return $sorted;
    }
  }

  /**
   * Processes a collection of related content so it's arranged by type and,
   *
   * Optionally, sorted by title.
   *
   * @param array $related_content
   *   An array of related content links.
   * @param string $parent_bundle
   *   The id of the parent entity bundle.
   * @param bool $sort
   *   Indicates whether to sort the result by title. Defaults to TRUE.
   *
   * @return array
   */
  public function processRelatedContent(array $related_content, $parent_bundle, $sort = TRUE) {
    // An array in the form of
    //  'movie' => [array, of, movie, renderables],
    //  'book' => [array, of, book, renderables],
    //  ...
    $results = [];
    foreach ($related_content as $item) {
      /** @var \Drupal\node\Entity\Node $node */
      $node = $item['content']['#options']['entity'];
      if (!isset($results[$node->bundle()])) {
        $results[$node->bundle()] = [];
      }
      $results[$node->bundle()][] = $item;
    }

    if ($sort) {
      foreach ($results as $bundle => &$items) {
        // We can use the (poorly named) sortTerms to sort content as well.
        $items = $this->sortTerms($items);
      }
    }

    // If the parent bundle is Generic Drugs, move Trade Name Drugs to the top
    // of the list.
    if ($parent_bundle == 'generic_drugs' && isset($results['drugs'])) {
      $results = ['drugs' => $results['drugs']] + $results;
    }
    $key_order = [
      'compound',
      'preparation',
      'combination',
      'movies',
      'people',
      'book',
    ];
    $results_orderd = array_replace(array_flip($key_order), $results);
    $results = $results_orderd;
    return $results;
  }

  /**
   * Wrapper for Element::children()
   *
   * @param array $eleemnt
   *   Element to process.
   *
   * @return array
   *   List of keys in $element that represent children of that element.
   */
  public function elementChildren($element) {
    if (is_array($element)) {
      return Element::children($element);
    }
    else {
      return [];
    }
  }

}
