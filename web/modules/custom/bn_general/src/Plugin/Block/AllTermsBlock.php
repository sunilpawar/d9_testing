<?php

namespace Drupal\bn_general\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;

/**
 * Provides a 'AllTermsBlock' block.
 *
 * @Block(
 *  id = "all_terms_block",
 *  admin_label = @Translation("All terms block"),
 * )
 */
class AllTermsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Construct.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(
        array $configuration,
        $plugin_id,
        $plugin_definition
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Use our own cache, rather than that provided by the Render API since we
    // have to invalidate the cache on certain changes to the behavenet_terms
    // vocab. Unfortunately, the vocabulary cache context only works for changes
    // to the vocabulary config, not terms within it.
    $cid = 'bn_general:AllTermsBlock';
    $items = [];
    if ($cache = \Drupal::cache()->get($cid)) {
      $items = $cache->data;
    }
    else {
      // Using Drupal::entityQuery and Term::loadMultiple( took between 8 - 10
      // times longer to process than \Drupal::database()->select()...
      $results = \Drupal::database()->select('taxonomy_term_field_data', 't')
        ->fields('t', ['tid', 'name'])
        ->condition('t.vid', 'behavenet_terms')
        ->orderBy('t.name')
        ->execute();

      foreach ($results as $result) {
        $items[] = [
          'url' => Url::fromRoute('entity.taxonomy_term.canonical', ['taxonomy_term' => $result->tid])->toString(),
          'text' => $result->name,
        ];
      }

      \Drupal::cache()->set($cid, $items);
    }

    $build = [];
    $build['items'] = $items;
    return $build;
  }

}
