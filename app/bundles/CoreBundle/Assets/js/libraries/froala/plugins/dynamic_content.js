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

  $.FE.PLUGINS.dynamicContent = function (editor) {

    /**
     * Check if fullscreen mode is active.
     */
    function isActive () {
      return true;
    }

    /**
     * Exec fullscreen.
     */
    function toggle (val) {
        editor.events.focus(true);
        editor.selection.restore();

        editor.html.insert('{dynamiccontent="' + val + '"}');
    }

    function _init () {
        Mautic.updateDynamicContentDropdown();
    }

    return {
      _init: _init,
      toggle: toggle,
      isActive: isActive
    }
  };

  // Register the font size command.
  $.FE.RegisterCommand('dynamicContent', {
    title: 'Dynamic Content',
    forcedRefresh: true,
    type: 'dropdown',
    callback: function (cmd, val) {
      this.dynamicContent.toggle(val);
    },
    plugin: 'dynamicContent'
  });

  // Add the font size icon.
  $.FE.DefineIcon('dynamicContent', {
    NAME: 'book'
  });

}));
