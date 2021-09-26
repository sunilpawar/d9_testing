<?php

namespace Drupal\bn_general\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an 'AllContentBlock' block.
 *
 * @Block(
 *  id = "all_content_block",
 *  admin_label = @Translation("All content by type"),
 * )
 */
class AllContentBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $settingsForm = parent::buildConfigurationForm($form, $form_state);
    $config = $this->getConfiguration();

    $allTypes = NodeType::loadMultiple();
    $types = [];
    foreach ($allTypes as $id => $type) {
      $types[$id] = $type->label();
    }
    $types['field_acronyms'] = $this->t('Include acronyms for generic drugs');

    $settingsForm['node_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Show all content from'),
      '#options' => $types,
      '#default_value' => $config['node_types'],
    ];

    $settingsForm['split_by_type'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Split up items by node type'),
      '#description' => $this->t('If selected, items will be passed to the template in the form of <code>["node_type" => [ array, of, items]</code> instead of just an [array, of items]. The <code>split_by_type</code> variable will be TRUE.'),
      '#default_value' => $config['split_by_type'],
    ];

    return $settingsForm;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    foreach (['node_types', 'split_by_type'] as $item) {
      $this->setConfigurationValue($item, $form_state->getValue($item));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // If we use the default cache provided by views, we would get a cache tag
    // for every single entry in this list. The overhead of that was too much
    // and it was too easy to invalidate the cache when changing things that
    // don't display in this block (namely the node title). Instead we have a
    // custom cache which we invalidate as needed in entity_presave.
    $cidBase = 'bn_general:AllContentBlock:';
    $items = [];
    $config = $this->getConfiguration();
    $split_by_type = !empty($config['split_by_type']);
    $types = array_filter($config['node_types']);
    // Display generic drugs first.
    if (isset($types['generic_drugs'])) {
      unset($types['generic_drugs']);
      $types = ['generic_drugs' => 'generic_drugs'] + $types;
    }

    foreach ($types as $type) {
      $cid = $cidBase . $type;
      if ($cache = \Drupal::cache()->get($cid)) {
        if ($split_by_type) {
          $items[$type] = $cache->data;
        }
        else {
          $items = array_merge($items, $cache->data);
        }
      }
      else {
        $new = [];
        if ($type == 'field_acronyms') {
          // Special case the need to display a list of generic drug acronyms.
          $results = \Drupal::database()->select('node__field_generic_acronyms', 'a')
            ->fields('a', ['field_generic_acronyms_value', 'entity_id'])
            ->condition('a.bundle', 'generic_drugs')
            ->orderBy('a.field_generic_acronyms_value')
            ->execute();
          foreach ($results as $result) {
            $new[] = [
              'url' => Url::fromRoute('entity.node.canonical', ['node' => $result->entity_id]),
              'text' => $result->field_generic_acronyms_value,
            ];
          }
        }
        else {
          // These are node types, collect all the content for this type.
          /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
          $query = \Drupal::entityQuery('node');
          $nids = $query
            ->condition('status', 1)
            ->condition('type', $type)
            ->sort('title')
            ->execute();
          $nodes = Node::loadMultiple($nids);

          /** @var \Drupal\node\Entity\Node $node */
          foreach ($nodes as $node) {
            $new[] = [
              'url' => $node->toUrl(),
              'text' => $node->label(),
            ];
          }
        }

        if ($split_by_type) {
          $items[$type] = $new;
        }
        else {
          $items = array_merge($items, $new);
        }

        \Drupal::cache()->set($cid, $new);
      }
    }

    $build = [];
    $build['#split_by_type'] = $split_by_type;
    $build['items'] = $items;

    // @todo invalidate the cache tag whenever node labels are changed.
    return $build;
  }

}
