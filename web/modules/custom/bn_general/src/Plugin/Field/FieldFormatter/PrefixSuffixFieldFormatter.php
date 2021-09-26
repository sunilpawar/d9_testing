<?php

namespace Drupal\bn_general\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'prefix_suffix_field_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "prefix_suffix_field_formatter",
 *   label = @Translation("Prefix/Suffix field formatter"),
 *   field_types = {
 *     "text",
 *     "string",
 *     "string_long"
 *   }
 * )
 */
class PrefixSuffixFieldFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'prefix' => '',
      'suffix' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $settingsForm = parent::settingsForm($form, $form_state);

    $settingsForm['prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Prefix'),
      '#description' => $this->t('Prepend the value of this field with this text.'),
      '#default_value' => $this->getSetting('prefix'),
    ];
    $settingsForm['suffix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Suffix'),
      '#description' => $this->t('Append the value of this field with this text.'),
      '#default_value' => $this->getSetting('suffix'),
    ];

    return $settingsForm;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $settings = $this->getSettings();
    if (!empty($settings['prefix'])) {
      $summary[] = $this->t('Prefix: @prefix', ['@prefix' => $settings['prefix']]);
    }
    if (!empty($settings['suffix'])) {
      $summary[] = $this->t('Suffix: @suffix', ['@suffix' => $settings['suffix']]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $prefix = $this->getSetting('prefix');
    $suffix = $this->getSetting('suffix');

    foreach ($items as $delta => $item) {
      $elements[$delta] = ['#markup' => $prefix . Html::escape($item->value) . $suffix];
    }

    return $elements;
  }

}
