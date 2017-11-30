/*!
 * froala_editor v2.4.2 (https://www.froala.com/wysiwyg-editor)
 * License https://froala.com/wysiwyg-editor/terms/
 * Copyright 2014-2017 Froala Labs
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
    'forms.edit': '[_BUTTONS_]',
    'forms.update': '[_BUTTONS_][_TEXT_LAYER_]'
  })

  $.extend($.FE.DEFAULTS, {
    formEditButtons: ['inputStyle', 'inputEdit'],
    formStyles: {
      'fr-rounded': 'Rounded',
      'fr-large': 'Large'
    },
    formMultipleStyles: true,
    formUpdateButtons: ['inputBack', '|']
  })

  $.FE.PLUGINS.forms = function (editor) {
    var current_input;

    /**
     * Input mousedown.
     */
    function _inputMouseDown (e) {
      e.preventDefault();
      editor.selection.clear();
      $(this).data('mousedown', true);
    }

    /**
     * Mouseup on the input.
     */
    function _inputMouseUp (e) {
      // Mousedown was made.
      if ($(this).data('mousedown')) {
        e.stopPropagation();
        $(this).removeData('mousedown');

        current_input = this;

        showEditPopup(this);
      }

      e.preventDefault();
    }

    /**
     * Cancel if mousedown was made on any input.
     */
    function _cancelSelection () {
      editor.$el.find('input, textarea, button').removeData('mousedown');
    }

    /**
     * Touch move.
     */
    function _inputTouchMove () {
      $(this).removeData('mousedown');
    }

    /**
     * Assign the input events.
     */
    function _bindEvents () {
      editor.events.$on(editor.$el, editor._mousedown, 'input, textarea, button', _inputMouseDown);
      editor.events.$on(editor.$el, editor._mouseup, 'input, textarea, button', _inputMouseUp);
      editor.events.$on(editor.$el, 'touchmove', 'input, textarea, button', _inputTouchMove);
      editor.events.$on(editor.$el, editor._mouseup, _cancelSelection);
      editor.events.$on(editor.$win, editor._mouseup, _cancelSelection);

      _initUpdatePopup(true);
    }

    /**
     * Get the current button.
     */
    function getInput () {
      if (current_input) return current_input;

      return null;
    }

    /**
     * Init the edit button popup.
     */
    function _initEditPopup () {
      // Button edit buttons.
      var buttons = '';
      if (editor.opts.formEditButtons.length > 0) {
        buttons = '<div class="fr-buttons">' + editor.button.buildList(editor.opts.formEditButtons) + '</div>';
      }

      var template = {
        buttons: buttons
      };

      // Set the template in the popup.
      var $popup = editor.popups.create('forms.edit', template);

      if (editor.$wp) {
        editor.events.$on(editor.$wp, 'scroll.link-edit', function () {
          if (get() && editor.popups.isVisible('forms.edit')) {
            showEditPopup(getInput());
          }
        });
      }

      return $popup;
    }

    /**
     * Show the edit button popup.
     */
    function showEditPopup (input) {
      var $popup = editor.popups.get('forms.edit');
      if (!$popup) $popup = _initEditPopup();

      current_input = input;
      var $input = $(input);

      editor.popups.refresh('forms.edit');

      editor.popups.setContainer('forms.edit', editor.$sc);
      var left = $input.offset().left + $input.outerWidth() / 2;
      var top = $input.offset().top + $input.outerHeight();

      editor.popups.show('forms.edit', left, top, $input.outerHeight());
    }

    /**
     * Refresh update button popup callback.
     */
    function _refreshUpdateCallback () {
      var $popup = editor.popups.get('forms.update');

      var input = getInput();
      if (input) {
        var $input = $(input);
        if ($input.is('button')) {
          $popup.find('input[type="text"][name="text"]').val($input.text());
        }
        else {
          $popup.find('input[type="text"][name="text"]').val($input.attr('placeholder'));
        }
      }

      $popup.find('input[type="text"][name="text"]').trigger('change');
    }

    /**
     * Hide update button popup callback.
     */
    function _hideUpdateCallback () {
      current_input = null;
    }

    /**
     * Init update button popup.
     */
    function _initUpdatePopup (delayed) {
      if (delayed) {
        editor.popups.onRefresh('forms.update', _refreshUpdateCallback);
        editor.popups.onHide('forms.update', _hideUpdateCallback);

        return true;
      }

      // Button update buttons.
      var buttons = '';
      if (editor.opts.formUpdateButtons.length >= 1) {
        buttons = '<div class="fr-buttons">' + editor.button.buildList(editor.opts.formUpdateButtons) + '</div>';
      }

      var text_layer = '';
      var tab_idx = 0;
      text_layer = '<div class="fr-forms-text-layer fr-layer fr-active">';
      text_layer += '<div class="fr-input-line"><input name="text" type="text" placeholder="Text" tabIndex="' + (++tab_idx) + '"></div>';

      text_layer += '<div class="fr-action-buttons"><button class="fr-command fr-submit" data-cmd="updateInput" href="#" tabIndex="' + (++tab_idx) + '" type="button">' + editor.language.translate('Update') + '</button></div></div>'

      var template = {
        buttons: buttons,
        text_layer: text_layer
      }

      // Set the template in the popup.
      var $popup = editor.popups.create('forms.update', template);

      return $popup;
    }

    /**
     * Show the button update popup.
     */
    function showUpdatePopup () {
      var input = getInput();
      if (input) {
        var $input = $(input);

        var $popup = editor.popups.get('forms.update');
        if (!$popup) $popup = _initUpdatePopup();

        if (!editor.popups.isVisible('forms.update')) {
          editor.popups.refresh('forms.update');
        }

        editor.popups.setContainer('forms.update', editor.$sc);
        var left = $input.offset().left + $input.outerWidth() / 2;
        var top = $input.offset().top + $input.outerHeight();

        editor.popups.show('forms.update', left, top, $input.outerHeight());
      }
    }

    /**
     * Apply specific style.
     */
    function applyStyle (val, formStyles, multipleStyles) {
      if (typeof formStyles == 'undefined') formStyles = editor.opts.formStyles;
      if (typeof multipleStyles == 'undefined') multipleStyles = editor.opts.formMultipleStyles;

      var input = getInput();
      if (!input) return false;

      // Remove multiple styles.
      if (!multipleStyles) {
        var styles = Object.keys(formStyles);
        styles.splice(styles.indexOf(val), 1);
        $(input).removeClass(styles.join(' '));
      }

      $(input).toggleClass(val);
    }

    /**
     * Back button in update button popup.
     */
    function back () {
      editor.events.disableBlur();
      editor.selection.restore();
      editor.events.enableBlur();

      var input = getInput();

      if (input && editor.$wp) {
        if (input.tagName == 'BUTTON') editor.selection.restore();
        showEditPopup(input);
      }
    }

    /**
     * Hit the update button in the input popup.
     */
    function updateInput () {
      var $popup = editor.popups.get('forms.update');

      var input = getInput();
      if (input) {
        var $input = $(input);
        var val = $popup.find('input[type="text"][name="text"]').val() || '';

        if (val.length) {
          if ($input.is('button')) {
            $input.text(val);
          }
          else {
            $input.attr('placeholder', val);
          }
        }

        editor.popups.hide('forms.update');
        showEditPopup(input);
      }
    }

    /**
     * Initialize.
     */
    function _init () {
      // Bind input events.
      _bindEvents();

      // Prevent form submit.
      editor.events.$on(editor.$el, 'submit', 'form', function (e) {
        e.preventDefault();
        return false;
      })
    }

    return {
      _init: _init,
      updateInput: updateInput,
      getInput: getInput,
      applyStyle: applyStyle,
      showUpdatePopup: showUpdatePopup,
      showEditPopup: showEditPopup,
      back: back
    }
  }

  // Register command to update input.
  $.FE.RegisterCommand('updateInput', {
    undo: false,
    focus: false,
    title: 'Update',
    callback: function () {
      this.forms.updateInput();
    }
  });

  // Link styles.
  $.FE.DefineIcon('inputStyle', { NAME: 'magic' })
  $.FE.RegisterCommand('inputStyle', {
    title: 'Style',
    type: 'dropdown',
    html: function () {
      var c = '<ul class="fr-dropdown-list">';
      var options =  this.opts.formStyles;
      for (var cls in options) {
        if (options.hasOwnProperty(cls)) {
          c += '<li><a class="fr-command" tabIndex="-1" data-cmd="inputStyle" data-param1="' + cls + '">' + this.language.translate(options[cls]) + '</a></li>';
        }
      }
      c += '</ul>';

      return c;
    },
    callback: function (cmd, val) {
      var input = this.forms.getInput();

      if (input) {
        this.forms.applyStyle(val);
        this.forms.showEditPopup(input);
      }
    },
    refreshOnShow: function ($btn, $dropdown) {
      var input = this.forms.getInput();

      if (input) {
        var $input = $(input);
        $dropdown.find('.fr-command').each (function () {
          var cls = $(this).data('param1');
          $(this).toggleClass('fr-active', $input.hasClass(cls));
        })
      }
    }
  });

  $.FE.DefineIcon('inputEdit', { NAME: 'edit' });
  $.FE.RegisterCommand('inputEdit', {
    title: 'Edit Button',
    undo: false,
    refreshAfterCallback: false,
    callback: function () {
      this.forms.showUpdatePopup();
    }
  })

  $.FE.DefineIcon('inputBack', { NAME: 'arrow-left' });
  $.FE.RegisterCommand('inputBack', {
    title: 'Back',
    undo: false,
    focus: false,
    back: true,
    refreshAfterCallback: false,
    callback: function () {
      this.forms.back();
    }
  });

  // Register command to update button.
  $.FE.RegisterCommand('updateInput', {
    undo: false,
    focus: false,
    title: 'Update',
    callback: function () {
      this.forms.updateInput();
    }
  });

}));
