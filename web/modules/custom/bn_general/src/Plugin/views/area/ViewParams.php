<?php

namespace Drupal\bn_general\Plugin\views\area;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\area\View;

/**
 * Insert a view inside of an area allowing overrides of contextual filters.
 *
 * @ingroup views_area_handlers
 *
 * @ViewsArea("view_params")
 */
class ViewParams extends View {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['override_arguments'] = array('default' => FALSE);
    $options['arguments'] = array('default' => '');
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['override_arguments'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Override contextual filters'),
      '#default_value' => $this->options['override_arguments'],
      '#description' => $this->t('Allow contextual filter arguments to be passed to the embedded view.'),
    );
    $form['arguments'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Contextual filter value(s)'),
      '#default_value' => $this->options['arguments'],
      '#states' => [
        'visible' => [
          ':input[name="options[override_arguments]"]' => array('checked' => TRUE),
        ],
      ],
    );
  }

  /**
   * {@inheritdoc}
   */
  public function render($empty = FALSE) {
    if (!empty($this->options['view_to_insert'])) {
      list($view_name, $display_id) = explode(':', $this->options['view_to_insert']);

      $view = $this->viewStorage->load($view_name)->getExecutable();

      $options = $this->options;
      if ($options['override_arguments']) {
        $view->args[] = $options['arguments'];
      }

      if (empty($view) || !$view->access($display_id)) {
        return array();
      }
      $view->setDisplay($display_id);

      // Avoid recursion
      $view->parent_views += $this->view->parent_views;
      $view->parent_views[] = "$view_name:$display_id";

      // Check if the view is part of the parent views of this view
      $search = "$view_name:$display_id";
      if (in_array($search, $this->view->parent_views)) {
        drupal_set_message(t("Recursion detected in view @view display @display.", array('@view' => $view_name, '@display' => $display_id)), 'error');
      }
      else {
        if (!empty($this->options['inherit_arguments']) && !empty($this->view->args)) {
          $output = $view->preview($display_id, $this->view->args);
        }
        else {
          $output = $view->preview($display_id);
        }
        $this->isEmpty = $view->display_handler->outputIsEmpty();
        return $output;
      }
    }
    return array();
  }

}
