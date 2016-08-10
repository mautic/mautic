/*!
 * froala_editor v2.3.4 (https://www.froala.com/wysiwyg-editor)
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

  'use strict';

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

      return $popup;
    }

    /*
     * HTML for the color picker text and background tabs.
     */
    function _colorsTabsHTML () {
      var tabs_html = '<div class="fr-colors-tabs">';

      // Text tab.
      tabs_html += '<span class="fr-colors-tab ' + (editor.opts.colorsDefaultTab == 'background' ? '' : 'fr-selected-tab ') + 'fr-command" data-param1="text" data-cmd="colorChangeSet" title="' + editor.language.translate('Text') + '">' + editor.language.translate('Text') + '</span>';

      // Background tab.
      tabs_html += '<span class="fr-colors-tab ' + (editor.opts.colorsDefaultTab == 'background' ? 'fr-selected-tab ' : '') + 'fr-command" data-param1="background" data-cmd="colorChangeSet" title="' + editor.language.translate('Background') + '">' + editor.language.translate('Background') + '</span>';

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
          colors_html += '<span class="fr-command fr-select-color" style="background: ' + colors[i] + ';" data-cmd="' + tab + 'Color" data-param1="' + colors[i] + '"></span>';
        }

        else {
          colors_html += '<span class="fr-command fr-select-color" data-cmd="' + tab + 'Color" data-param1="REMOVE" title="' + editor.language.translate('Clear Formatting') + '">' + editor.icon.create('remove') + '</span>';
        }
      }

      return colors_html + '</div>';
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

      // Remove current color selection.
      $popup.find('.fr-' + tab + '-color .fr-select-color').removeClass('fr-selected-color');

      // Find the selected color.
      while ($element.get(0) != editor.$el.get(0)) {
        // Transparent or black.
        if ($element.css(color_type) == 'transparent' || $element.css(color_type) == 'rgba(0, 0, 0, 0)') {
          $element = $element.parent();
        }

        // Select the correct color.
        else {
          $popup.find('.fr-' + tab + '-color .fr-select-color[data-param1="' + editor.helpers.RGBToHex($element.css(color_type)) + '"]').addClass('fr-selected-color');
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
        $tab.siblings().removeClass('fr-selected-tab');
        $tab.addClass('fr-selected-tab');

        // Switch the color set.
        $tab.parents('.fr-popup').find('.fr-color-set').removeClass('fr-selected-set');
        $tab.parents('.fr-popup').find('.fr-color-set.fr-' + val + '-color').addClass('fr-selected-set');

        // Refresh selected color.
        _refreshColor(val);
      }
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
        if (this.$el.find('.fr-marker')) {
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
