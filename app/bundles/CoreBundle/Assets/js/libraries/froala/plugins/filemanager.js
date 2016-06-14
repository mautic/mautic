/*!
 * froala_editor v2.2.4 (https://www.froala.com/wysiwyg-editor)
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
    fileManagerLoadURL: 'http://i.froala.com/load-files',
    fileManagerLoadMethod: 'get',
    fileManagerLoadParams: {},
    fileManagerPreloader: '',
    fileManagerDeleteURL: '',
    fileManagerDeleteMethod: 'post',
    fileManagerDeleteParams: {},
    fileManagerPageSize: 12,
    fileManagerScrollOffset: 20,
    fileManagerToggleTags: true
  });

  $.FE.PLUGINS.fileManager = function (editor) {
    var $modal;
    var $preloader;
    var $media_files;
    var $scroller;
    var $image_tags;
    var $modal_title;
    var $overlay;
    var images;
    var page;
    var image_count;
    var loaded_images;
    var column_number;

    // Load errors.
    var BAD_LINK = 10;
    var ERROR_DURING_LOAD = 11;
    var MISSING_LOAD_URL_OPTION = 12;
    var LOAD_BAD_RESPONSE = 13;
    var MISSING_IMG_THUMB = 14;
    var MISSING_IMG_URL = 15;

    // Delete errors
    var ERROR_DURING_DELETE = 21;
    var MISSING_DELETE_URL_OPTION = 22;

    // Error Messages
    var error_messages = {};
    error_messages[BAD_LINK] = 'Image cannot be loaded from the passed link.';
    error_messages[ERROR_DURING_LOAD] = 'Error during load images request.';
    error_messages[MISSING_LOAD_URL_OPTION] = 'Missing fileManagerLoadURL option.';
    error_messages[LOAD_BAD_RESPONSE] = 'Parsing load response failed.';
    error_messages[MISSING_IMG_THUMB] = 'Missing image thumb.';
    error_messages[MISSING_IMG_URL] = 'Missing image URL.';
    error_messages[ERROR_DURING_DELETE] = 'Error during delete image request.';
    error_messages[MISSING_DELETE_URL_OPTION] = 'Missing fileManagerDeleteURL option.';

    var $current_image;

    var urlobj;

    /*
     * Show the media manager.
     */
    function show () {
      urlobj = 'test';
      OpenServerBrowser(
        mauticBasePath + '/' + mauticAssetPrefix + 'app/bundles/CoreBundle/Assets/js/libraries/ckeditor/filemanager/index.html?type=Images',
        screen.width * 0.7,
        screen.height * 0.7
      );
    }

    function OpenServerBrowser( url, width, height ) {
      var iLeft = (screen.width - width) / 2 ;
      var iTop = (screen.height - height) / 2 ;
      var sOptions = "toolbar=no,status=no,resizable=yes,dependent=yes" ;
      sOptions += ",width=" + width ;
      sOptions += ",height=" + height ;
      sOptions += ",left=" + iLeft ;
      sOptions += ",top=" + iTop ;
      var oWindow = window.open( url, "BrowseWindow", sOptions ) ;
    }

    function SetUrl( url, width, height, alt ) {
      document.getElementById(urlobj).value = url ;
      oWindow = null;
    }

    /*
     * Hide the media manager.
     */
    function hide () {
      editor.events.enableBlur();
      $modal.hide();
      $overlay.hide();
      editor.$doc.find('body').removeClass('prevent-scroll fr-mobile');
    }

    /*
     * Insert image into the editor.
     */
    function _insertImage (e) {
      // Image to insert.
      var $img = $(e.currentTarget).siblings('img');

      hide();
      editor.image.showProgressBar();

      if (!$current_image) {
        // Make sure we have focus.
        editor.events.focus(true);
        editor.selection.restore();

        var rect = editor.position.getBoundingRect();

        var left = rect.left + rect.width / 2;
        var top = rect.top + rect.height;

        // Show the image insert popup.
        editor.popups.setContainer('image.insert', editor.$box || $('body'));
        editor.popups.show('image.insert', left, top);
      }
      else {
        $current_image.trigger('click');
      }

      // Copy additional image attributes.
      // data-url is set as src therefore not needed anymore.
      // data-tag is only used to sort images by tag in the image manager.
      var img_attributes = {};
      var img_data = $img.data();

      for (var key in img_data) {
        if (img_data.hasOwnProperty(key)) {
          if (key != 'url' && key != 'tag') {
            img_attributes[key] = img_data[key];
          }
        }
      }

      editor.image.insert($img.data('url'), false, img_attributes, $current_image);
    }

    /*
     * Throw image manager errors.
     */
    function _throwError (code, response) {
      // Load images error.
      if (10 <= code && code < 20) {
        // Hide preloader.
        $preloader.hide();
      }

      // Delete image error.
      else if (20 <= code && code < 30) {
        // Remove deleting overlay.
        $('.fr-image-deleting').removeClass('fr-image-deleting');
      }

      // Trigger error event.
      editor.events.trigger('fileManager.error', [{
        code: code,
        message: error_messages[code]
      }, response]);
    }

    function _delayedInit() {
      $preloader = $modal.find('#fr-preloader');
      $media_files = $modal.find('#fr-image-list');
      $scroller = $modal.find('#fr-scroller');
      $image_tags = $modal.find('#fr-modal-tags');
      $modal_title = $image_tags.parent();

      // Set height for title (we need this for show tags transition).
      var title_height = $modal_title.find('.fr-modal-title-line').outerHeight();
      $modal_title.css('height', title_height);
      $scroller.css('margin-top', title_height);

      // Close button.
      editor.events.bindClick($modal, 'i#fr-modal-close', hide);

      // Delete and insert buttons for mobile.
      if (editor.helpers.isMobile()) {
        // Show image buttons on mobile.
        editor.events.bindClick($media_files, 'div.fr-image-container', function (e) {
          $modal.find('.fr-mobile-selected').removeClass('fr-mobile-selected');
          $(e.currentTarget).addClass('fr-mobile-selected');
        });

        // Hide image buttons if we click outside it.
        $modal.on(editor._mousedown, function () {
          $modal.find('.fr-mobile-selected').removeClass('fr-mobile-selected');
        });
      }

      // Insert image.
      editor.events.bindClick($media_files, '.fr-insert-img', _insertImage);

      // Make sure we don't trigger blur.
      $modal.on(editor._mousedown + ' ' + editor._mouseup, function (e) {
        e.stopPropagation();
      });

      // Mouse down on anything.
      $modal.on(editor._mousedown, '*', function () {
        editor.events.disableBlur();
      });
    }

    /*
     * Init media manager.
     */
    function _init () {
      if (!editor.$wp && editor.$el.get(0).tagName != 'IMG') return false;
    }

    return {
      require: ['image'],
      _init: _init,
      show: show,
      hide: hide
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
