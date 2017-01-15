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
    'image.insert': '[_BUTTONS_][_UPLOAD_LAYER_][_BY_URL_LAYER_][_PROGRESS_BAR_]',
    'image.edit': '[_BUTTONS_]',
    'image.alt': '[_BUTTONS_][_ALT_LAYER_]',
    'image.size': '[_BUTTONS_][_SIZE_LAYER_]'
  })

  $.extend($.FE.DEFAULTS, {
    imageInsertButtons: ['imageBack', '|', 'imageUpload', 'imageByURL'],
    imageEditButtons: ['imageReplace', 'imageAlign', 'imageRemove', '|', 'imageLink', 'linkOpen', 'linkEdit', 'linkRemove', '-', 'imageDisplay', 'imageStyle', 'imageAlt', 'imageSize'],
    imageAltButtons: ['imageBack', '|'],
    imageSizeButtons: ['imageBack', '|'],
    imageUploadURL: 'https://i.froala.com/upload',
    imageUploadParam: 'file',
    imageUploadParams: {},
    imageUploadToS3: false,
    imageUploadMethod: 'POST',
    imageMaxSize: 10 * 1024 * 1024,
    imageAllowedTypes: ['jpeg', 'jpg', 'png', 'gif', 'svg+xml'],
    imageResize: true,
    imageResizeWithPercent: false,
    imageRoundPercent: false,
    imageDefaultWidth: 300,
    imageDefaultAlign: 'center',
    imageDefaultDisplay: 'block',
    imageSplitHTML: false,
    imageStyles: {
      'fr-rounded': 'Rounded',
      'fr-bordered': 'Bordered'
    },
    imageMove: true,
    imageMultipleStyles: true,
    imageTextNear: true,
    imagePaste: true,
    imagePasteProcess: false,
    imageMinWidth: 16,
    imageOutputSize: false
  });

  $.FE.PLUGINS.image = function (editor) {
    var $current_image;
    var $image_resizer;
    var $handler;
    var $overlay;
    var mousedown = false;

    var BAD_LINK = 1;
    var MISSING_LINK = 2;
    var ERROR_DURING_UPLOAD = 3;
    var BAD_RESPONSE = 4;
    var MAX_SIZE_EXCEEDED = 5;
    var BAD_FILE_TYPE = 6;
    var NO_CORS_IE = 7;

    var error_messages = {};
    error_messages[BAD_LINK] = 'Image cannot be loaded from the passed link.',
    error_messages[MISSING_LINK] = 'No link in upload response.',
    error_messages[ERROR_DURING_UPLOAD] = 'Error during file upload.',
    error_messages[BAD_RESPONSE] = 'Parsing response failed.',
    error_messages[MAX_SIZE_EXCEEDED] = 'File is too large.',
    error_messages[BAD_FILE_TYPE] = 'Image file type is invalid.',
    error_messages[NO_CORS_IE] = 'Files can be uploaded only to same domain in IE 8 and IE 9.'

    /**
     * Refresh the image insert popup.
     */

    function _refreshInsertPopup () {
      var $popup = editor.popups.get('image.insert');

      var $url_input = $popup.find('.fr-image-by-url-layer input');
      $url_input.val('');

      if ($current_image) {
        $url_input.val($current_image.attr('src'));
      }

      $url_input.trigger('change');
    }

    /**
     * Show the image upload popup.
     */

    function showInsertPopup () {
      var $btn = editor.$tb.find('.fr-command[data-cmd="insertImage"]');

      var $popup = editor.popups.get('image.insert');
      if (!$popup) $popup = _initInsertPopup();

      hideProgressBar();
      if (!$popup.hasClass('fr-active')) {
        editor.popups.refresh('image.insert');
        editor.popups.setContainer('image.insert', editor.$tb);

        if ($btn.is(':visible')) {
          var left = $btn.offset().left + $btn.outerWidth() / 2;
          var top = $btn.offset().top + (editor.opts.toolbarBottom ? 10 : $btn.outerHeight() - 10);
          editor.popups.show('image.insert', left, top, $btn.outerHeight());
        } else {
          editor.position.forSelection($popup);
          editor.popups.show('image.insert');
        }
      }
    }

    /**
     * Show the image edit popup.
     */

    function _showEditPopup () {
      var $popup = editor.popups.get('image.edit');
      if (!$popup) $popup = _initEditPopup();

      if ($popup) {
        editor.popups.setContainer('image.edit', $(editor.opts.scrollableContainer));
        editor.popups.refresh('image.edit');
        var left = $current_image.offset().left + $current_image.outerWidth() / 2;
        var top = $current_image.offset().top + $current_image.outerHeight();

        editor.popups.show('image.edit', left, top, $current_image.outerHeight());
      }
    }

    /**
     * Hide image upload popup.
     */

    function _hideInsertPopup () {
      hideProgressBar();
    }

    /**
     * Convert style to classes.
     */

    function _convertStyleToClasses ($img) {
      if (!$img.hasClass('fr-dii') && !$img.hasClass('fr-dib')) {
        // Set float to none.
        var flt = $img.css('float');
        $img.css('float', 'none');

        // Image has display block.
        if ($img.css('display') == 'block') {
          // Set float to the initial value.
          $img.css('float', flt);

          if (editor.opts.imageEditButtons.indexOf('imageAlign') >= 0) {
            // Margin left is 0.
            // Margin right is auto.
            if (parseInt($img.css('margin-left'), 10) === 0 && ($img.attr('style') || '').indexOf('margin-right: auto') >= 0) {
              $img.addClass('fr-fil');
            }

            // Margin left is auto.
            // Margin right is 0.
            else if (parseInt($img.css('margin-right'), 10) === 0 && ($img.attr('style') || '').indexOf('margin-left: auto') >= 0) {
              $img.addClass('fr-fir');
            }
          }

          $img.addClass('fr-dib');
        }

        // Display inline.
        else {
          // Set float.
          $img.css('float', flt);

          if (editor.opts.imageEditButtons.indexOf('imageAlign') >= 0) {
            // Float left.
            if ($img.css('float') == 'left') {
              $img.addClass('fr-fil');
            }

            // Float right.
            else if ($img.css('float') == 'right') {
              $img.addClass('fr-fir');
            }
          }

          $img.addClass('fr-dii');
        }

        // Reset inline style.
        $img.css('margin', '');
        $img.css('float', '');
        $img.css('display', '');
        $img.css('z-index', '');
        $img.css('position', '');
        $img.css('overflow', '');
        $img.css('vertical-align', '');
      }
    }

    /**
     * Refresh the image list.
     */

    function _refreshImageList () {
      var images = editor.el.tagName == 'IMG' ? [editor.el] : editor.el.querySelectorAll('img');

      for (var i = 0; i < images.length; i++) {
        var $img = $(images[i]);

        if (editor.opts.imageEditButtons.indexOf('imageAlign') >= 0 || editor.opts.imageEditButtons.indexOf('imageDisplay') >= 0) {
          _convertStyleToClasses($img);
        }

        // Do not allow text near image.
        if (!editor.opts.imageTextNear) {
          $img.removeClass('fr-dii').addClass('fr-dib');
        }

        if (editor.opts.iframe) {
          $img.on('load', editor.size.syncIframe);
        }
      }
    }

    /**
     * Keep images in sync when content changed.
     */
    var images;

    function _syncImages () {
      // Get current images.
      var c_images = Array.prototype.slice.call(editor.el.querySelectorAll('img'));

      // Current images src.
      var image_srcs = [];
      var i;
      for (i = 0; i < c_images.length; i++) {
        image_srcs.push(c_images[i].getAttribute('src'));

        $(c_images[i]).toggleClass('fr-draggable', editor.opts.imageMove);
        if (c_images[i].getAttribute('class') === '') c_images[i].removeAttribute('class');
        if (c_images[i].getAttribute('style') === '') c_images[i].removeAttribute('style');
      }

      // Loop previous images and check their src.
      if (images) {
        for (i = 0; i < images.length; i++) {
          if (image_srcs.indexOf(images[i].getAttribute('src')) < 0) {
            editor.events.trigger('image.removed', [$(images[i])]);
          }
        }
      }

      // Current images are the old ones.
      images = c_images;
    }

    /**
     * Reposition resizer.
     */

    function _repositionResizer () {
      if (!$image_resizer) _initImageResizer();

      var $container = editor.$wp || $(editor.opts.scrollableContainer);

      $container.append($image_resizer);
      $image_resizer.data('instance', editor);

      var wrap_correction_top = $container.scrollTop() - (($container.css('position') != 'static' ? $container.offset().top : 0));
      var wrap_correction_left = $container.scrollLeft() - (($container.css('position') != 'static' ? $container.offset().left : 0));

      wrap_correction_left -= editor.helpers.getPX($container.css('border-left-width'));
      wrap_correction_top -= editor.helpers.getPX($container.css('border-top-width'));

      if (editor.$el.is('img')) {
        wrap_correction_top = 0;
        wrap_correction_left = 0;
      }

      $image_resizer
        .css('top', (editor.opts.iframe ? $current_image.offset().top : $current_image.offset().top + wrap_correction_top) - 1)
        .css('left', (editor.opts.iframe ? $current_image.offset().left : $current_image.offset().left + wrap_correction_left) - 1)
        .css('width', $current_image.get(0).getBoundingClientRect().width)
        .css('height', $current_image.get(0).getBoundingClientRect().height)
        .addClass('fr-active');
    }

    /**
     * Create resize handler.
     */

    function _getHandler (pos) {
      return '<div class="fr-handler fr-h' + pos + '"></div>';
    }

    /**
     * Mouse down to start resize.
     */
    function _handlerMousedown (e) {
      // Check if resizer belongs to current instance.
      if (!editor.core.sameInstance($image_resizer)) return true;

      e.preventDefault();
      e.stopPropagation();

      if (editor.$el.find('img.fr-error').left) return false;

      if (!editor.undo.canDo()) editor.undo.saveStep();

      $handler = $(this);
      $handler.data('start-x', e.pageX || e.originalEvent.touches[0].pageX);
      $handler.data('start-width', $current_image.width());
      $handler.data('start-height', $current_image.height());

      // Set current width.
      var width = $current_image.width();
      if (editor.opts.imageResizeWithPercent) {
        var p_node = $current_image.parentsUntil(editor.$el, editor.html.blockTagsQuery()).get(0) || editor.el;

        $current_image.css('width', (width / $(p_node).outerWidth() * 100).toFixed(2) + '%');
      } else {
        $current_image.css('width', width);
      }

      $overlay.show();

      editor.popups.hideAll();

      _unmarkExit();
    }

    /**
     * Do resize.
     */

    function _handlerMousemove (e) {
      // Check if resizer belongs to current instance.
      if (!editor.core.sameInstance($image_resizer)) return true;

      if ($handler && $current_image) {
        e.preventDefault()

        if (editor.$el.find('img.fr-error').left) return false;

        var c_x = e.pageX || (e.originalEvent.touches ? e.originalEvent.touches[0].pageX : null);

        if (!c_x) {
          return false;
        }

        var s_x = $handler.data('start-x');

        var diff_x = c_x - s_x;

        var width = $handler.data('start-width');
        if ($handler.hasClass('fr-hnw') || $handler.hasClass('fr-hsw')) {
          diff_x = 0 - diff_x;
        }

        if (editor.opts.imageResizeWithPercent) {
          var p_node = $current_image.parentsUntil(editor.$el, editor.html.blockTagsQuery()).get(0) || editor.el;

          width = ((width + diff_x) / $(p_node).outerWidth() * 100).toFixed(2);
          if (editor.opts.imageRoundPercent) width = Math.round(width);

          $current_image.css('width', width + '%');
          $current_image.css('height', '').removeAttr('height');
        } else {
          if (width + diff_x >= editor.opts.imageMinWidth) {
            $current_image.css('width', width + diff_x);
          }

          $current_image.css('height', $handler.data('start-height') * $current_image.width() / $handler.data('start-width'));
        }

        _repositionResizer();

        editor.events.trigger('image.resize', [get()]);
      }
    }

    /**
     * Stop resize.
     */

    function _handlerMouseup (e) {
      // Check if resizer belongs to current instance.
      if (!editor.core.sameInstance($image_resizer)) return true;

      if ($handler && $current_image) {
        if (e) e.stopPropagation();

        if (editor.$el.find('img.fr-error').left) return false;

        $handler = null;
        $overlay.hide();
        _repositionResizer();
        _showEditPopup();

        editor.undo.saveStep();

        editor.events.trigger('image.resizeEnd', [get()]);
      }
    }

    /**
     * Throw an image error.
     */

    function _throwError (code, response) {
      editor.edit.on();
      if ($current_image) $current_image.addClass('fr-error');
      _showErrorMessage(editor.language.translate('Something went wrong. Please try again.'));

      editor.events.trigger('image.error', [{
          code: code,
          message: error_messages[code]
        },
        response
      ]);
    }

    /**
     * Init the image edit popup.
     */

    function _initEditPopup (delayed) {
      if (delayed) {
        if (editor.$wp) {
          editor.events.$on(editor.$wp, 'scroll', function () {
            if ($current_image && editor.popups.isVisible('image.edit')) {
              _showEditPopup();
            }
          });
        }

        return true;
      }

      // Image buttons.
      var image_buttons = '';
      if (editor.opts.imageEditButtons.length > 0) {
        image_buttons += '<div class="fr-buttons">';
        image_buttons += editor.button.buildList(editor.opts.imageEditButtons);
        image_buttons += '</div>';

        var template = {
          buttons: image_buttons
        };

        var $popup = editor.popups.create('image.edit', template);

        return $popup;
      }

      return false;
    }

    /**
     * Show progress bar.
     */

    function showProgressBar (no_message) {
      var $popup = editor.popups.get('image.insert');
      if (!$popup) $popup = _initInsertPopup();

      $popup.find('.fr-layer.fr-active').removeClass('fr-active').addClass('fr-pactive');
      $popup.find('.fr-image-progress-bar-layer').addClass('fr-active');
      $popup.find('.fr-buttons').hide();

      if ($current_image) {
        editor.popups.setContainer('image.insert', $(editor.opts.scrollableContainer));
        var left = $current_image.offset().left + $current_image.width() / 2;
        var top = $current_image.offset().top + $current_image.height();

        editor.popups.show('image.insert', left, top, $current_image.outerHeight());
      }

      if (typeof no_message == 'undefined') {
        _setProgressMessage('Uploading', 0);
      }
    }

    /**
     * Hide progress bar.
     */
    function hideProgressBar (dismiss) {
      var $popup = editor.popups.get('image.insert');

      if ($popup) {
        $popup.find('.fr-layer.fr-pactive').addClass('fr-active').removeClass('fr-pactive');
        $popup.find('.fr-image-progress-bar-layer').removeClass('fr-active');
        $popup.find('.fr-buttons').show();

        // Dismiss error message.
        if (dismiss || editor.$el.find('img.fr-error').length) {
          editor.events.focus();

          if (editor.$el.find('img.fr-error').length) {
            editor.$el.find('img.fr-error').remove();
            editor.undo.saveStep();
            editor.undo.run();
            editor.undo.dropRedo();
          }
          if (!editor.$wp && $current_image) {
            var $img = $current_image;
            _exitEdit(true);
            editor.selection.setAfter($img.get(0));
            editor.selection.restore();
          }
          editor.popups.hide('image.insert');
        }
      }
    }

    /**
     * Set a progress message.
     */

    function _setProgressMessage (message, progress) {
      var $popup = editor.popups.get('image.insert');

      if ($popup) {
        var $layer = $popup.find('.fr-image-progress-bar-layer');
        $layer.find('h3').text(message + (progress ? ' ' + progress + '%' : ''));

        $layer.removeClass('fr-error');

        if (progress) {
          $layer.find('div').removeClass('fr-indeterminate');
          $layer.find('div > span').css('width', progress + '%');
        } else {
          $layer.find('div').addClass('fr-indeterminate');
        }
      }
    }

    /**
     * Show error message to the user.
     */

    function _showErrorMessage (message) {
      showProgressBar();
      var $popup = editor.popups.get('image.insert');
      var $layer = $popup.find('.fr-image-progress-bar-layer');
      $layer.addClass('fr-error');
      var $message_header = $layer.find('h3');
      $message_header.text(message);
      editor.events.disableBlur();
      $message_header.focus();
    }

    /**
     * Insert image using URL callback.
     */

    function insertByURL () {
      var $popup = editor.popups.get('image.insert');
      var $input = $popup.find('.fr-image-by-url-layer input');

      if ($input.val().length > 0) {
        showProgressBar();
        _setProgressMessage('Loading image');
        insert($input.val(), true, [], $current_image);
        $input.val('');
        $input.blur();
      }
    }

    function _editImg ($img) {
      _edit.call($img.get(0));
    }

    function _loadedCallback () {
      var $img = $(this);

      editor.popups.hide('image.insert');

      $img.removeClass('fr-uploading');

      // Select the image.
      if ($img.next().is('br')) {
        $img.next().remove();
      }

      _editImg($img);

      editor.events.trigger('image.loaded', [$img]);
    }

    /**
     * Insert image into the editor.
     */

    function insert (link, sanitize, data, $existing_img, response) {
      editor.edit.off();
      _setProgressMessage('Loading image');

      if (sanitize) link = editor.helpers.sanitizeURL(link);

      var image = new Image();
      image.onload = function () {
        var $img;
        var attr;

        if ($existing_img) {
          if (!editor.undo.canDo() && !$existing_img.hasClass('fr-uploading')) editor.undo.saveStep();

          var old_src = $existing_img.data('fr-old-src');

          if (editor.$wp) {
            // Clone existing image.
            $img = $existing_img.clone().removeData('fr-old-src').removeClass('fr-uploading');

            // Remove load event.
            $img.off('load');

            // Set new SRC.
            if (old_src) $existing_img.attr('src', old_src);

            // Replace existing image with its clone.
            $existing_img.replaceWith($img);
          } else {
            $img = $existing_img;
          }

          // Remove old data.
          var atts = $img.get(0).attributes;
          for (var i = 0; i < atts.length; i++) {
            var att = atts[i];
            if (att.nodeName.indexOf('data-') === 0) {
              $img.removeAttr(att.nodeName);
            }
          }

          // Set new data.
          if (typeof data != 'undefined') {
            for (attr in data) {
              if (data.hasOwnProperty(attr)) {
                if (attr != 'link') {
                  $img.attr('data-' + attr, data[attr]);
                }
              }
            }
          }

          $img.on('load', _loadedCallback);
          $img.attr('src', link);
          editor.edit.on();
          _syncImages();
          editor.undo.saveStep();

          // Cursor will not appear if we don't make blur.
          editor.$el.blur();

          editor.events.trigger(old_src ? 'image.replaced' : 'image.inserted', [$img, response]);
        } else {
          $img = _addImage(link, data, _loadedCallback);
          _syncImages();
          editor.undo.saveStep();
          editor.events.trigger('image.inserted', [$img, response]);
        }
      }

      image.onerror = function () {
        _throwError(BAD_LINK);
      }

      showProgressBar('Loading image');

      image.src = link;
    }

    /**
     * Parse image response.
     */

    function _parseResponse (response) {
      try {
        if (editor.events.trigger('image.uploaded', [response], true) === false) {
          editor.edit.on();
          return false;
        }

        var resp = $.parseJSON(response);
        if (resp.link) {
          return resp;
        } else {
          // No link in upload request.
          _throwError(MISSING_LINK, response);
          return false;
        }
      } catch (ex) {
        // Bad response.
        _throwError(BAD_RESPONSE, response);
        return false;
      }
    }

    /**
     * Parse image response.
     */

    function _parseXMLResponse (response) {
      try {
        var link = $(response).find('Location').text();
        var key = $(response).find('Key').text();

        if (editor.events.trigger('image.uploadedToS3', [link, key, response], true) === false) {
          editor.edit.on();
          return false;
        }

        return link;
      } catch (ex) {
        // Bad response.
        _throwError(BAD_RESPONSE, response);
        return false;
      }
    }

    /**
     * Image was uploaded to the server and we have a response.
     */

    function _imageUploaded ($img) {
      _setProgressMessage('Loading image');

      var status = this.status;
      var response = this.response;
      var responseXML = this.responseXML;
      var responseText = this.responseText;

      try {
        if (editor.opts.imageUploadToS3) {
          if (status == 201) {
            var link = _parseXMLResponse(responseXML);
            if (link) {
              insert(link, false, [], $img, response || responseXML);
            }
          } else {
            _throwError(BAD_RESPONSE, response || responseXML);
          }
        } else {
          if (status >= 200 && status < 300) {
            var resp = _parseResponse(responseText);
            if (resp) {
              insert(resp.link, false, resp, $img, response || responseText);
            }
          } else {
            _throwError(ERROR_DURING_UPLOAD, response || responseText);
          }
        }
      } catch (ex) {
        // Bad response.
        _throwError(BAD_RESPONSE, response || responseText);
      }
    }

    /**
     * Image upload error.
     */

    function _imageUploadError () {
      _throwError(BAD_RESPONSE, this.response || this.responseText || this.responseXML);
    }

    /**
     * Image upload progress.
     */

    function _imageUploadProgress (e) {
      if (e.lengthComputable) {
        var complete = (e.loaded / e.total * 100 | 0);
        _setProgressMessage('Uploading', complete);
      }
    }

    function _addImage (link, data, loadCallback) {
      // Build image data string.
      var data_str = '';
      var attr;
      if (data && typeof data != 'undefined') {
        for (attr in data) {
          if (data.hasOwnProperty(attr)) {
            if (attr != 'link') {
              data_str += ' data-' + attr + '="' + data[attr] + '"';
            }
          }
        }
      }

      var width = editor.opts.imageDefaultWidth;
      if (width && width != 'auto') {
        width = width + (editor.opts.imageResizeWithPercent ? '%' : 'px');
      }

      // Create image object and set the load event.
      var $img = $('<img class="' + (editor.opts.imageDefaultDisplay ? 'fr-di' + editor.opts.imageDefaultDisplay[0] : '') + (editor.opts.imageDefaultAlign ? (editor.opts.imageDefaultAlign != 'center' ? ' fr-fi' + editor.opts.imageDefaultAlign[0] : '') : '') + '" src="' + link + '"' + data_str + (width ? ' style="width: ' + width + ';"' : '') + '>');

      $img.on('load', loadCallback);

      // Make sure we have focus.
      // Call the event.
      editor.edit.on();
      editor.events.focus(true);
      editor.selection.restore();

      editor.undo.saveStep();

      // Insert marker and then replace it with the image.
      if (editor.opts.imageSplitHTML) {
        editor.markers.split();
      } else {
        editor.markers.insert();
      }

      var $marker = editor.$el.find('.fr-marker');
      $marker.replaceWith($img);

      editor.html.wrap();
      editor.selection.clear();

      return $img;
    }

    /**
     * Image upload aborted.
     */
    function _imageUploadAborted () {
      editor.edit.on();
      hideProgressBar(true);
    }

    /**
     * Start the uploading process.
     */
    function _startUpload (xhr, form_data, image) {
      function _sendRequest () {
        var $img = $(this);

        $img.off('load');

        $img.addClass('fr-uploading');

        if ($img.next().is('br')) {
          $img.next().remove();
        }

        editor.placeholder.refresh();

        // Select the image.
        if (!$img.is($current_image)) _editImg($img);

        _repositionResizer();
        showProgressBar();

        editor.edit.off();

        // Set upload events.
        xhr.onload = function () {
          _imageUploaded.call(xhr, $img);
        };
        xhr.onerror = _imageUploadError;
        xhr.upload.onprogress = _imageUploadProgress;
        xhr.onabort = _imageUploadAborted;

        // Set abort event.
        $img.off('abortUpload').on('abortUpload', function () {
          if (xhr.readyState != 4) {
            xhr.abort();
          }
        });

        // Send data.
        xhr.send(form_data);
      }

      var reader = new FileReader();
      var $img;
      reader.addEventListener('load', function () {
        var link = reader.result;

        if (reader.result.indexOf('svg+xml') < 0) {
          // Convert image to local blob.
          var binary = atob(reader.result.split(',')[1]);
          var array = [];
          for (var i = 0; i < binary.length; i++) {
            array.push(binary.charCodeAt(i));
          }

          // Get local image link.
          link = window.URL.createObjectURL(new Blob([new Uint8Array(array)], {
            type: 'image/jpeg'
          }));
        }

        // No image.
        if (!$current_image) {
          $img = _addImage(link, null, _sendRequest);
        } else {
          $current_image.on('load', _sendRequest);
          editor.edit.on();
          editor.undo.saveStep();
          $current_image.data('fr-old-src', $current_image.attr('src'));
          $current_image.attr('src', link);
        }
      }, false);

      reader.readAsDataURL(image);
    }

    /**
     * Do image upload.
     */

    function upload (images) {
      // Make sure we have what to upload.
      if (typeof images != 'undefined' && images.length > 0) {
        // Check if we should cancel the image upload.
        if (editor.events.trigger('image.beforeUpload', [images]) === false) {
          return false;
        }

        var image = images[0];

        // Check image max size.
        if (image.size > editor.opts.imageMaxSize) {
          _throwError(MAX_SIZE_EXCEEDED);
          return false;
        }

        // Check image types.
        if (editor.opts.imageAllowedTypes.indexOf(image.type.replace(/image\//g, '')) < 0) {
          _throwError(BAD_FILE_TYPE);
          return false;
        }

        // Create form Data.
        var form_data;
        if (editor.drag_support.formdata) {
          form_data = editor.drag_support.formdata ? new FormData() : null;
        }

        // Prepare form data for request.
        if (form_data) {
          var key;

          // Upload to S3.
          if (editor.opts.imageUploadToS3 !== false) {
            form_data.append('key', editor.opts.imageUploadToS3.keyStart + (new Date()).getTime() + '-' + (image.name || 'untitled'));
            form_data.append('success_action_status', '201');
            form_data.append('X-Requested-With', 'xhr');
            form_data.append('Content-Type', image.type);

            for (key in editor.opts.imageUploadToS3.params) {
              if (editor.opts.imageUploadToS3.params.hasOwnProperty(key)) {
                form_data.append(key, editor.opts.imageUploadToS3.params[key]);
              }
            }
          }

          // Add upload params.
          for (key in editor.opts.imageUploadParams) {
            if (editor.opts.imageUploadParams.hasOwnProperty(key)) {
              form_data.append(key, editor.opts.imageUploadParams[key]);
            }
          }

          // Set the image in the request.
          form_data.append(editor.opts.imageUploadParam, image);

          // Create XHR request.
          var url = editor.opts.imageUploadURL;
          if (editor.opts.imageUploadToS3) {
            if (editor.opts.imageUploadToS3.uploadURL) {
              url = editor.opts.imageUploadToS3.uploadURL;
            }
            else {
              url = 'https://' + editor.opts.imageUploadToS3.region + '.amazonaws.com/' + editor.opts.imageUploadToS3.bucket;
            }
          }
          var xhr = editor.core.getXHR(url, editor.opts.imageUploadMethod);

          _startUpload(xhr, form_data, image);
        }
      }
    }

    /**
     * Image drop inside the upload zone.
     */

    function _bindInsertEvents ($popup) {
      // Drag over the dropable area.
      editor.events.$on($popup, 'dragover dragenter', '.fr-image-upload-layer', function () {
        $(this).addClass('fr-drop');
        return false;
      });

      // Drag end.
      editor.events.$on($popup, 'dragleave dragend', '.fr-image-upload-layer', function () {
        $(this).removeClass('fr-drop');
        return false;
      });

      // Drop.
      editor.events.$on($popup, 'drop', '.fr-image-upload-layer', function (e) {
        e.preventDefault();
        e.stopPropagation();

        $(this).removeClass('fr-drop');

        var dt = e.originalEvent.dataTransfer;
        if (dt && dt.files) {
          var inst = $popup.data('instance') || editor;
          inst.events.disableBlur();
          inst.image.upload(dt.files);
          inst.events.enableBlur();
        }
      });

      editor.events.$on($popup, 'change', '.fr-image-upload-layer input[type="file"]', function () {
        if (this.files) {
          var inst = $popup.data('instance') || editor;
          inst.events.disableBlur();
          $popup.find('input:focus').blur();
          inst.events.enableBlur();
          inst.image.upload(this.files);
        }
        // Else IE 9 case.

        // Chrome fix.
        $(this).val('');
      });
    }

    function _drop (e) {
      // Check if we are dropping files.
      var dt = e.originalEvent.dataTransfer;
      if (dt && dt.files && dt.files.length) {
        var img = dt.files[0];
        if (img && img.type) {
          // Dropped file is an image that we allow.
          if (editor.opts.imageAllowedTypes.indexOf(img.type.replace(/image\//g, '')) >= 0) {
            editor.markers.remove();
            editor.markers.insertAtPoint(e.originalEvent);
            editor.$el.find('.fr-marker').replaceWith($.FE.MARKERS);

            // Hide popups.
            editor.popups.hideAll();

            // Show the image insert popup.
            var $popup = editor.popups.get('image.insert');
            if (!$popup) $popup = _initInsertPopup();

            editor.popups.setContainer('image.insert', $(editor.opts.scrollableContainer));
            editor.popups.show('image.insert', e.originalEvent.pageX, e.originalEvent.pageY);
            showProgressBar();

            // Upload images.
            upload(dt.files);

            // Cancel anything else.
            e.preventDefault();
            e.stopPropagation();

            return false;
          }
        }
      }
    }

    function _placeCursor () {
      var t;
      var p_node;
      var r = editor.selection.ranges(0);
      if (r.collapsed && r.startContainer.nodeType == Node.ELEMENT_NODE) {
        // Click after image.
        if (r.startContainer.childNodes.length == r.startOffset) {
          t = r.startContainer.childNodes[r.startOffset - 1];
          if (t && t.tagName == 'IMG' && $(t).css('display') == 'block') {
            // Check if image is last node.
            p_node = editor.node.blockParent(t);
            if (p_node && editor.html.defaultTag()) {
              if (!p_node.nextSibling) {
                if (['TD', 'TH'].indexOf(p_node.tagName) < 0) {
                  $(p_node).after('<' + editor.html.defaultTag() + '><br>' + $.FE.MARKERS + '</' + editor.html.defaultTag() + '>');
                }
                else {
                  $(t).after('<br>' + $.FE.MARKERS);
                }
                editor.selection.restore();
              }
            }
            else if (!p_node) {
              $(t).after('<br>' + $.FE.MARKERS);
              editor.selection.restore();
            }
          }
        }
        else if (r.startOffset === 0 && r.startContainer.childNodes.length > r.startOffset) {
          t = r.startContainer.childNodes[r.startOffset];
          if (t && t.tagName == 'IMG' && $(t).css('display') == 'block') {
            // Check if image is last node.
            p_node = editor.node.blockParent(t);
            if (p_node && editor.html.defaultTag()) {
              if (!p_node.previousSibling) {
                if (['TD', 'TH'].indexOf(p_node.tagName) < 0) {
                  $(p_node).before('<' + editor.html.defaultTag() + '><br>' + $.FE.MARKERS + '</' + editor.html.defaultTag() + '>');
                }
                else {
                  $(t).before('<br>' + $.FE.MARKERS);
                }
                editor.selection.restore();
              }
            }
            else if (!p_node) {
              $(t).before($.FE.MARKERS + '<br>');
              editor.selection.restore();
            }
          }
        }
      }
    }

    function _initEvents () {
      // Mouse down on image. It might start move.
      editor.events.$on(editor.$el, editor._mousedown, editor.el.tagName == 'IMG' ? null : 'img:not([contenteditable="false"])', function (e) {
        if ($(this).parents('[contenteditable]:not(.fr-element):not(body):first').attr('contenteditable') == 'false') return true;

        if (!editor.helpers.isMobile()) editor.selection.clear();

        mousedown = true;

        if (editor.popups.areVisible()) editor.events.disableBlur();

        // Prevent the image resizing.
        if (editor.browser.msie) {
          editor.events.disableBlur();
          editor.$el.attr('contenteditable', false);
        }

        if (!editor.draggable) e.preventDefault();

        e.stopPropagation();
      });

      // Mouse up on an image prevent move.
      editor.events.$on(editor.$el, editor._mouseup, editor.el.tagName == 'IMG' ? null : 'img:not([contenteditable="false"])', function (e) {
        if ($(this).parents('[contenteditable]:not(.fr-element):not(body):first').attr('contenteditable') == 'false') return true;

        if (mousedown) {
          mousedown = false;

          // Remove moving class.
          e.stopPropagation();

          if (editor.browser.msie) {
            editor.$el.attr('contenteditable', true);
            editor.events.enableBlur();
          }
        }
      });

      // Show image popup when it was selected.
      editor.events.on('keyup', function (e) {
        if (e.shiftKey && editor.selection.text().replace(/\n/g, '') === '') {
          var s_el = editor.selection.element();
          var e_el = editor.selection.endElement();
          if (s_el && s_el.tagName == 'IMG') {
            _editImg($(s_el));
          }
          else if (e_el && e_el.tagName == 'IMG') {
            _editImg($(e_el));
          }
        }
      }, true);

      // Drop inside the editor.
      editor.events.on('drop', _drop);

      editor.events.on('mousedown window.mousedown', _markExit);
      editor.events.on('window.touchmove', _unmarkExit);

      editor.events.on('mouseup window.mouseup', function () {
        if ($current_image) {
          _exitEdit();
          return false;
        }
      });
      editor.events.on('commands.mousedown', function ($btn) {
        if ($btn.parents('.fr-toolbar').length > 0) {
          _exitEdit();
        }
      });

      editor.events.on('mouseup', _placeCursor);

      editor.events.on('blur image.hideResizer commands.undo commands.redo element.dropped', function () {
        mousedown = false;
        _exitEdit(true);
      });
    }

    /**
     * Init the image upload popup.
     */

    function _initInsertPopup (delayed) {
      if (delayed) {
        editor.popups.onRefresh('image.insert', _refreshInsertPopup);
        editor.popups.onHide('image.insert', _hideInsertPopup);

        return true;
      }

      var active;

      // Image buttons.
      var image_buttons = '';
      if (editor.opts.imageInsertButtons.length > 1) {
        image_buttons = '<div class="fr-buttons">' + editor.button.buildList(editor.opts.imageInsertButtons) + '</div>';
      }

      var uploadIndex = editor.opts.imageInsertButtons.indexOf('imageUpload');
      var urlIndex = editor.opts.imageInsertButtons.indexOf('imageByURL');

      // Image upload layer.
      var upload_layer = '';
      if (uploadIndex >= 0) {
        active = ' fr-active';
        if (urlIndex >= 0 && uploadIndex > urlIndex) {
          active = '';
        }

        upload_layer = '<div class="fr-image-upload-layer' + active + ' fr-layer" id="fr-image-upload-layer-' + editor.id + '"><strong>' + editor.language.translate('Drop image') + '</strong><br>(' + editor.language.translate('or click') + ')<div class="fr-form"><input type="file" accept="image/' + editor.opts.imageAllowedTypes.join(', image/').toLowerCase() + '" tabIndex="-1" aria-labelledby="fr-image-upload-layer-' + editor.id + '" role="button"></div></div>'
      }

      // Image by url layer.
      var by_url_layer = '';
      if (urlIndex >= 0) {
        active = ' fr-active';
        if (uploadIndex >= 0 && urlIndex > uploadIndex) {
          active = '';
        }

        by_url_layer = '<div class="fr-image-by-url-layer' + active + ' fr-layer" id="fr-image-by-url-layer-' + editor.id + '"><div class="fr-input-line"><input id="fr-image-by-url-layer-text-' + editor.id + '" type="text" placeholder="http://" tabIndex="1" aria-required="true"></div><div class="fr-action-buttons"><button type="button" class="fr-command fr-submit" data-cmd="imageInsertByURL" tabIndex="2" role="button">' + editor.language.translate('Insert') + '</button></div></div>'
      }

      // Progress bar.
      var progress_bar_layer = '<div class="fr-image-progress-bar-layer fr-layer"><h3 tabIndex="-1" class="fr-message">Uploading</h3><div class="fr-loader"><span class="fr-progress"></span></div><div class="fr-action-buttons"><button type="button" class="fr-command fr-dismiss" data-cmd="imageDismissError" tabIndex="2" role="button">OK</button></div></div>';

      var template = {
        buttons: image_buttons,
        upload_layer: upload_layer,
        by_url_layer: by_url_layer,
        progress_bar: progress_bar_layer
      }

      // Set the template in the popup.
      var $popup = editor.popups.create('image.insert', template);

      if (editor.$wp) {
        editor.events.$on(editor.$wp, 'scroll', function () {
          if ($current_image && editor.popups.isVisible('image.insert')) {
            replace();
          }
        });
      }

      _bindInsertEvents($popup);

      return $popup;
    }

    /**
     * Refresh the ALT popup.
     */

    function _refreshAltPopup () {
      if ($current_image) {
        var $popup = editor.popups.get('image.alt');
        $popup.find('input').val($current_image.attr('alt') || '').trigger('change');
      }
    }

    /**
     * Show the ALT popup.
     */

    function showAltPopup () {
      var $popup = editor.popups.get('image.alt');
      if (!$popup) $popup = _initAltPopup();

      hideProgressBar();
      editor.popups.refresh('image.alt');
      editor.popups.setContainer('image.alt', $(editor.opts.scrollableContainer));
      var left = $current_image.offset().left + $current_image.width() / 2;
      var top = $current_image.offset().top + $current_image.height();

      editor.popups.show('image.alt', left, top, $current_image.outerHeight());
    }

    /**
     * Init the image upload popup.
     */

    function _initAltPopup (delayed) {
      if (delayed) {
        editor.popups.onRefresh('image.alt', _refreshAltPopup);
        return true;
      }

      // Image buttons.
      var image_buttons = '';
      image_buttons = '<div class="fr-buttons">' + editor.button.buildList(editor.opts.imageAltButtons) + '</div>';

      // Image by url layer.
      var alt_layer = '';
      alt_layer = '<div class="fr-image-alt-layer fr-layer fr-active" id="fr-image-alt-layer-' + editor.id + '"><div class="fr-input-line"><input id="fr-image-alt-layer-text-' + editor.id + '" type="text" placeholder="' + editor.language.translate('Alternate Text') + '" tabIndex="1"></div><div class="fr-action-buttons"><button type="button" class="fr-command fr-submit" data-cmd="imageSetAlt" tabIndex="2" role="button">' + editor.language.translate('Update') + '</button></div></div>'

      var template = {
        buttons: image_buttons,
        alt_layer: alt_layer
      }

      // Set the template in the popup.
      var $popup = editor.popups.create('image.alt', template);

      if (editor.$wp) {
        editor.events.$on(editor.$wp, 'scroll.image-alt', function () {
          if ($current_image && editor.popups.isVisible('image.alt')) {
            showAltPopup();
          }
        });
      }

      return $popup;
    }

    /**
     * Set ALT based on the values from the popup.
     */

    function setAlt (alt) {
      if ($current_image) {
        var $popup = editor.popups.get('image.alt');
        $current_image.attr('alt', alt || $popup.find('input').val() || '');
        $popup.find('input:focus').blur();
        _editImg($current_image);
      }
    }

    /**
     * Refresh the size popup.
     */

    function _refreshSizePopup () {
      if ($current_image) {
        var $popup = editor.popups.get('image.size');
        $popup.find('input[name="width"]').val($current_image.get(0).style.width).trigger('change');
        $popup.find('input[name="height"]').val($current_image.get(0).style.height).trigger('change');
      }
    }

    /**
     * Show the size popup.
     */

    function showSizePopup () {
      var $popup = editor.popups.get('image.size');
      if (!$popup) $popup = _initSizePopup();

      hideProgressBar();
      editor.popups.refresh('image.size');
      editor.popups.setContainer('image.size', $(editor.opts.scrollableContainer));
      var left = $current_image.offset().left + $current_image.width() / 2;
      var top = $current_image.offset().top + $current_image.height();

      editor.popups.show('image.size', left, top, $current_image.outerHeight());
    }

    /**
     * Init the image upload popup.
     */

    function _initSizePopup (delayed) {
      if (delayed) {
        editor.popups.onRefresh('image.size', _refreshSizePopup);

        return true;
      }

      // Image buttons.
      var image_buttons = '';
      image_buttons = '<div class="fr-buttons">' + editor.button.buildList(editor.opts.imageSizeButtons) + '</div>';

      // Size layer.
      var size_layer = '';
      size_layer = '<div class="fr-image-size-layer fr-layer fr-active" id="fr-image-size-layer-' + editor.id + '"><div class="fr-image-group"><div class="fr-input-line"><input id="fr-image-size-layer-width-' + editor.id + '" type="text" name="width" placeholder="' + editor.language.translate('Width') + '" tabIndex="1"></div><div class="fr-input-line"><input id="fr-image-size-layer-height' + editor.id + '" type="text" name="height" placeholder="' + editor.language.translate('Height') + '" tabIndex="1"></div></div><div class="fr-action-buttons"><button type="button" class="fr-command fr-submit" data-cmd="imageSetSize" tabIndex="2" role="button">' + editor.language.translate('Update') + '</button></div></div>'

      var template = {
        buttons: image_buttons,
        size_layer: size_layer
      };

      // Set the template in the popup.
      var $popup = editor.popups.create('image.size', template);

      if (editor.$wp) {
        editor.events.$on(editor.$wp, 'scroll.image-size', function () {
          if ($current_image && editor.popups.isVisible('image.size')) {
            showSizePopup();
          }
        });
      }

      return $popup;
    }

    /**
     * Set size based on the current image size.
     */

    function setSize (width, height) {
      if ($current_image) {
        var $popup = editor.popups.get('image.size');
        $current_image.css('width', width || $popup.find('input[name="width"]').val());
        $current_image.css('height', height || $popup.find('input[name="height"]').val());

        $popup.find('input:focus').blur();
        _editImg($current_image);
      }
    }

    /**
     * Show the image upload layer.
     */

    function showLayer (name) {
      var $popup = editor.popups.get('image.insert');

      var left;
      var top;

      // Click on the button from the toolbar without image selected.
      if (!$current_image && !editor.opts.toolbarInline) {
        var $btn = editor.$tb.find('.fr-command[data-cmd="insertImage"]');
        left = $btn.offset().left + $btn.outerWidth() / 2;
        top = $btn.offset().top + (editor.opts.toolbarBottom ? 10 : $btn.outerHeight() - 10);
      }

      // Image is selected.
      else if ($current_image) {
        // Set the top to the bottom of the image.
        top = $current_image.offset().top + $current_image.outerHeight();
      }

      // Image is selected and we are in inline mode.
      if (!$current_image && editor.opts.toolbarInline) {
        // Set top to the popup top.
        top = $popup.offset().top - editor.helpers.getPX($popup.css('margin-top'));

        // If the popup is above apply height correction.
        if ($popup.hasClass('fr-above')) {
          top += $popup.outerHeight();
        }
      }

      // Show the new layer.
      $popup.find('.fr-layer').removeClass('fr-active');
      $popup.find('.fr-' + name + '-layer').addClass('fr-active');

      editor.popups.show('image.insert', left, top, ($current_image ? $current_image.outerHeight() : 0));
      editor.accessibility.focusPopup($popup);
    }

    /**
     * Refresh the upload image button.
     */

    function refreshUploadButton ($btn) {
      var $popup = editor.popups.get('image.insert');
      if ($popup.find('.fr-image-upload-layer').hasClass('fr-active')) {
        $btn.addClass('fr-active').attr('aria-pressed', true);
      }
    }

    /**
     * Refresh the insert by url button.
     */

    function refreshByURLButton ($btn) {
      var $popup = editor.popups.get('image.insert');
      if ($popup.find('.fr-image-by-url-layer').hasClass('fr-active')) {
        $btn.addClass('fr-active').attr('aria-pressed', true);
      }
    }

    function _resizeImage (e, initPageX, direction, step) {
      e.pageX = initPageX;
      _handlerMousedown.call(this, e);
      e.pageX = e.pageX + direction * Math.floor(Math.pow(1.1, step));
      _handlerMousemove.call(this, e);
      _handlerMouseup.call(this, e);

      return step++;
    }

    /**
     * Init image resizer.
     */
    function _initImageResizer () {
      var doc;

      // No shared image resizer.
      if (!editor.shared.$image_resizer) {
        // Create shared image resizer.
        editor.shared.$image_resizer = $('<div class="fr-image-resizer"></div>');
        $image_resizer = editor.shared.$image_resizer;

        // Bind mousedown event shared.
        editor.events.$on($image_resizer, 'mousedown', function (e) {
          e.stopPropagation();
        }, true);

        // Image resize is enabled.
        if (editor.opts.imageResize) {
          $image_resizer.append(_getHandler('nw') + _getHandler('ne') + _getHandler('sw') + _getHandler('se'));

          // Add image resizer overlay and set it.
          editor.shared.$img_overlay = $('<div class="fr-image-overlay"></div>');
          $overlay = editor.shared.$img_overlay;
          doc = $image_resizer.get(0).ownerDocument;
          $(doc).find('body').append($overlay);
        }
      } else {
        $image_resizer = editor.shared.$image_resizer;
        $overlay = editor.shared.$img_overlay;

        editor.events.on('destroy', function () {
          $image_resizer.removeClass('fr-active').appendTo($('body'));
        }, true);
      }

      // Shared destroy.
      editor.events.on('shared.destroy', function () {
        $image_resizer.html('').removeData().remove();
        $image_resizer = null;

        if (editor.opts.imageResize) {
          $overlay.remove();
          $overlay = null;
        }
      }, true);

      // Window resize. Exit from edit.
      if (!editor.helpers.isMobile()) {
        editor.events.$on($(editor.o_win), 'resize', function () {
          if ($current_image && !$current_image.hasClass('fr-uploading')) {
            _exitEdit(true);
          }
          else if ($current_image) {
            _repositionResizer();
            replace();
            showProgressBar(false);
          }
        });
      }

      // Image resize is enabled.
      if (editor.opts.imageResize) {
        doc = $image_resizer.get(0).ownerDocument;

        editor.events.$on($image_resizer, editor._mousedown, '.fr-handler', _handlerMousedown);
        editor.events.$on($(doc), editor._mousemove, _handlerMousemove);
        editor.events.$on($(doc.defaultView || doc.parentWindow), editor._mouseup, _handlerMouseup);

        editor.events.$on($overlay, 'mouseleave', _handlerMouseup);

        // Accessibility.

        // Used for keys holing.
        var step = 1;
        var prevKey = null;
        var prevTimestamp = 0;

        // Keydown event.
        editor.events.on('keydown', function (e) {
          if ($current_image) {
            var ctrlKey = navigator.userAgent.indexOf('Mac OS X') != -1 ? e.metaKey : e.ctrlKey;
            var keycode = e.which;

            if (keycode !== prevKey || e.timeStamp - prevTimestamp > 200) {
              step = 1; // Reset step. Known browser issue: Keyup does not trigger when ctrl is pressed.
            }

            // Increase image size.
            if ((keycode == $.FE.KEYCODE.EQUALS || (editor.browser.mozilla && keycode == $.FE.KEYCODE.FF_EQUALS)) && ctrlKey && !e.altKey) {
              step = _resizeImage.call(this, e, 1, 1, step);
            }
            // Decrease image size.
            else if ((keycode == $.FE.KEYCODE.HYPHEN || (editor.browser.mozilla && keycode == $.FE.KEYCODE.FF_HYPHEN)) && ctrlKey && !e.altKey) {
              step = _resizeImage.call(this, e, 2, -1, step);
            }

            // Save key code.
            prevKey = keycode;

            // Save timestamp.
            prevTimestamp = e.timeStamp;
          }
        }, true);

        // Reset the step on key up event.
        editor.events.on('keyup', function () {
          step = 1;
        });
      }
    }

    /**
     * Remove the current image.
     */
    function remove ($img) {
      $img = $img || $current_image;
      if ($img) {
        if (editor.events.trigger('image.beforeRemove', [$img]) !== false) {
          editor.popups.hideAll();
          _selectImage();
          _exitEdit(true);

          if (!editor.undo.canDo()) editor.undo.saveStep();

          if ($img.get(0) == editor.el) {
            $img.removeAttr('src');
          } else {
            if ($img.get(0).parentNode.tagName == 'A') {
              editor.selection.setBefore($img.get(0).parentNode) || editor.selection.setAfter($img.get(0).parentNode) || $img.parent().after($.FE.MARKERS);
              $($img.get(0).parentNode).remove();
            } else {
              editor.selection.setBefore($img.get(0)) || editor.selection.setAfter($img.get(0)) || $img.after($.FE.MARKERS);
              $img.remove();
            }

            editor.html.fillEmptyBlocks();
            editor.selection.restore();
          }

          editor.undo.saveStep();
        }
      }
    }

    function _editorKeydownHandler (e) {
      var key_code = e.which;
      if ($current_image && (key_code == $.FE.KEYCODE.BACKSPACE || key_code == $.FE.KEYCODE.DELETE)) {
        e.preventDefault();
        e.stopPropagation();
        remove();
        return false;
      }

      if ($current_image && key_code == $.FE.KEYCODE.ESC) {
        var $img = $current_image;
        _exitEdit(true);
        editor.selection.setAfter($img.get(0));
        editor.selection.restore();
        e.preventDefault();
        return false;
      }

      // Move cursor if left and right arrows are used.
      if ($current_image && (key_code == $.FE.KEYCODE.ARROW_LEFT || key_code == $.FE.KEYCODE.ARROW_RIGHT)) {
        var img = $current_image.get(0);
        _exitEdit(true);
        if (key_code == $.FE.KEYCODE.ARROW_LEFT) {
          editor.selection.setBefore(img);
        }
        else {
          editor.selection.setAfter(img);
        }
        editor.selection.restore();
        e.preventDefault();
        return false;
      }
      if ($current_image && key_code != $.FE.KEYCODE.F10 && !editor.keys.isBrowserAction(e)) {
        e.preventDefault();
        return false;
      }
    }

    /**
     * Initialization.
     */
    function _init () {
      _initEvents();

      // Init on image.
      if (editor.el.tagName == 'IMG') {
        editor.$el.addClass('fr-view');
      }

      editor.events.$on(editor.$el, editor.helpers.isMobile() && !editor.helpers.isWindowsPhone() ? 'touchend' : 'click', editor.el.tagName == 'IMG' ? null : 'img:not([contenteditable="false"])', _edit);

      if (editor.helpers.isMobile()) {
        editor.events.$on(editor.$el, 'touchstart', editor.el.tagName == 'IMG' ? null : 'img:not([contenteditable="false"])', function () {
          touchScroll = false;
        })

        editor.events.$on(editor.$el, 'touchmove', function () {
          touchScroll = true;
        });
      }

      if (editor.$wp) {
        editor.events.on('window.keydown keydown', _editorKeydownHandler, true)
      }
      else {
        editor.events.$on(editor.$win, 'keydown', _editorKeydownHandler);
      }

      // ESC from accessibility.
      editor.events.on('toolbar.esc', function () {
        if ($current_image) {
          if (editor.$wp) {
            editor.events.disableBlur();
            editor.events.focus();
          }
          else {
            var $img = $current_image;
            _exitEdit(true);
            editor.selection.setAfter($img.get(0));
            editor.selection.restore();
          }
          return false;
        }
      }, true);

      // Copy/cut image.
      editor.events.on('window.cut window.copy', function (e) {
        if ($current_image) {
          _selectImage();
          $.FE.copied_text = '\n';
          $.FE.copied_html = $current_image.get(0).outerHTML;

          if (e.type == 'copy') {
            setTimeout(function () {
              _editImg($current_image);
            })
          }
          else {
            _exitEdit(true);
            editor.undo.saveStep();
            setTimeout(function () {
              editor.undo.saveStep();
            }, 0);
          }
        }
      }, true);

      // Do not leave page while uploading.
      editor.events.$on($(editor.o_win), 'keydown', function (e) {
        var key_code = e.which;
        if ($current_image && key_code == $.FE.KEYCODE.BACKSPACE) {
          e.preventDefault();
          return false;
        }
      });

      // Check if image is uploading to abort it.
      editor.events.$on(editor.$win, 'keydown', function (e) {
        var key_code = e.which;
        if ($current_image && $current_image.hasClass('fr-uploading') && key_code == $.FE.KEYCODE.ESC) {
          $current_image.trigger('abortUpload');
        }
      });

      editor.events.on('destroy', function () {
        if ($current_image && $current_image.hasClass('fr-uploading')) {
          $current_image.trigger('abortUpload');
        }
      });

      editor.events.on('paste.before', _clipboardPaste);
      editor.events.on('paste.beforeCleanup', _clipboardPasteCleanup);
      editor.events.on('paste.after', _uploadPastedImages);

      editor.events.on('html.set', _refreshImageList);
      editor.events.on('html.inserted', _refreshImageList);
      _refreshImageList();
      editor.events.on('destroy', function () {
        images = [];
      })

      editor.events.on('html.get', function (html) {
        html = html.replace(/<(img)((?:[\w\W]*?))class="([\w\W]*?)(fr-uploading|fr-error)([\w\W]*?)"((?:[\w\W]*?))>/g, '');

        return html;
      });

      if (editor.opts.imageOutputSize) {
        var imgs;

        editor.events.on('html.beforeGet', function () {
          imgs = editor.el.querySelectorAll('img')
          for (var i = 0; i < imgs.length; i++) {
            var width = imgs[i].style.width || $(imgs[i]).width();
            var height = imgs[i].style.height || $(imgs[i]).height();

            if (width) imgs[i].setAttribute('width', ('' + width).replace(/px/, ''));
            if (height) imgs[i].setAttribute('height', ('' + height).replace(/px/, ''));
          }
        });

        editor.events.on('html.afterGet', function () {
          for (var i = 0; i < imgs.length; i++) {
            imgs[i].removeAttribute('width');
            imgs[i].removeAttribute('height');
          }
        });
      }

      if (editor.opts.iframe) {
        editor.events.on('image.loaded', editor.size.syncIframe);
      }

      if (editor.$wp) {
        _syncImages();
        editor.events.on('contentChanged', _syncImages);
      }

      editor.events.$on($(editor.o_win), 'orientationchange.image', function () {
        setTimeout(function () {
          var $current_image = get();
          if ($current_image) {
            _editImg($current_image);
          }
        }, 0);
      });

      _initEditPopup(true);
      _initInsertPopup(true);
      _initSizePopup(true);
      _initAltPopup(true);

      editor.events.on('node.remove', function ($node) {
        if ($node.get(0).tagName == 'IMG') {
          remove($node);
          return false;
        }
      });
    }

    function _uploadPastedImages () {
      if (!editor.opts.imagePaste) {
        editor.$el.find('img[data-fr-image-pasted]').remove();
      } else {
        // Safari won't work https://bugs.webkit.org/show_bug.cgi?id=49141
        editor.$el.find('img[data-fr-image-pasted]').each(function (index, img) {
          if (editor.opts.imagePasteProcess) {
            var width = editor.opts.imageDefaultWidth;
            if (width && width != 'auto') {
              width = width + (editor.opts.imageResizeWithPercent ? '%' : 'px');
            }
            $(img).css('width', width);

            $(img)
              .removeClass('fr-dii fr-dib fr-fir fr-fil')
              .addClass((editor.opts.imageDefaultDisplay ? 'fr-di' + editor.opts.imageDefaultDisplay[0] : '') + (editor.opts.imageDefaultAlign ? (editor.opts.imageDefaultAlign != 'center' ? ' fr-fi' + editor.opts.imageDefaultAlign[0] : '') : ''));
          }

          // Data images.
          if (img.src.indexOf('data:') === 0) {
            if (editor.events.trigger('image.beforePasteUpload', [img]) === false) {
              return false;
            }

            // Show the progress bar.
            $current_image = $(img);
            _repositionResizer();
            _showEditPopup();
            replace();
            showProgressBar();
            editor.edit.off();

            // Convert image to blob.
            var binary = atob($(img).attr('src').split(',')[1]);
            var array = [];
            for (var i = 0; i < binary.length; i++) {
              array.push(binary.charCodeAt(i));
            }
            var upload_img = new Blob([new Uint8Array(array)], {
              type: 'image/jpeg'
            });

            upload([upload_img]);

            $(img).removeAttr('data-fr-image-pasted');
          }

          // Images without http (Safari ones.).
          else if (img.src.indexOf('http') !== 0) {
            editor.selection.save();
            $(img).remove();
            editor.selection.restore();
          } else {
            $(img).removeAttr('data-fr-image-pasted');
          }
        });
      }
    }

    function _clipboardPaste (e) {
      if (e && e.clipboardData) {
        if (e.clipboardData.items && e.clipboardData.items[0]) {

          var file = e.clipboardData.items[0].getAsFile();

          if (file) {
            var reader = new FileReader();
            reader.onload = function (e) {
              var result = e.target.result;

              // Default width.
              var width = editor.opts.imageDefaultWidth;
              if (width && width != 'auto') {
                width = width + (editor.opts.imageResizeWithPercent ? '%' : 'px');
              }

              editor.html.insert('<img data-fr-image-pasted="true" class="' + (editor.opts.imageDefaultDisplay ? 'fr-di' + editor.opts.imageDefaultDisplay[0] : '') + (editor.opts.imageDefaultAlign ? (editor.opts.imageDefaultAlign != 'center' ? ' fr-fi' + editor.opts.imageDefaultAlign[0] : '') : '') + '" src="' + result + '"' + (width ? ' style="width: ' + width + ';"' : '') + '>');

              editor.events.trigger('paste.after');
            };

            reader.readAsDataURL(file);

            return false;
          }
        }
      }
    }

    function _clipboardPasteCleanup (clipboard_html) {
      clipboard_html = clipboard_html.replace(/<img /gi, '<img data-fr-image-pasted="true" ');
      return clipboard_html;
    }

    /**
     * Start edit.
     */
    var touchScroll;

    function _edit (e) {
      if ($(this).parents('[contenteditable]:not(.fr-element):not(body):first').attr('contenteditable') == 'false') return true;

      if (e && e.type == 'touchend' && touchScroll) {
        return true;
      }

      if (e && editor.edit.isDisabled()) {
        e.stopPropagation();
        e.preventDefault();
        return false;
      }

      // Hide resizer for other instances.
      for (var i = 0; i < $.FE.INSTANCES.length; i++) {
        if ($.FE.INSTANCES[i] != editor) {
          $.FE.INSTANCES[i].events.trigger('image.hideResizer');
        }
      }

      editor.toolbar.disable();

      if (e) {
        e.stopPropagation();
        e.preventDefault();
      }

      // Hide keyboard.
      if (editor.helpers.isMobile()) {
        editor.events.disableBlur();
        editor.$el.blur();
        editor.events.enableBlur();
      }

      if (editor.opts.iframe) {
        editor.size.syncIframe();
      }

      $current_image = $(this);
      _selectImage();
      _repositionResizer();
      _showEditPopup();

      editor.selection.clear();
      editor.button.bulkRefresh();

      editor.events.trigger('video.hideResizer');
    }

    /**
     * Exit edit.
     */

    function _exitEdit (force_exit) {
      if ($current_image && (_canExit() || force_exit === true)) {
        editor.toolbar.enable();

        $image_resizer.removeClass('fr-active');

        editor.popups.hide('image.edit');

        $current_image = null;

        _unmarkExit();
      }
    }

    var img_exit_flag = false;

    function _markExit () {
      img_exit_flag = true;
    }

    function _unmarkExit () {
      img_exit_flag = false;
    }

    function _canExit () {
      return img_exit_flag;
    }

    /**
     * Align image.
     */

    function align (val) {
      $current_image.removeClass('fr-fir fr-fil');
      if (val == 'left') {
        $current_image.addClass('fr-fil');
      } else if (val == 'right') {
        $current_image.addClass('fr-fir');
      }

      _repositionResizer();
      _showEditPopup();
    }

    /**
     * Refresh the align icon.
     */

    function refreshAlign ($btn) {
      if ($current_image) {
        if ($current_image.hasClass('fr-fil')) {
          $btn.find('> *:first').replaceWith(editor.icon.create('align-left'));
        } else if ($current_image.hasClass('fr-fir')) {
          $btn.find('> *:first').replaceWith(editor.icon.create('align-right'));
        } else {
          $btn.find('> *:first').replaceWith(editor.icon.create('align-justify'));
        }
      }
    }

    /**
     * Refresh the align option from the dropdown.
     */

    function refreshAlignOnShow ($btn, $dropdown) {
      if ($current_image) {
        var alignment = 'justify';

        if ($current_image.hasClass('fr-fil')) {
          alignment = 'left';
        } else if ($current_image.hasClass('fr-fir')) {
          alignment = 'right';
        }

        $dropdown.find('.fr-command[data-param1="' + alignment + '"]').addClass('fr-active').attr('aria-selected', true);
      }
    }

    /**
     * Align image.
     */

    function display (val) {
      $current_image.removeClass('fr-dii fr-dib');
      if (val == 'inline') {
        $current_image.addClass('fr-dii');
      } else if (val == 'block') {
        $current_image.addClass('fr-dib');
      }

      _repositionResizer();
      _showEditPopup();
    }

    /**
     * Refresh the image display selected option.
     */

    function refreshDisplayOnShow ($btn, $dropdown) {
      var d = 'block';
      if ($current_image.hasClass('fr-dii')) {
        d = 'inline';
      }

      $dropdown.find('.fr-command[data-param1="' + d + '"]').addClass('fr-active').attr('aria-selected', true);
    }

    /**
     * Show the replace popup.
     */

    function replace () {
      var $popup = editor.popups.get('image.insert');
      if (!$popup) $popup = _initInsertPopup();

      if (!editor.popups.isVisible('image.insert')) {
        hideProgressBar();
        editor.popups.refresh('image.insert');
        editor.popups.setContainer('image.insert', $(editor.opts.scrollableContainer));
      }

      var left = $current_image.offset().left + $current_image.width() / 2;
      var top = $current_image.offset().top + $current_image.height();

      editor.popups.show('image.insert', left, top, $current_image.outerHeight());
    }

    /**
     * Place selection around current image.
     */
    function _selectImage () {
      if ($current_image) {
        editor.selection.clear();
        var range = editor.doc.createRange();
        range.selectNode($current_image.get(0));
        var selection = editor.selection.get();
        selection.addRange(range);
      }
    }

    /**
     * Get back to the image main popup.
     */
    function back () {
      if ($current_image) {
        editor.events.disableBlur();
        $('.fr-popup input:focus').blur();
        _editImg($current_image);
      } else {
        editor.events.disableBlur();
        editor.selection.restore();
        editor.events.enableBlur();

        editor.popups.hide('image.insert');
        editor.toolbar.showInline();
      }
    }

    /**
     * Get the current image.
     */

    function get () {
      return $current_image;
    }

    /**
     * Apply specific style.
     */

    function applyStyle (val, imageStyles, multipleStyles) {
      if (typeof imageStyles == 'undefined') imageStyles = editor.opts.imageStyles;
      if (typeof multipleStyles == 'undefined') multipleStyles = editor.opts.imageMultipleStyles;

      if (!$current_image) return false;

      // Remove multiple styles.
      if (!multipleStyles) {
        var styles = Object.keys(imageStyles);
        styles.splice(styles.indexOf(val), 1);
        $current_image.removeClass(styles.join(' '));
      }

      $current_image.toggleClass(val);

      _editImg($current_image);
    }

    return {
      _init: _init,
      showInsertPopup: showInsertPopup,
      showLayer: showLayer,
      refreshUploadButton: refreshUploadButton,
      refreshByURLButton: refreshByURLButton,
      upload: upload,
      insertByURL: insertByURL,
      align: align,
      refreshAlign: refreshAlign,
      refreshAlignOnShow: refreshAlignOnShow,
      display: display,
      refreshDisplayOnShow: refreshDisplayOnShow,
      replace: replace,
      back: back,
      get: get,
      insert: insert,
      showProgressBar: showProgressBar,
      remove: remove,
      hideProgressBar: hideProgressBar,
      applyStyle: applyStyle,
      showAltPopup: showAltPopup,
      showSizePopup: showSizePopup,
      setAlt: setAlt,
      setSize: setSize,
      exitEdit: _exitEdit,
      edit: _editImg
    }
  }

  // Insert image button.
  $.FE.DefineIcon('insertImage', {
    NAME: 'image'
  });
  $.FE.RegisterShortcut($.FE.KEYCODE.P, 'insertImage', null, 'P');
  $.FE.RegisterCommand('insertImage', {
    title: 'Insert Image',
    undo: false,
    focus: true,
    refreshAfterCallback: false,
    popup: true,
    callback: function () {
      if (!this.popups.isVisible('image.insert')) {
        this.image.showInsertPopup();
      } else {
        if (this.$el.find('.fr-marker').length) {
          this.events.disableBlur();
          this.selection.restore();
        }
        this.popups.hide('image.insert');
      }
    },
    plugin: 'image'
  });

  // Image upload button inside the insert image popup.
  $.FE.DefineIcon('imageUpload', {
    NAME: 'upload'
  });
  $.FE.RegisterCommand('imageUpload', {
    title: 'Upload Image',
    undo: false,
    focus: false,
    toggle: true,
    callback: function () {
      this.image.showLayer('image-upload');
    },
    refresh: function ($btn) {
      this.image.refreshUploadButton($btn);
    }
  });

  // Image by URL button inside the insert image popup.
  $.FE.DefineIcon('imageByURL', {
    NAME: 'link'
  });
  $.FE.RegisterCommand('imageByURL', {
    title: 'By URL',
    undo: false,
    focus: false,
    toggle: true,
    callback: function () {
      this.image.showLayer('image-by-url');
    },
    refresh: function ($btn) {
      this.image.refreshByURLButton($btn);
    }
  })

  // Insert image button inside the insert by URL layer.
  $.FE.RegisterCommand('imageInsertByURL', {
    title: 'Insert Image',
    undo: true,
    refreshAfterCallback: false,
    callback: function () {
      this.image.insertByURL();
    },
    refresh: function ($btn) {
      var $current_image = this.image.get();
      if (!$current_image) {
        $btn.text(this.language.translate('Insert'));
      } else {
        $btn.text(this.language.translate('Replace'));
      }
    }
  })

  // Image display.
  $.FE.DefineIcon('imageDisplay', {
    NAME: 'star'
  })
  $.FE.RegisterCommand('imageDisplay', {
    title: 'Display',
    type: 'dropdown',
    options: {
      inline: 'Inline',
      block: 'Break Text'
    },
    callback: function (cmd, val) {
      this.image.display(val);
    },
    refresh: function ($btn) {
      if (!this.opts.imageTextNear) $btn.addClass('fr-hidden');
    },
    refreshOnShow: function ($btn, $dropdown) {
      this.image.refreshDisplayOnShow($btn, $dropdown);
    }
  })

  // Image align.
  if (!$.FE.ICONS.align) {
    $.FE.DefineIcon('align', { NAME: 'align-left' });
    $.FE.DefineIcon('align-left', { NAME: 'align-left' });
    $.FE.DefineIcon('align-right', { NAME: 'align-right' });
    $.FE.DefineIcon('align-center', { NAME: 'align-center' });
    $.FE.DefineIcon('align-justify', { NAME: 'align-justify' });
  }

  $.FE.DefineIcon('imageAlign', {
    NAME: 'align-center'
  })
  $.FE.RegisterCommand('imageAlign', {
    type: 'dropdown',
    title: 'Align',
    options: {
      left: 'Align Left',
      justify: 'None',
      right: 'Align Right'
    },
    html: function () {
      var c = '<ul class="fr-dropdown-list" role="presentation">';
      var options = $.FE.COMMANDS.imageAlign.options;
      for (var val in options) {
        if (options.hasOwnProperty(val)) {
          c += '<li role="presentation"><a class="fr-command fr-title" tabIndex="-1" role="option" data-cmd="imageAlign" data-param1="' + val + '" title="' + this.language.translate(options[val]) + '">' + this.icon.create('align-' + val) + '<span class="fr-sr-only">' + this.language.translate(options[val]) + '</span></a></li>';
        }
      }
      c += '</ul>';

      return c;
    },
    callback: function (cmd, val) {
      this.image.align(val);
    },
    refresh: function ($btn) {
      this.image.refreshAlign($btn);
    },
    refreshOnShow: function ($btn, $dropdown) {
      this.image.refreshAlignOnShow($btn, $dropdown);
    }
  })

  // Image replace.
  $.FE.DefineIcon('imageReplace', {
    NAME: 'exchange'
  })
  $.FE.RegisterCommand('imageReplace', {
    title: 'Replace',
    undo: false,
    focus: false,
    popup: true,
    refreshAfterCallback: false,
    callback: function () {
      this.image.replace();
    }
  })

  // Image remove.
  $.FE.DefineIcon('imageRemove', {
    NAME: 'trash'
  })
  $.FE.RegisterCommand('imageRemove', {
    title: 'Remove',
    callback: function () {
      this.image.remove();
    }
  })

  // Image back.
  $.FE.DefineIcon('imageBack', {
    NAME: 'arrow-left'
  });
  $.FE.RegisterCommand('imageBack', {
    title: 'Back',
    undo: false,
    focus: false,
    back: true,
    callback: function () {
      this.image.back();
    },
    refresh: function ($btn) {
      var $current_image = this.image.get();
      if (!$current_image && !this.opts.toolbarInline) {
        $btn.addClass('fr-hidden');
        $btn.next('.fr-separator').addClass('fr-hidden');
      } else {
        $btn.removeClass('fr-hidden');
        $btn.next('.fr-separator').removeClass('fr-hidden');
      }
    }
  });

  $.FE.RegisterCommand('imageDismissError', {
    title: 'OK',
    undo: false,
    callback: function () {
      this.image.hideProgressBar(true);
    }
  })

  // Image styles.
  $.FE.DefineIcon('imageStyle', {
    NAME: 'magic'
  })
  $.FE.RegisterCommand('imageStyle', {
    title: 'Style',
    type: 'dropdown',
    html: function () {
      var c = '<ul class="fr-dropdown-list" role="presentation">';
      var options = this.opts.imageStyles;
      for (var cls in options) {
        if (options.hasOwnProperty(cls)) {
          c += '<li role="presentation"><a class="fr-command" tabIndex="-1" role="option" data-cmd="imageStyle" data-param1="' + cls + '">' + this.language.translate(options[cls]) + '</a></li>';
        }
      }
      c += '</ul>';

      return c;
    },
    callback: function (cmd, val) {
      this.image.applyStyle(val);
    },
    refreshOnShow: function ($btn, $dropdown) {
      var $current_image = this.image.get();

      if ($current_image) {
        $dropdown.find('.fr-command').each(function () {
          var cls = $(this).data('param1');
          var active = $current_image.hasClass(cls);
          $(this).toggleClass('fr-active', active).attr('aria-selected', active);
        })
      }
    }
  })

  // Image alt.
  $.FE.DefineIcon('imageAlt', {
    NAME: 'info'
  })
  $.FE.RegisterCommand('imageAlt', {
    undo: false,
    focus: false,
    popup: true,
    title: 'Alternate Text',
    callback: function () {
      this.image.showAltPopup();
    }
  });

  $.FE.RegisterCommand('imageSetAlt', {
    undo: true,
    focus: false,
    title: 'Update',
    refreshAfterCallback: false,
    callback: function () {
      this.image.setAlt();
    }
  });

  // Image size.
  $.FE.DefineIcon('imageSize', {
    NAME: 'arrows-alt'
  })
  $.FE.RegisterCommand('imageSize', {
    undo: false,
    focus: false,
    popup: true,
    title: 'Change Size',
    callback: function () {
      this.image.showSizePopup();
    }
  });

  $.FE.RegisterCommand('imageSetSize', {
    undo: true,
    focus: false,
    title: 'Update',
    refreshAfterCallback: false,
    callback: function () {
      this.image.setSize();
    }
  });

}));
