<?php

namespace Drupal\ehealth\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Database\Database;

/**
 * Provides a 'PixelBlock' block.
 *
 * @Block(
 *  id = "pixel_block",
 *  admin_label = @Translation("Pixel block"),
 * )
 */
class PixelBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $currentUser = \Drupal::currentUser();
    if (!$currentUser->isAuthenticated()) {
      return;
    }
    // Get current user ID
    $drupalUserID = $currentUser->id();
    // Get Database connection
    $conn = Database::getConnection();
    // Check User doing signin first time
    $details = $conn->select('user_first_login', 'n')
      ->fields('n', ['first_time_login', 'updated_date'])
      ->condition('uid', $drupalUserID, '=')
      ->condition('uid', 1, '!=')
      ->execute()
      ->fetchObject();

    if ($details->first_time_login == 0) {
      // Its not first time then do not show block
      return;
    }
    // Get user Profile details for these fields
    $userProfile = \Drupal\user\Entity\User::load($drupalUserID);
    $config = \Drupal::configFactory()->getEditable('ehealth.settings');

    // parameter used for pixel authentication
    $fieldNames = [];
    $fieldNames['t'] = $config->get('ehealth_token');

    /*
    $fieldNames['fname'] = $userProfile->field_first_name->value;
    $fieldNames['lname'] = $userProfile->field_last_name->value;
    $fieldNames['npi'] = $userProfile->field_npi->value;
    $fieldNames['zip'] = $userProfile->field_zipcode->value ?? '08648';
    */

    $fieldNames['fname'] = $userProfile->field_name_first->value;
    $fieldNames['lname'] = $userProfile->field_name_last->value;
    $fieldNames['npi'] = $userProfile->field_npi->value;
    //$fieldNames['zip'] = $userProfile->field_zipcode->value;
    $fieldNames['zip'] = $userProfile->field_zipcode_plain_text->value;

    $fieldNames['syssource'] = $config->get('ehealth_publisher_id');
    $fieldNames['indid'] = $drupalUserID;

    // Remove empty value element from array.
    $fieldNames = array_filter($fieldNames);
    $p = [];
    foreach ($fieldNames as $n => $v) {
      $p[] = "$n=" . urlencode($v);
    }
    // name value for submitting to server
    $nameValueReq = implode('&', $p);

    //  Initiate curl call to get result
    $request_url = "https://ws1.ehealthcaresolutions.com/ws/wr/?";
    $request = $request_url . $nameValueReq;
    //$request = 'https://ws1.ehealthcaresolutions
    //.com/ws/wr/?t=fhrmIr5n4lkijl78Jkb7Bk651nJnlJ5kjnk6&fname=Scott&lname=Adler&zip=08648&syssource=behavenet&indid=test123';
    $this->testlog($request);
    $ch = curl_init($request);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    $result = curl_exec($ch);
    curl_close($ch);

    // Get the result
    $ehsid = json_decode($result);
    $this->testlog($ehsid);
    // If result is not empty then load pixel in the block.
    $pixelBlock = '';
    if ($ehsid->result != "") {
      $pub_id = $config->get('ehealth_publisher_id');
      $pixelBlock = "<div><img src='http://ws1.ehealthcaresolutions.com/ws/cd/?pub="
        . $pub_id . "&eid=" . $ehsid->result . "'style='display:none;'></div>";

    }

    // Since pixed are displayed now, update flag to 0
    $conn = Database::getConnection();
    $conn->update('user_first_login')
      ->fields([
        'first_time_login' => 0,
        'updated_date' => \Drupal::time()->getRequestTime(),
      ])
      ->condition('uid', $drupalUserID, '=')
      ->execute();

    if (!empty($pixelBlock)) {
      return [
        'pixel_block' => [
          '#theme' => 'pixel_reg',
          '#content' => $pixelBlock,
          '#cache' => [
            'max-age' => 0,
          ]
        ]
      ];
    }

    return;
  }

  public function testlog($message) {
    // log your message
error_log(print_r($message, TRUE) . "\n", 3, '/home/u16-ma6yygv9dxmz/www/behavenet.com/public_html/modules/custom/ehealth/src/Plugin/Block/log');
  }
}

