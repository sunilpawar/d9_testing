<?php

namespace Drupal\bn_ads\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'AdBlock' block.
 *
 * @Block(
 *  id = "ad_block",
 *  admin_label = @Translation("Ad block"),
 * )
 */
class AdBlock extends BlockBase {

  /**
   * Holds machine_name => human-readable options for ad locations.
   */
  protected $layoutOptions = [];

  /**
   * @inheritdoc
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->layoutOptions = [
      'footer' => $this->t('Footer - 728 x 90'),
      'leader' => $this->t('Header - 728 x 90'),
      'rectangle' => $this->t('Rectangle - 300 x 250'),
      'skyscraper' => $this->t('Skyscraper - 160 x 600'),
      'interstitial' => $this->t('Interstitial'),
      'content_footer' => $this->t('Content Footer - 300 x 250'),
    ];
  }

  /**
   * @inheritdoc
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();

    $form['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Select the size and type of ad'),
      '#description' => $this->t('Some ads of the same size may be used in different places on a page. For example, the Header banner ad will have higher value than the footer banner ad.'),
      '#default_value' => isset($config['type']) ? $config['type'] : '',
      '#options' => $this->layoutOptions,
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * @inheritdoc
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->setConfigurationValue('type', $form_state->getValue('type'));
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $drupalUserID = '0';
    $currentUser = \Drupal::currentUser();
    if ($currentUser->isAuthenticated()) {
      $drupalUserID = $currentUser->id();
    }
    $adBlock = '';
    switch ($config['type']) {
      case 'footer':
        $adBlock = $this->footer_ad($drupalUserID);
        break;

      case 'leader':
        $adBlock = $this->leader_ad($drupalUserID);
        break;

      case 'rectangle':
        $adBlock = $this->rectangle_ad($drupalUserID);
        break;

      case 'skyscraper':
        $adBlock = $this->skyscraper_ad($drupalUserID);
        break;

      case 'interstitial':
        $adBlock = $this->interstitial_ad($drupalUserID);
        break;

      case 'content_footer':
        $adBlock = $this->content_footer_ad($drupalUserID);
        break;

      default:
        $adBlock = $this->t('This ad block has been misconfigured.');
    }

    return [
      'ad_block' => [
        '#theme' => 'bn_ad',
        '#content' => $adBlock,
        '#attached' => [
          'library' => ['bn_ads/dfp']
        ],
        '#cache' => [
          'max-age' => 0,
        ],
      ]
    ];
  }

  protected function leader_ad($drupalUserID) {
    return <<<HERE
      <!-- ASYNC UAT M - 728x90/320x50(t,l) - Physicians (MD)/Psychiatry/ -->
      <div id="behavenet-behavenet-0">
      <script type="text/javascript">
      var ehs_screenwidth=((navigator.userAgent.match(/iPhone/i)) || (navigator.userAgent.match(/iPod/i))) ? screen.width : window.innerWidth;
      if (ehs_screenwidth>=778) {
      (function() {
      var a="",b=[
      "adid=behavenet-behavenet-0",
      "site=ehs.pro.behavenet.behavenet",
      "size=728x90",
      "vpos=t",
      "hpos=l",
      "zone=",
      "iprof=md",
      "ims1=p38",
      "puid=$drupalUserID",
      ];
      for (var c=0;c<b.length;++c){a+=b[c]+"&";}a+="r="+Math.random()*1e16+"&url="+encodeURIComponent(window.location.href);
      var x="https://ads.ehealthcaresolutions.com/a/?"+a;
      var y=document.createElement("script");y.type="text/javascript";y.async=true;y.src=x;
      var z=document.getElementsByTagName("script")[0];
      z.parentNode.insertBefore(y, z);
      })();
      }
      else {
      (function() {
      var a="",b=[
      "adid=behavenet-behavenet-0",
      "site=ehs.pro.behavenet.behavenet",
      "size=320x50",
      "vpos=t",
      "hpos=l",
      "zone=",
      "iprof=md",
      "ims1=p38",
      "puid=$drupalUserID",
      ];
      for (var c=0;c<b.length;++c){a+=b[c]+"&";}a+="r="+Math.random()*1e16+"&url="+encodeURIComponent(window.location.href);
      var x="https://ads.ehealthcaresolutions.com/a/?"+a;
      var y=document.createElement("script");y.type="text/javascript";y.async=true;y.src=x;
      var z=document.getElementsByTagName("script")[0];
      z.parentNode.insertBefore(y, z);
      })();
      }
      </script>
      </div>
      <!-- END ASYNC UAT M - 728x90/320x50(t,l) -->
HERE;
  }

  protected function skyscraper_ad($drupalUserID) {
    return <<<HERE
      <!-- ASYNC UAT M - 160x600(t,l) - Physicians (MD)/Psychiatry/ -->
      <div id="behavenet-behavenet-1">
      <script type="text/javascript">
      var ehs_screenwidth=((navigator.userAgent.match(/iPhone/i)) || (navigator.userAgent.match(/iPod/i))) ? screen.width : window.innerWidth;
      if (ehs_screenwidth>=778) {
      (function() {
      var a="",b=[
      "adid=behavenet-behavenet-1",
      "site=ehs.pro.behavenet.behavenet",
      "size=160x600",
      "vpos=t",
      "hpos=l",
      "zone=",
      "iprof=md",
      "ims1=p38",
      "puid=$drupalUserID",
      ];
      for (var c=0;c<b.length;++c){a+=b[c]+"&";}a+="r="+Math.random()*1e16+"&url="+encodeURIComponent(window.location.href);
      var x="https://ads.ehealthcaresolutions.com/a/?"+a;
      var y=document.createElement("script");y.type="text/javascript";y.async=true;y.src=x;
      var z=document.getElementsByTagName("script")[0];
      z.parentNode.insertBefore(y, z);
      })();
      }
      </script>
      </div>
      <!-- END ASYNC UAT M - 160x600(t,l) -->
HERE;
  }

  protected function rectangle_ad($drupalUserID) {
    return <<<HERE
      <!-- ASYNC UAT M - 300x250(t,r) - Physicians (MD)/Psychiatry/ -->
      <div id="behavenet-behavenet-2">
      <script type="text/javascript">
      (function() {
      var a="",b=[
      "adid=behavenet-behavenet-2",
      "site=ehs.pro.behavenet.behavenet",
      "size=300x250",
      "vpos=t",
      "hpos=r",
      "zone=",
      "iprof=md",
      "ims1=p38",
      "puid=$drupalUserID",
      ];
      for (var c=0;c<b.length;++c){a+=b[c]+"&";}a+="r="+Math.random()*1e16+"&url="+encodeURIComponent(window.location.href);
      var x="https://ads.ehealthcaresolutions.com/a/?"+a;
      var y=document.createElement("script");y.type="text/javascript";y.async=true;y.src=x;
      var z=document.getElementsByTagName("script")[0];
      z.parentNode.insertBefore(y, z);
      })();
      </script>
      </div>
      <!-- END ASYNC UAT M - 300x250(t,r) -->
HERE;
  }

  protected function footer_ad($drupalUserID) {
    return <<<HERE
      <!-- ASYNC UAT M - 728x90/320x50(t,l) - Physicians (MD)/Psychiatry/ -->
      <div id="behavenet-behavenet-3">
      <script type="text/javascript">
      var ehs_screenwidth=((navigator.userAgent.match(/iPhone/i)) || (navigator.userAgent.match(/iPod/i))) ? screen.width : window.innerWidth;
      if (ehs_screenwidth>=778) {
      (function() {
      var a="",b=[
      "adid=behavenet-behavenet-3",
      "site=ehs.pro.behavenet.behavenet",
      "size=728x90",
      "vpos=t",
      "hpos=l",
      "zone=",
      "iprof=md",
      "ims1=p38",
      "puid=$drupalUserID",
      ];
      for (var c=0;c<b.length;++c){a+=b[c]+"&";}a+="r="+Math.random()*1e16+"&url="+encodeURIComponent(window.location.href);
      var x="https://ads.ehealthcaresolutions.com/a/?"+a;
      var y=document.createElement("script");y.type="text/javascript";y.async=true;y.src=x;
      var z=document.getElementsByTagName("script")[0];
      z.parentNode.insertBefore(y, z);
      })();
      }
      else {
      (function() {
      var a="",b=[
      "adid=behavenet-behavenet-3",
      "site=ehs.pro.behavenet.behavenet",
      "size=320x50",
      "vpos=t",
      "hpos=l",
      "zone=",
      "iprof=md",
      "ims1=p38",
      "puid=$drupalUserID",
      ];
      for (var c=0;c<b.length;++c){a+=b[c]+"&";}a+="r="+Math.random()*1e16+"&url="+encodeURIComponent(window.location.href);
      var x="https://ads.ehealthcaresolutions.com/a/?"+a;
      var y=document.createElement("script");y.type="text/javascript";y.async=true;y.src=x;
      var z=document.getElementsByTagName("script")[0];
      z.parentNode.insertBefore(y, z);
      })();
      }
      </script>
      </div>
      <!-- END ASYNC UAT M - 728x90/320x50(t,l) -->
HERE;
  }

  protected function interstitial_ad($drupalUserID) {
    return <<<HERE
      <!-- ASYNC UAT M - inter - Physicians (MD)/Psychiatry/ -->
      <div id="behavenet-behavenet-4">
      <script type="text/javascript">
      (function() {
      var a="",b=[
      "adid=behavenet-behavenet-4",
      "site=ehs.pro.behavenet.behavenet",
      "size=inter",
      "zone=",
      "iprof=md",
      "ims1=p38",
      "puid=$drupalUserID",
      ];
      for (var c=0;c<b.length;++c){a+=b[c]+"&";}a+="r="+Math.random()*1e16+"&url="+encodeURIComponent(window.location.href);
      var x="https://ads.ehealthcaresolutions.com/a/?"+a;
      var y=document.createElement("script");y.type="text/javascript";y.async=true;y.src=x;
      var z=document.getElementsByTagName("script")[0];
      z.parentNode.insertBefore(y, z);
      })();
      </script>
      </div>
      <!-- END ASYNC UAT M - inter -->
HERE;
  }

  protected function content_footer_ad($drupalUserID) {
    return <<<HERE
      <!-- ASYNC UAT M - 300x600(t,l) - Physicians (MD)/Psychiatry/ -->
      <div id="behavenet-behavenet-5">
      <script type="text/javascript">
      var ehs_screenwidth=((navigator.userAgent.match(/iPhone/i)) || (navigator.userAgent.match(/iPod/i))) ? screen.width : window.innerWidth;
      if (ehs_screenwidth>=778) {
      (function() {
      var a="",b=[
      "adid=behavenet-behavenet-5",
      "site=ehs.pro.behavenet.behavenet",
      "size=300x250",
      "vpos=t",
      "hpos=l",
      "zone=",
      "iprof=md",
      "ims1=p38",
      "puid=$drupalUserID",
      ];
      for (var c=0;c<b.length;++c){a+=b[c]+"&";}a+="r="+Math.random()*1e16+"&url="+encodeURIComponent(window.location.href);
      var x="https://ads.ehealthcaresolutions.com/a/?"+a;
      var y=document.createElement("script");y.type="text/javascript";y.async=true;y.src=x;
      var z=document.getElementsByTagName("script")[0];
      z.parentNode.insertBefore(y, z);
      })();
      }
      </script>
      </div>
      <!-- END ASYNC UAT M - 300x600(t,l) -->
HERE;
  }

}

