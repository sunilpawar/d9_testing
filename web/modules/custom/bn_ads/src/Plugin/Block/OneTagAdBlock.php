<?php

namespace Drupal\bn_ads\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a OneTag ad block.
 *
 * @Block(
 *  id = "onetag_ad_block",
 *  admin_label = @Translation("OneTag ad block"),
 * )
 */
class OneTagAdBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      'onetag_ad_block' => [
        '#markup' => '<script src="//get.s-onetag.com/84024822-360d-424d-9b9f-2bfaa41afd8a/tag.min.js" async defer></script>',
        '#allowed_tags' => ['div', 'script'],
      ]
    ];
  }

}
