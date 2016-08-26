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

  // Extend defaults.
  $.extend($.FE.DEFAULTS, {

  });

  $.FE.URLRegEx = /(\s|^|>)((http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+(\.[a-zA-Z]{2,3})?(:\d*)?(\/[^\s<]*)?)(\s|$|<)/gi;

  $.FE.PLUGINS.url = function (editor) {

    function _convertURLS (contents) {
      // All content zones.
      contents.each (function () {
        if (this.tagName == 'IFRAME') return;

        // Text node.
        if (this.nodeType == 3) {
          var text = this.textContent.replace(/&nbsp;/gi, '');

          // Check if text is URL.
          if ($.FE.URLRegEx.test(text)) {
            // Convert it to A.
            $(this).before(text.replace($.FE.URLRegEx, '$1<a href="$2">$2</a>$7'));

            $(this).remove();
          }
        }

        // Other type of node.
        else if (this.nodeType == 1 && ['A', 'BUTTON', 'TEXTAREA'].indexOf(this.tagName) < 0) {
          // Convert urls inside it.
          _convertURLS(editor.node.contents(this));
        }
      })
    }

    /*
     * Initialize.
     */
    function _init () {
      editor.events.on('paste.afterCleanup', function (html) {
        if ($.FE.URLRegEx.test(html)) {
          return html.replace($.FE.URLRegEx, '$1<a href="$2">$2</a>$7')
        }
      });

      editor.events.on('keyup', function (e) {
        var keycode = e.which;
        if (keycode == $.FE.KEYCODE.ENTER || keycode == $.FE.KEYCODE.SPACE) {
          _convertURLS(editor.node.contents(editor.$el.get(0)));
        }
      });

      editor.events.on('keydown', function (e) {
        var keycode = e.which;

        if (keycode == $.FE.KEYCODE.ENTER) {
          var el = editor.selection.element();

          if ((el.tagName == 'A' || $(el).parents('a').length) && editor.selection.info(el).atEnd) {
            e.stopImmediatePropagation();

            if (el.tagName !== 'A') el = $(el).parents('a')[0];
            $(el).after('&nbsp;' + $.FE.MARKERS);
            editor.selection.restore();

            return false;
          }
        }
      });
    }

    return {
      _init: _init
    }
  }

}));
