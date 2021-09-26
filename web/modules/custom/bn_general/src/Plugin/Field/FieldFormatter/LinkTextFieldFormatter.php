<?php

namespace Drupal\bn_general\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\link\Plugin\Field\FieldFormatter\LinkFormatter;

/**
 * Plugin implementation of the 'link_text_field_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "link_text_field_formatter",
 *   label = @Translation("Link with fixed text"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class LinkTextFieldFormatter extends LinkFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = parent::defaultSettings();
    $settings['trim_length'] = '';
    $settings['link_text'] = '';
    unset($settings['url_only']);
    unset($settings['url_plain']);
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    // We want to borrow as much from the LinkFormatter as possible.
    $settings = parent::settingsForm($form, $form_state);

    // But we do not need these settings.
    unset($settings['url_only']);
    unset($settings['url_plain']);
    unset($settings['trim_length']);

    // Push this setting to the top of the form.
    $settings = [
      'link_text' => [
        '#type' => 'textfield',
        '#title' => $this->t('Use this text for the link'),
        '#description' => $this->t('Override any custom entered text with this text for the link.'),
        '#default_value' => $this->getSetting('link_text'),
        '#required' => TRUE,
      ],
    ] + $settings;

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Override link text with "@text"', ['@text' => $this->getSetting('link_text')]);
    $summary += parent::settingsSummary();
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    // Tweak the item titles to use the override text.
    $title = $this->getSetting('link_text');
    foreach ($items as $item) {
      $item->title = $title;
    }

    return parent::viewElements($items, $langcode);
  }

}
