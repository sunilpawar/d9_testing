<?php

namespace Drupal\bn_ads\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a Tap Native ad block.
 *
 * @Block(
 *  id = "tap_native_ad_block",
 *  admin_label = @Translation("Tap Native ad block"),
 * )
 */
class TapNativeAdBlock extends BlockBase {

  /**
   * @inheritdoc
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();

    $ad_numbers = [
      '110208' => '110208',
      '110209' => '110209',
    ];

    $form['number'] = [
      '#type' => 'select',
      '#title' => $this->t('Select the ad number'),
      '#default_value' => isset($config['number']) ? $config['number'] : '',
      '#options' => $ad_numbers,
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * @inheritdoc
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->setConfigurationValue('number', $form_state->getValue('number'));
  }
  
  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $build = $this->ad_markup($config['number']);

    return [
      '#theme' => 'bn_ad',
      '#content' => $build,
    ];
  }

  protected function ad_markup($number) {
    return '<div id="adx_native_ad_' . $number . '"></div><script type="text/javascript">(function() {var a="",b=["aid=' . $number . '"];for (var c=0;c<b.length;++c){a+=b[c]+"&";}a+="r="+Math.random()*1e16+"&url="+encodeURIComponent(window.location.href);var x="https://content.tapnative.com/tn/?"+a;var y=document.createElement("script");y.type="text/javascript";y.async=true;y.src=x;var z=document.getElementsByTagName("script")[0];z.parentNode.insertBefore(y, z);})();</script>';
  }

}
