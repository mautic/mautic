/*!
 * froala_editor v2.2.4 (https://www.froala.com/wysiwyg-editor)
 * License GPL v3
 * Copyright Mautic
 */

var FroalaEditorForFileManager;

function SetUrl( url, width, height, alt ) {
  FroalaEditorForFileManager.image.insert(url, false);
  oWindow = null;
}

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

  $.FE.PLUGINS.fileManager = function (editor) {
    var $current_image;

    var urlobj;

    /*
     * Show the file manager.
     */
    function show () {
      FroalaEditorForFileManager = editor;
      openServerBrowser(
        mauticBasePath + '/' + mauticAssetPrefix + 'app/bundles/CoreBundle/Assets/js/libraries/ckeditor/filemanager/index.html?type=Images',
        screen.width * 0.7,
        screen.height * 0.7
      );
    }

    function openServerBrowser( url, width, height ) {
      var iLeft = (screen.width - width) / 2 ;
      var iTop = (screen.height - height) / 2 ;
      var sOptions = "toolbar=no,status=no,resizable=yes,dependent=yes" ;
      sOptions += ",width=" + width ;
      sOptions += ",height=" + height ;
      sOptions += ",left=" + iLeft ;
      sOptions += ",top=" + iTop ;
      var oWindow = window.open( url, "BrowseWindow", sOptions ) ;
    }

    return {
      require: ['image'],
      show: show
    }
  };

  if (!$.FE.PLUGINS.image) {
    throw new Error('File manager plugin requires image plugin.');
  }

  $.FE.DEFAULTS.imageInsertButtons.push('fileManager');

  $.FE.RegisterCommand('fileManager', {
    title: 'Browse',
    undo: false,
    focus: false,
    callback: function () {
      this.fileManager.show();
    },
    plugin: 'fileManager'
  })

  // Add the font size icon.
  $.FE.DefineIcon('fileManager', {
    NAME: 'folder'
  });

}));
