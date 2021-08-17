<?php

namespace Drupal\bn_general\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'year_only_date_widget' widget.
 *
 * @FieldWidget(
 *   id = "year_only_date_widget",
 *   label = @Translation("Year-only date widget"),
 *   field_types = {
 *     "datetime"
 *   }
 * )
 */
class YearOnlyDateWidget extends WidgetBase {

  /**
   * Holds display options for this widget.
   *
   * @var array
   */
  protected $displayOptions = [];

  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    $this->displayOptions = [
      'select' => $this->t('Select list'),
      'textfield' => $this->t('Text field'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'start_year' => '2001',
      'end_year' => '+3 years',
      'display' => 'select',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];

    $elements['start_year'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Start year'),
      '#description' => $this->t('Enter a 4-digit year or a relative date such as <em>-3 years</em>'),
      '#default_value' => $this->getSetting('start_year'),
      '#required' => TRUE,
    ];
    $elements['end_year'] = [
      '#type' => 'textfield',
      '#title' => $this->t('End year'),
      '#description' => $this->t('Enter a 4-digit year or a relative date such as <em>+3 years</em>'),
      '#default_value' => $this->getSetting('end_year'),
      '#required' => TRUE,
    ];
    $elements['display'] = [
      '#type' => 'select',
      '#title' => $this->t('Display year-only entry as'),
      '#default_value' => $this->getSetting('display'),
      '#required' => TRUE,
      '#options' => $this->displayOptions,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $summary[] = $this->t('Years from @start to @end', [
      '@start' => $this->getSetting('start_year'),
      '@end' => $this->getSetting('end_year'),
    ]);
    $summary[] = $this->t('Input method: @display.', ['@display' => $this->displayOptions[$this->getSetting('display')]]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    if ($this->getSetting('display') == 'select') {
      $start = is_numeric($this->getSetting('start_year')) ? $this->getSetting('start_year') : date('Y', strtotime($this->getSetting('start_year')));
      $end = is_numeric($this->getSetting('end_year')) ? $this->getSetting('end_year') : date('Y', strtotime($this->getSetting('end_year')));

      $options = [];
      for ($year = $start; $year <= $end; $year++) {
        $options[$year . '-01-01'] = $year;
      }
      $element['value'] = $element + [
        '#type' => 'select',
        '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
        '#options' => $options,
      ];
    }
    else {
      $element['value'] = $element + [
        '#type' => 'textfield',
        '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
        '#size' => $this->getSetting('size'),
        '#placeholder' => $this->getSetting('placeholder'),
        '#maxlength' => $this->getFieldSetting('max_length'),
      ];
    }

    return $element;
  }

}
