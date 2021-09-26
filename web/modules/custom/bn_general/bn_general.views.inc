<?php

/**
 * @file
 * Provide views data for bn_general plugins.
 */

/**
 * Implements hook_views_data().
 */
function bn_general_views_data() {
  $data = [];

  $data['views']['view_params'] = [
    'title' => t('View area with params'),
    'help' => t('Insert a view inside an area and override contextual filters.'),
    'area' => [
      'id' => 'view_params',
    ],
  ];
  $data['views']['term_search'] = [
    'title' => t('Terms search results'),
    'help' => t('To be used with the Behavenet Search view.'),
    'area' => [
      'id' => 'term_search',
    ],
  ];
  $data['views']['taxonomy_index_tid_options'] = [
    'title' => t('Term filter with additional options'),
    'help' => t('Basic term filter with options to limit the display by depth or parent ID'),
    'filter' => [
      'id' => 'taxonomy_index_tid_options',
    ],
  ];

  return $data;
}

/**
 * Implements hook_views_data_alter().
 */
function bn_general_views_data_alter(array &$data) {
  $data['node_field_data']['taxonomy_index_tid_options'] = [
    'help' => t('Display content if it has the selected taxonomy terms, but with more options.'),
    'real field' => 'nid',
    'filter' => [
      'title' => t('Has taxonomy terms (with options)'),
      'id' => 'taxonomy_index_tid_options',
    ],
  ];
}
