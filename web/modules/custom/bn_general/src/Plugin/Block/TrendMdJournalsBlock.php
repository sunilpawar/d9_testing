<?php

namespace Drupal\bn_general\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'TrendMdJournals' block.
 *
 * @Block(
 *  id = "trendmd_journals_block",
 *  admin_label = @Translation("Trend MD Journals block"),
 * )
 */
class TrendMdJournalsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#markup' => '<div id="trendmd-suggestions"></div>',
      '#attached' => array(
        'library' => array('bn_general/trendmd_journals'),
      ),
    ];
  }

}
