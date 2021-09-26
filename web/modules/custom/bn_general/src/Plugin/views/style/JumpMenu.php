<?php

namespace Drupal\bn_general\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\style\StylePluginBase;

/**
 * Style plugin to render each item in an ordered or unordered list.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "jump_menu",
 *   title = @Translation("Jump Menu"),
 *   help = @Translation("Displays results as a select element jump menu."),
 *   theme = "views_view_jumpmenu",
 *   display_types = {"normal"}
 * )
 */
class JumpMenu extends StylePluginBase {

  /**
   * Does the style plugin allows to use style plugins.
   *
   * @var bool
   */
  protected $usesRowPlugin = TRUE;

  /**
   * Does the style plugin support custom css class for the rows.
   *
   * @var bool
   */
  protected $usesRowClass = TRUE;

  /**
   * Set default options.
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['text_field'] = ['default' => ''];
    // $options['wrapper_class'] = array('default' => 'item-list');
    return $options;
  }

  /**
   * Render the given style.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $fields = $this->view->display_handler->getFieldLabels();
    $form['text_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Text field'),
      '#options' => $fields,
      '#default_value' => $this->options['text_field'],
    ];
    // $form['wrapper_class'] = array(
    // '#title' => $this->t('Wrapper class'),
    // '#description' => $this->t('The class to provide on the wrapper,
    // outside the list.'),
    // '#type' => 'textfield',
    // '#size' => '30',
    // '#default_value' => $this->options['wrapper_class'],
    // );
  }

}
