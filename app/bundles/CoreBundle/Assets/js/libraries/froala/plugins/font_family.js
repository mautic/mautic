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

  

  $.extend($.FE.DEFAULTS, {
    fontFamily: {
      'Arial,Helvetica,sans-serif': 'Arial',
      'Georgia,serif': 'Georgia',
      'Impact,Charcoal,sans-serif': 'Impact',
      'Tahoma,Geneva,sans-serif': 'Tahoma',
      'Times New Roman,Times,serif': 'Times New Roman',
      'Verdana,Geneva,sans-serif': 'Verdana'
    },
    fontFamilySelection: false,
    fontFamilyDefaultSelection: 'Font Family'
  })

  $.FE.PLUGINS.fontFamily = function (editor) {
    function apply (val) {
      editor.format.applyStyle('font-family', val);
    }

    function refreshOnShow($btn, $dropdown) {
      $dropdown.find('.fr-command.fr-active').removeClass('fr-active').attr('aria-selected', false);
      $dropdown.find('.fr-command[data-param1="' + _getSelection() + '"]').addClass('fr-active').attr('aria-selected', true);

      var $list = $dropdown.find('.fr-dropdown-list');
      var $active = $dropdown.find('.fr-active').parent();
      if ($active.length) {
        $list.parent().scrollTop($active.offset().top - $list.offset().top - ($list.parent().outerHeight() / 2 - $active.outerHeight() / 2));
      }
      else {
        $list.parent().scrollTop(0);
      }
    }

    function _getArray (val) {
      var font_array = val.replace(/(sans-serif|serif|monospace|cursive|fantasy)/gi, '').replace(/"|'| /g, '').split(',');

      return $.grep(font_array, function (txt) { return txt.length > 0 });
    }

    /**
     * Return first match position.
     */
    function _matches (array1, array2) {
      for (var i = 0; i < array1.length; i++) {
        for (var j = 0; j < array2.length; j++) {
          if (array1[i] == array2[j]) {
            return [i, j];
          }
        }
      }

      return null;
    }

    function _getSelection () {
      var val = $(editor.selection.element()).css('font-family');
      var font_array = _getArray(val);

      var font_matches = [];
      for (var key in editor.opts.fontFamily) {
        if (editor.opts.fontFamily.hasOwnProperty(key)) {
          var c_font_array = _getArray(key);

          var match = _matches(font_array, c_font_array);
          if (match) {
            font_matches.push([key, match]);
          }
        }
      }

      if (font_matches.length === 0) return null;

      // Sort matches by their position.
      // Times,Arial should be detected as being Times, not Arial.
      font_matches.sort(function (a, b) {
        var f_diff = a[1][0] - b[1][0];
        if (f_diff === 0) {
          return a[1][1] - b[1][1];
        }
        else {
          return f_diff;
        }
      });

      return font_matches[0][0];
    }

    function refresh ($btn) {
      if (editor.opts.fontFamilySelection) {
        var val = $(editor.selection.element()).css('font-family').replace(/(sans-serif|serif|monospace|cursive|fantasy)/gi, '').replace(/"|'|/g, '').split(',');

        $btn.find('> span').text(editor.opts.fontFamily[_getSelection()] || val[0] || editor.opts.fontFamilyDefaultSelection);
      }
    }

    return {
      apply: apply,
      refreshOnShow: refreshOnShow,
      refresh: refresh
    }
  }

  // Register the font size command.
  $.FE.RegisterCommand('fontFamily', {
    type: 'dropdown',
    displaySelection: function (editor) {
      return editor.opts.fontFamilySelection;
    },
    defaultSelection: function (editor) {
      return editor.opts.fontFamilyDefaultSelection;
    },
    displaySelectionWidth: 120,
    html: function () {
      var c = '<ul class="fr-dropdown-list" role="presentation">';
      var options = this.opts.fontFamily;
      for (var val in options) {
        if (options.hasOwnProperty(val)) {
          c += '<li role="presentation"><a class="fr-command" tabIndex="-1" role="option" data-cmd="fontFamily" data-param1="' + val + '" style="font-family: ' + val + '" title="' + options[val] + '">' + options[val] + '</a></li>';
        }
      }
      c += '</ul>';

      return c;
    },
    title: 'Font Family',
    callback: function (cmd, val) {
      this.fontFamily.apply(val);
    },
    refresh: function ($btn) {
      this.fontFamily.refresh($btn);
    },
    refreshOnShow: function ($btn, $dropdown) {
      this.fontFamily.refreshOnShow($btn, $dropdown);
    },
    plugin: 'fontFamily'
  })

  // Add the font size icon.
  $.FE.DefineIcon('fontFamily', {
    NAME: 'font'
  });

}));
