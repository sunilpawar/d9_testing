<?php

namespace Drupal\bn_ads\Plugin\Block;

use Drupal\Core\Block\BlockBase;

// Use Drupal\Core\Form\FormStateInterface;.

/**
 * Provides an Amazon ad block.
 *
 * @Block(
 *  id = "amazon_ad_block",
 *  admin_label = @Translation("Amazon ad block"),
 * )
 */
class AmazonAdBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      'amazon_ad_block' => [
        '#markup' => '<div class="amazon-ad-block"><iframe src="https://rcm-na.amazon-adsystem.com/e/cm?t=behavenetrinc&o=1&p=12&l=ur1&category=dvd&banner=1SDQVA1DWHAHX2JQR782&f=ifr" width="300" height="250" scrolling="no" border="0" marginwidth="0" style="border:none;" frameborder="0"></iframe></div>',
        '#allowed_tags' => ['div', 'iframe'],
      ],
    ];
  }

}
