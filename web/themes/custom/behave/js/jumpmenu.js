/**
 * @file
 * Javascript required for jump menus.
 */

(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.behave_jumpmenu = {
    attach: function (context, settings) {
      $(".js-jumpmenu", context).change(function() {
        window.location = $(this).find("option:selected").val();
      });
    }
  };
}) (jQuery, Drupal);
