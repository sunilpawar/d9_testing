<?php

namespace Drupal\ehealth\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SettingForm.
 */
class SettingForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'ehealth.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'setting_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = \Drupal::configFactory()->getEditable('ehealth.settings');
    $form['ehealth_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('E-health Token'),
      '#description' => '',
      '#default_value' => $config->get('ehealth_token'),
      '#size' => 60,
      '#maxlength' => 128,
      '#required' => TRUE,
    ];
    $form['ehealth_publisher_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('E-health Publisher ID'),
      '#description' => '',
      '#default_value' => $config->get('ehealth_publisher_id'),
      '#size' => 60,
      '#maxlength' => 128,
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValues() as $key => $value) {
      // @TODO: Validate fields.
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    \Drupal::configFactory()->getEditable('ehealth.settings')
      ->set('ehealth_publisher_id', $form_state->getValue('ehealth_publisher_id'))
      ->set('ehealth_token', $form_state->getValue('ehealth_token'))
      ->save();

    return parent::submitForm($form, $form_state);
  }
}
