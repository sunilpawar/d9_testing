/**
 * @file
 * Javascript that runs on all Behavenet pages. This is pulled in from the
 * theme's info.yml file.
 */

(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.behave_general = {
    attach: function (context, settings) {
      behave_init_glossary(context, settings);
    }
  };

  function behave_init_glossary(context, settings) {
    // Expand collapse functionality for the Glossary listing the the sidebar.
    var options = {
      open_char : '&#9660;',
      close_char : '&#9658;',
      animation : 300
    };
    $('#block-glossarylisting-2', context).once('glossary-listing').each(function () {
      $(this)
      // Find all child ul elements and close them by default.
        .find('li > ul').each(function () {
        $(this)
          .hide()
          .parent()
          .prepend('<span class="js-tree js-closed" style="cursor:pointer;">' + options.close_char + '</span>')
          .addClass('js-hasChildren');
      });

      // Setup click events.
      $('span.js-tree', context).click(function () {
        var $this = $(this);
        if ($this.hasClass('js-closed')) {
          $this.html(options.open_char)
            .removeClass('js-closed')
            .addClass('js-open')
            .siblings('ul').show(options.animation);
        }
        else {
          $this.html(options.close_char)
            .removeClass('js-open')
            .addClass('js-closed')
            .siblings('ul').hide(options.animation);
        }
      });
    });
  }

}) (jQuery, Drupal);
