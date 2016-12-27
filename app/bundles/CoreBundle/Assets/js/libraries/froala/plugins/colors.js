/*!
 * froala_editor v2.4.0 (https://www.froala.com/wysiwyg-editor)
 * License https://froala.com/wysiwyg-editor/terms/
 * Copyright 2014-2016 Froala Labs
 */

(function (factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define(['jquery'], factory);
    } else if (typeof module === 'object' && module.exports) {
        // Node/CommonJS
        module.exports = function( root, jQuery ) {
            if ( jQuery === undefined ) {
                // require('jQuery') returns a factory that requires window to
                // build a jQuery instance, we normalize how we use modules
                // that require this pattern but the window provided is a noop
                // if it's defined (how jquery works)
                if ( typeof window !== 'undefined' ) {
                    jQuery = require('jquery');
                }
                else {
                    jQuery = require('jquery')(root);
                }
            }
            factory(jQuery);
            return jQuery;
        };
    } else {
        // Browser globals
        factory(jQuery);
    }
}(function ($) {

  

  $.extend($.FE.POPUP_TEMPLATES, {
    'colors.picker': '[_BUTTONS_][_TEXT_COLORS_][_BACKGROUND_COLORS_]'
  })

  // Extend defaults.
  $.extend($.FE.DEFAULTS, {
    colorsText: [
      '#61BD6D', '#1ABC9C', '#54ACD2', '#2C82C9', '#9365B8', '#475577', '#CCCCCC',
      '#41A85F', '#00A885', '#3D8EB9', '#2969B0', '#553982', '#28324E', '#000000',
      '#F7DA64', '#FBA026', '#EB6B56', '#E25041', '#A38F84', '#EFEFEF', '#FFFFFF',
      '#FAC51C', '#F37934', '#D14841', '#B8312F', '#7C706B', '#D1D5D8', 'REMOVE'
    ],
    colorsBackground: [
      '#61BD6D', '#1ABC9C', '#54ACD2', '#2C82C9', '#9365B8', '#475577', '#CCCCCC',
      '#41A85F', '#00A885', '#3D8EB9', '#2969B0', '#553982', '#28324E', '#000000',
      '#F7DA64', '#FBA026', '#EB6B56', '#E25041', '#A38F84', '#EFEFEF', '#FFFFFF',
      '#FAC51C', '#F37934', '#D14841', '#B8312F', '#7C706B', '#D1D5D8', 'REMOVE'
    ],
    colorsStep: 7,
    colorsDefaultTab: 'text',
    colorsButtons: ['colorsBack', '|', '-']
  });

  $.FE.PLUGINS.colors = function (editor) {
    /*
     * Show the colors popup.
     */
    function _showColorsPopup () {
      var $btn = editor.$tb.find('.fr-command[data-cmd="color"]');

      var $popup = editor.popups.get('colors.picker');
      if (!$popup) $popup = _initColorsPopup();

      if (!$popup.hasClass('fr-active')) {
        // Colors popup
        editor.popups.setContainer('colors.picker', editor.$tb);

        // Refresh selected color.
        _refreshColor($popup.find('.fr-selected-tab').attr('data-param1'));

        // Colors popup left and top position.
        if ($btn.is(':visible')) {
          var left = $btn.offset().left + $btn.outerWidth() / 2;
          var top = $btn.offset().top + (editor.opts.toolbarBottom ? 10 : $btn.outerHeight() - 10);
          editor.popups.show('colors.picker', left, top, $btn.outerHeight());
        }
        else {
          editor.position.forSelection($popup);
          editor.popups.show('colors.picker');
        }
      }
    }

    /*
     * Hide colors popup.
     */
    function _hideColorsPopup () {
      // Hide popup.
      editor.popups.hide('colors.picker');
    }

    /**
     * Init the colors popup.
     */
    function _initColorsPopup () {
      var colors_buttons = '<div class="fr-buttons fr-colors-buttons">';

      if (editor.opts.toolbarInline) {
        // Colors buttons.
        if (editor.opts.colorsButtons.length > 0) {
          colors_buttons += editor.button.buildList(editor.opts.colorsButtons)
        }
      }

      colors_buttons += _colorsTabsHTML() + '</div>';

      var template = {
        buttons: colors_buttons,
        text_colors: _colorPickerHTML('text'),
        background_colors: _colorPickerHTML('background')
      };

      // Create popup.
      var $popup = editor.popups.create('colors.picker', template);

      _addAccessibility($popup);

      return $popup;
    }

    /*
     * HTML for the color picker text and background tabs.
     */
    function _colorsTabsHTML () {
      var tabs_html = '<div class="fr-colors-tabs fr-group">';

      // Text tab.
      tabs_html += '<span class="fr-colors-tab ' + (editor.opts.colorsDefaultTab == 'background' ? '' : 'fr-selected-tab ') + 'fr-command" tabIndex="-1" role="button" aria-pressed="' + (editor.opts.colorsDefaultTab == 'background' ? false : true) + '" data-param1="text" data-cmd="colorChangeSet" title="' + editor.language.translate('Text') + '">' + editor.language.translate('Text') + '</span>';

      // Background tab.
      tabs_html += '<span class="fr-colors-tab ' + (editor.opts.colorsDefaultTab == 'background' ? 'fr-selected-tab ' : '') + 'fr-command" tabIndex="-1" role="button" aria-pressed="' + (editor.opts.colorsDefaultTab == 'background' ? true : false) + '" data-param1="background" data-cmd="colorChangeSet" title="' + editor.language.translate('Background') + '">' + editor.language.translate('Background') + '</span>';

      return tabs_html + '</div>';
    }

    /*
     * HTML for the color picker colors.
     */
    function _colorPickerHTML (tab) {
      // Get colors according to tab name.
      var colors = (tab == 'text' ? editor.opts.colorsText : editor.opts.colorsBackground);

      // Create colors html.
      var colors_html = '<div class="fr-color-set fr-' + tab + '-color' + ((editor.opts.colorsDefaultTab == tab || (editor.opts.colorsDefaultTab != 'text' && editor.opts.colorsDefaultTab != 'background' && tab == 'text')) ? ' fr-selected-set' : '') + '">';

      // Add colors.
      for (var i = 0; i < colors.length; i++) {
        if (i !== 0 && i % editor.opts.colorsStep === 0) {
          colors_html += '<br>';
        }

        if (colors[i] != 'REMOVE') {
          colors_html += '<span class="fr-command fr-select-color" style="background: ' + colors[i] + ';" tabIndex="-1" aria-selected="false" role="button" data-cmd="' + tab + 'Color" data-param1="' + colors[i] + '"><span class="fr-sr-only">' + editor.language.translate('Color') + ' ' + colors[i] + '&nbsp;&nbsp;&nbsp;</span></span>';
        }

        else {
          colors_html += '<span class="fr-command fr-select-color" data-cmd="' + tab + 'Color" tabIndex="-1" role="button" data-param1="REMOVE" title="' + editor.language.translate('Clear Formatting') + '">' + editor.icon.create('remove') + '<span class="fr-sr-only">' + editor.language.translate('Clear Formatting') + '</span>' +  '</span>';
        }
      }

      return colors_html + '</div>';
    }

    /*
     * Register keyboard events.
     */
    function _addAccessibility ($popup) {
      // Register popup event.
      editor.events.on('popup.tab', function (e) {
        var $focused_item = $(e.currentTarget);

        // Skip if popup is not visible or focus is elsewere.
        if (!editor.popups.isVisible('colors.picker') || !$focused_item.is('span')) {
          return true;
        }
        var key_code = e.which;
        var status = true;

        // Tabbing.
        if ($.FE.KEYCODE.TAB == key_code) {
          var $tb = $popup.find('.fr-buttons');
          // Focus back the popup's toolbar if exists.
          status = !editor.accessibility.focusToolbar($tb, (e.shiftKey ? true : false));
        }
        // Arrows.
        else if ($.FE.KEYCODE.ARROW_UP == key_code || $.FE.KEYCODE.ARROW_DOWN == key_code || $.FE.KEYCODE.ARROW_LEFT == key_code || $.FE.KEYCODE.ARROW_RIGHT == key_code) {
          if ($focused_item.is('span.fr-select-color')) {
            // Get all current colors.
            var $colors = $focused_item.parent().find('span.fr-select-color');

            // Get focused item position.
            var index = $colors.index($focused_item);

            // Get color matrix dimensions.
            var columns = editor.opts.colorsStep;
            var lines = Math.floor($colors.length / columns);

            // Get focused item coordinates.
            var column = index % columns;
            var line = Math.floor(index / columns);

            var nextIndex = line * columns + column;
            var dimension = lines * columns;

            // Calculate next index. Go to the other opposite site of the matrix if there is no next adjacent element.
            // Up/Down: Traverse matrix lines.
            // Left/Right: Traverse the matrix as it is a vector.
            if ($.FE.KEYCODE.ARROW_UP == key_code) {
              nextIndex = (((nextIndex - columns) % dimension) + dimension) % dimension; // Javascript negative modulo bug.
            }
            else if ($.FE.KEYCODE.ARROW_DOWN == key_code) {
              nextIndex = (nextIndex + columns) % dimension;
            }
            else if ($.FE.KEYCODE.ARROW_LEFT == key_code) {
              nextIndex = (((nextIndex - 1) % dimension) + dimension) % dimension; // Javascript negative modulo bug.
            }
            else if ($.FE.KEYCODE.ARROW_RIGHT == key_code) {
              nextIndex = (nextIndex + 1) % dimension;
            }

            // Get the next element based on the new index.
            var $el = $($colors.get(nextIndex));

            // Focus.
            editor.events.disableBlur();
            $el.focus();

            status = false;
          }
        }
        // ENTER or SPACE.
        else if ($.FE.KEYCODE.ENTER == key_code) {

          editor.button.exec($focused_item);
          status = false;
        }

        // Prevent propagation.
        if (status === false) {
          e.preventDefault();
          e.stopPropagation();
        }

        return status;
      }, true);
    }

    /*
     * Show the current selected color.
     */
    function _refreshColor (tab) {
      var $popup = editor.popups.get('colors.picker');
      var $element = $(editor.selection.element());

      // The color css property.
      var color_type;
      if (tab == 'background') {
        color_type = 'background-color';
      }
      else {
        color_type = 'color';
      }

      var $current_color = $popup.find('.fr-' + tab + '-color .fr-select-color');
      // Remove current color selection.
      $current_color.find('.fr-selected-color').remove();
      $current_color.removeClass('fr-active-item');
      $current_color.not('[data-param1="REMOVE"]').attr('aria-selected', false);

      // Find the selected color.
      while ($element.get(0) != editor.el) {
        // Transparent or black.
        if ($element.css(color_type) == 'transparent' || $element.css(color_type) == 'rgba(0, 0, 0, 0)') {
          $element = $element.parent();
        }

        // Select the correct color.
        else {
          var $select_color = $popup.find('.fr-' + tab + '-color .fr-select-color[data-param1="' + editor.helpers.RGBToHex($element.css(color_type)) + '"]');
          // Add checked icon.
          $select_color.append('<span class="fr-selected-color" aria-hidden="true">\uf00c</span>');
          $select_color.addClass('fr-active-item').attr('aria-selected', true);
          break;
        }
      }
    }

    /*
     * Change the colors' tab.
     */
    function _changeSet ($tab, val) {
      // Only on the tab that is not selected yet. On left click only.
      if (!$tab.hasClass('fr-selected-tab')) {
        // Switch selected tab.
        $tab.siblings().removeClass('fr-selected-tab').attr('aria-pressed', false);
        $tab.addClass('fr-selected-tab').attr('aria-pressed', true);

        // Switch the color set.
        $tab.parents('.fr-popup').find('.fr-color-set').removeClass('fr-selected-set');
        $tab.parents('.fr-popup').find('.fr-color-set.fr-' + val + '-color').addClass('fr-selected-set');

        // Refresh selected color.
        _refreshColor(val);

      }
      // Focus popup.
      editor.accessibility.focusPopup($tab.parents('.fr-popup'));
    }

    /*
     * Change background color.
     */
    function background (val) {
      // Set background  color.
      if (val != 'REMOVE') {
        editor.format.applyStyle('background-color', editor.helpers.HEXtoRGB(val));
      }

      // Remove background color.
      else {
        editor.format.removeStyle('background-color');
      }

      _hideColorsPopup();
    }

    /*
     * Change text color.
     */
    function text (val) {
      // Set text color.
      if (val != 'REMOVE') {
        editor.format.applyStyle('color', editor.helpers.HEXtoRGB(val));
      }

      // Remove text color.
      else {
        editor.format.removeStyle('color');
      }

      _hideColorsPopup();
    }

    /*
     * Go back to the inline editor.
     */
    function back () {
      editor.popups.hide('colors.picker');
      editor.toolbar.showInline();
    }

    return {
      showColorsPopup: _showColorsPopup,
      hideColorsPopup: _hideColorsPopup,
      changeSet: _changeSet,
      background: background,
      text: text,
      back: back
    }
  }

  // Toolbar colors button.
  $.FE.DefineIcon('colors', { NAME: 'tint' });
  $.FE.RegisterCommand('color', {
    title: 'Colors',
    undo: false,
    focus: true,
    refreshOnCallback: false,
    popup: true,
    callback: function () {
      if (!this.popups.isVisible('colors.picker')) {
        this.colors.showColorsPopup();
      }
      else {
        if (this.$el.find('.fr-marker').length) {
          this.events.disableBlur();
          this.selection.restore();
        }
        this.popups.hide('colors.picker');
      }
    },
    plugin: 'colors'
  });

  // Select text color command.
  $.FE.RegisterCommand('textColor', {
    undo: true,
    callback: function (cmd, val) {
      this.colors.text(val);
    }
  });

  // Select background color command.
  $.FE.RegisterCommand('backgroundColor', {
    undo: true,
    callback: function (cmd, val) {
      this.colors.background(val);
    }
  });

  $.FE.RegisterCommand('colorChangeSet', {
    undo: false,
    focus: false,
    callback: function (cmd, val) {
      var $tab = this.popups.get('colors.picker').find('.fr-command[data-cmd="' + cmd + '"][data-param1="' + val + '"]');
      this.colors.changeSet($tab, val);
    }
  });

  // Colors back.
  $.FE.DefineIcon('colorsBack', { NAME: 'arrow-left' });
  $.FE.RegisterCommand('colorsBack', {
    title: 'Back',
    undo: false,
    focus: false,
    back: true,
    refreshAfterCallback: false,
    callback: function () {
      this.colors.back();
    }
  });

  $.FE.DefineIcon('remove', { NAME: 'eraser' });

}));
