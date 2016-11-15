/*!
 * froala_editor v2.3.3 (https://www.froala.com/wysiwyg-editor)
 * License https://froala.com/wysiwyg-editor/terms/
 * Copyright 2014-2016 Mautic team
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

  $.FE.PLUGINS.token = function (editor) {

    function apply () {
      editor.html.insert(' {');
      editor.$el.keyup();
    }

    return {
      apply: apply
    }
  }

  // Register the token command.
  $.FE.RegisterCommand('token', {
    title: 'Insert token',
    callback: function (cmd) {
      this.token.apply();
    },
    plugin: 'token'
  })

  // Add the token icon.
  $.FE.DefineIcon('token', {
    NAME: 'tag'
  });

}));
