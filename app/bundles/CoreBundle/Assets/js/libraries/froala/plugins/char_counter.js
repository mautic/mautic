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

  

  // Extend defaults.
  $.extend($.FE.DEFAULTS, {
    charCounterMax: -1,
    charCounterCount: true
  });


  $.FE.PLUGINS.charCounter = function (editor) {
    var $counter;

    /**
     * Get the char number.
     */
    function count () {
      return editor.el.textContent.length;
    }

    /**
     * Check chars on typing.
     */
    function _checkCharNumber (e) {
      // Continue if infinite characters;
      if (editor.opts.charCounterMax < 0) return true;

      // Continue if enough characters.
      if (count() < editor.opts.charCounterMax) return true;

      // Stop if the key will produce a new char.
      var keyCode = e.which;
      if (!editor.keys.ctrlKey(e) && editor.keys.isCharacter(keyCode)) {
        e.preventDefault();
        e.stopPropagation();
        editor.events.trigger('charCounter.exceeded');
        return false;
      }

      return true;
    }

    /**
     * Check chars on paste.
     */
    function _checkCharNumberOnPaste (html) {
      if (editor.opts.charCounterMax < 0) return html;

      var len = $('<div>').html(html).text().length;
      if (len + count() <= editor.opts.charCounterMax) return html;

      editor.events.trigger('charCounter.exceeded');

      return '';
    }

    /**
     * Update the char counter.
     */
    function _updateCharNumber () {
      if (editor.opts.charCounterCount) {
        var chars = count() + (editor.opts.charCounterMax > 0 ?  '/' + editor.opts.charCounterMax : '');

        $counter.text(chars);

        if (editor.opts.toolbarBottom) {
          $counter.css('margin-bottom', editor.$tb.outerHeight(true))
        }

        // Scroll size correction.
        var scroll_size = editor.$wp.get(0).offsetWidth - editor.$wp.get(0).clientWidth;
        if (scroll_size >= 0) {
          if (editor.opts.direction == 'rtl') {
            $counter.css('margin-left', scroll_size);
          }
          else {
            $counter.css('margin-right', scroll_size);
          }
        }
      }
    }

    /*
     * Initialize.
     */
    function _init () {
      if (!editor.$wp) return false;

      if (!editor.opts.charCounterCount) return false;

      $counter = $('<span class="fr-counter"></span>');
      $counter.css('bottom', editor.$wp.css('border-bottom-width'));
      editor.$box.append($counter);

      editor.events.on('keydown', _checkCharNumber, true);
      editor.events.on('paste.afterCleanup', _checkCharNumberOnPaste);
      editor.events.on('keyup contentChanged input', function () {
        editor.events.trigger('charCounter.update');
      });

      editor.events.on('charCounter.update', _updateCharNumber);
      editor.events.trigger('charCounter.update');

      editor.events.on('destroy', function () {
        $(editor.o_win).off('resize.char' + editor.id);
        $counter.removeData().remove();
        $counter = null;
      });
    }

    return {
      _init: _init,
      count: count
    }
  }

}));
