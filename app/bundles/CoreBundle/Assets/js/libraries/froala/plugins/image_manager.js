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
    imageManagerLoadURL: 'https://i.froala.com/load-files',
    imageManagerLoadMethod: 'get',
    imageManagerLoadParams: {},
    imageManagerPreloader: '',
    imageManagerDeleteURL: '',
    imageManagerDeleteMethod: 'post',
    imageManagerDeleteParams: {},
    imageManagerPageSize: 12,
    imageManagerScrollOffset: 20,
    imageManagerToggleTags: true
  });

  $.FE.PLUGINS.imageManager = function (editor) {
    var $modal;
    var modal_id = 'image_manager';

    var $head;
    var $body;
    var $preloader;
    var $media_files;
    var $image_tags;
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
    error_messages[MISSING_LOAD_URL_OPTION] = 'Missing imageManagerLoadURL option.';
    error_messages[LOAD_BAD_RESPONSE] = 'Parsing load response failed.';
    error_messages[MISSING_IMG_THUMB] = 'Missing image thumb.';
    error_messages[MISSING_IMG_URL] = 'Missing image URL.';
    error_messages[ERROR_DURING_DELETE] = 'Error during delete image request.';
    error_messages[MISSING_DELETE_URL_OPTION] = 'Missing imageManagerDeleteURL option.';

    /*
     * Show the image manager.
     */
    function show () {
      // Build the image manager.
      if (!$modal) {
        // Build head.
        var head = '<div class="fr-modal-head-line"><i class="fa fa-bars fr-modal-more fr-not-available" id="fr-modal-more-' + editor.sid + '" title="' + editor.language.translate('Tags') + '"></i><h4 data-text="true">' + editor.language.translate('Manage Images') + '</h4></div>';

        // Tags
        head += '<div class="fr-modal-tags" id="fr-modal-tags"></div>';

        // Preloader.
        var body = '<img class="fr-preloader" id="fr-preloader" alt="' + editor.language.translate('Loading') + '.." src="' + editor.opts.imageManagerPreloader + '" style="display: none;">';

        // Image list.
        body += '<div class="fr-image-list" id="fr-image-list"></div>';

        var modalHash = editor.modals.create(modal_id, head, body);
        $modal = modalHash.$modal;
        $head = modalHash.$head;
        $body = modalHash.$body;
      }

      // Set the current image (if modal is opened to replace an image).
      $modal.data('current-image', editor.image.get());

      // Show modal.
      editor.modals.show(modal_id);

      if (!$preloader) {
        _delayedInit();
      }

      // Load images.
      _loadImages();
    }

    /*
     * Hide the image manager.
     */
    function hide () {
      // Hide modal.
      editor.modals.hide(modal_id);
    }

    /*
     * Get the number of columns based on window width.
     */
    function _columnNumber () {
      var window_width = $(window).outerWidth();

      // Screen XS.
      if (window_width < 768) {
        return 2;
      }

      // Screen SM and MD.
      else if (window_width < 1200) {
        return 3;
      }

      // Screen LG.
      else {
        return 4;
      }
    }

    /*
     * Add the correct number of columns.
     */
    function _buildColumns () {
      $media_files.empty();

      for (var i = 0; i < column_number; i++) {
        $media_files.append('<div class="fr-list-column"></div>');
      }
    }

    /*
     * Load images from server.
     */
    function _loadImages () {
      $preloader.show();
      $media_files.find('.fr-list-column').empty();

      // If the images load URL is set.
      if (editor.opts.imageManagerLoadURL) {
        // Make GET request to get the images.
        $.ajax({
          url: editor.opts.imageManagerLoadURL,
          method: editor.opts.imageManagerLoadMethod,
          data: editor.opts.imageManagerLoadParams,
          dataType: 'json',
          crossDomain: editor.opts.requestWithCORS,
          xhrFields: {
            withCredentials: editor.opts.requestWithCredentials
          },
          headers: editor.opts.requestHeaders
        })
        // On success start processing the response.
        .done(function (data, status, xhr) {
          editor.events.trigger('imageManager.imagesLoaded', [data]);
          _processLoadedImages(data, xhr.response);
          $preloader.hide();
        })

        // On fail throw error during request.
        .fail(function () {
          var xhr = this.xhr();
          _throwError(ERROR_DURING_LOAD, xhr.response || xhr.responseText);
        });
      }

      // Throw missing imageManagerLoadURL option error.
      else {
        _throwError(MISSING_LOAD_URL_OPTION);
      }
    }

    /*
     * Process loaded images.
     */
    function _processLoadedImages (imgs, response) {
      try {
        $media_files.find('.fr-list-column').empty();
        page = 0;
        image_count = 0;
        loaded_images = 0;
        images = imgs;

        // Load files.
        _infiniteScroll();
      }

      // Throw error while parsing the response.
      catch (ex) {
        _throwError(LOAD_BAD_RESPONSE, response);
      }
    }

    /*
     * Load more images if necessary.
     */
    function _infiniteScroll () {
      // If there aren't enough images in the modal or if the user scrolls down.
      if (image_count < images.length &&
        ($media_files.outerHeight() <= $body.outerHeight() + editor.opts.imageManagerScrollOffset ||
        $body.scrollTop() + editor.opts.imageManagerScrollOffset > $media_files.outerHeight() - $body.outerHeight())) {
        // Increase page number.
        page++;

        // Load each image on this page.
        for (var i = editor.opts.imageManagerPageSize * (page - 1); i < Math.min(images.length, editor.opts.imageManagerPageSize * page); i++) {
          _loadImage(images[i]);
        }
      }
    }

    /*
     * Load file.
     */
    function _loadImage (image) {
      var img = new Image();
      var $img_container = $('<div class="fr-image-container fr-empty fr-image-' + (loaded_images++) + '" data-loading="' + editor.language.translate('Loading') + '.." data-deleting="' + editor.language.translate('Deleting') + '..">');

      // After adding image empty container modal might change its height.
      _resizeModal(false);

      // Image has been loaded.
      img.onload = function () {
        // Update image container height.
        $img_container.height(Math.floor($img_container.width() / img.width * img.height));

        // Create image HTML.
        var $img = $('<img/>');

        // Use image thumb in image manager.
        if (image.thumb) {
          // Set image src attribute/
          $img.attr('src', image.thumb);
        }

        // Image does not have thumb.
        else {
          // Throw missing image thumb error.
          _throwError(MISSING_IMG_THUMB, image);

          // Set image URL as src attribute.
          if (image.url) {
            $img.attr('src', image.url);
          }
          // Missing image URL.
          else {
            // Throw missing image url error.
            _throwError(MISSING_IMG_URL, image);

            // Don't go further if image does not have a src attribute.
            return false;
          }
        }

        // Save image URL.
        if (image.url) $img.attr('data-url', image.url);

        // Image tags.
        if (image.tag) {
          // Show tags only if there are any.
          $head.find('.fr-modal-more.fr-not-available').removeClass('fr-not-available');
          $head.find('.fr-modal-tags').show();

          // Image has more than one tag.
          if (image.tag.indexOf(',') >= 0) {
            // Add tags to the image manager tag list.
            var tags = image.tag.split(',');

            for (var i = 0; i < tags.length; i++) {
              // Remove trailing spaces.
              tags[i] = tags[i].trim();

              // Add tag.
              if ($image_tags.find('a[title="' + tags[i] + '"]').length === 0) {
                $image_tags.append('<a role="button" title="' + tags[i] + '">' + tags[i] + '</a>');
              }
            }

            // Set img tag attribute.
            $img.attr('data-tag', tags.join());
          }

          // Image has only one tag.
          else {
            // Add tag to the tag list.
            if ($image_tags.find('a[title="' + image.tag.trim() + '"]').length === 0) {
              $image_tags.append('<a role="button" title="' + image.tag.trim() + '">' + image.tag.trim() + '</a>');
            }

            // Set img tag attribute.
            $img.attr('data-tag', image.tag.trim());
          }
        }

        // Image alt.
        if (image.name) {
          $img.attr('alt', image.name);
        }

        // Set image additional data.
        for (var key in image) {
          if (image.hasOwnProperty(key)) {
            if (key != 'thumb' && key != 'url' && key != 'tag') {
              $img.attr('data-' + key, image[key]);
            }
          }
        }

        // Add image and insert and delete buttons to the image container.
        $img_container
          .append($img)
          .append($(editor.icon.create('imageManagerDelete')).addClass('fr-delete-img').attr('title', editor.language.translate('Delete')))
          .append($(editor.icon.create('imageManagerInsert')).addClass('fr-insert-img').attr('title', editor.language.translate('Insert')))

        // Show image only if it has selected tags.
        $image_tags.find('.fr-selected-tag').each (function (index, tag) {
          if (!_imageHasTag($img, tag.text)) {
            $img_container.hide();
          }
        });

        // After an image is loaded the modal may need to be resized.
        $img.on('load', function () {
          // Image container is no longer empty.
          $img_container.removeClass('fr-empty');
          $img_container.height('auto');

          // Increase image counter.
          image_count++;

          // A loded image may break the images order. Reorder them starting with this image.
          var imgs = _getImages(parseInt($img.parent().attr('class').match(/fr-image-(\d+)/)[1], 10) + 1);

          // Reorder images.
          _reorderImages(imgs);

          // Image modal may need resizing.
          _resizeModal(false);

          // If this was the last image on page then we might need to load more.
          if (image_count % editor.opts.imageManagerPageSize === 0) {
            _infiniteScroll();
          }
        });

        // Trigger imageLoaded event.
        editor.events.trigger('imageManager.imageLoaded', [$img]);
      };

      // Error while loading the image.
      img.onerror = function () {
        image_count++;
        $img_container.remove();

        // Removing an image container may break image order.
        var imgs = _getImages(parseInt($img_container.attr('class').match(/fr-image-(\d+)/)[1], 10) + 1);

        // Reorder images.
        _reorderImages(imgs);

        _throwError(BAD_LINK, image);

        // If this was the last image on page then we might need to load more.
        if (image_count % editor.opts.imageManagerPageSize === 0) {
          _infiniteScroll();
        }
      };

      // Set the image object's src.
      img.src = image.thumb || image.url;

      // Add loaded or empty image to the media manager image list on the shortest column.
      _shortestColumn().append($img_container);
    }

    /*
     * Get the shortest image column.
     */
    function _shortestColumn () {
      var $col;
      var min_height;

      $media_files.find('.fr-list-column').each (function (index, col) {
        var $column = $(col);

        // Assume that the first column is the shortest.
        if (index === 0) {
          min_height = $column.outerHeight();
          $col = $column;
        }

        // Check if another column is shorter.
        else {
          if ($column.outerHeight() < min_height) {
            min_height = $column.outerHeight();
            $col = $column;
          }
        }
      });

      return $col;
    }

    /*
     * Get all images from the image manager.
     */
    function _getImages (from) {
      if (from === undefined) from = 0;
      var get_images = [];

      for (var i = loaded_images - 1; i >= from; i--) {
        var $image = $media_files.find('.fr-image-' + i);

        if ($image.length) {
          get_images.push($image);

          // Add images here before deleting them so the on load callback is triggered.
          $('<div id="fr-image-hidden-container">').append($image);
          $media_files.find('.fr-image-' + i).remove();
        }
      }

      return get_images;
    }

    /*
     * Add images back into the image manager.
     */
    function _reorderImages (imgs) {
      for (var i = imgs.length - 1; i >= 0; i--) {
        _shortestColumn().append(imgs[i]);
      }
    }

    /*
     * Resize the media manager modal if height changes.
     */
    function _resizeModal (infinite_scroll) {
      if (infinite_scroll === undefined) infinite_scroll = true;
      if (!$modal.is(':visible')) return true;

      // If width changes, the number of columns may change.
      var cols = _columnNumber();

      if (cols != column_number) {
        column_number = cols;

        // Get all images.
        var imgs = _getImages();

        // Remove current columns and add new ones.
        _buildColumns();

        // Reorder images.
        _reorderImages(imgs);
      }

      editor.modals.resize(modal_id);

      // Load more photos when window is resized if necessary.
      if (infinite_scroll) {
        _infiniteScroll();
      }
    }

    function _getImageAttrs ($img) {
      var img_attributes = {};
      var img_data = $img.data();

      for (var key in img_data) {
        if (img_data.hasOwnProperty(key)) {
          if (key != 'url' && key != 'tag') {
            img_attributes[key] = img_data[key];
          }
        }
      }

      return img_attributes;
    }

    /*
     * Insert image into the editor.
     */
    function _insertImage (e) {
      // Image to insert.
      var $img = $(e.currentTarget).siblings('img');

      var inst = $modal.data('instance') || editor;
      var $current_image = $modal.data('current-image');

      editor.modals.hide(modal_id);
      inst.image.showProgressBar();

      if (!$current_image) {
        // Make sure we have focus.
        inst.events.focus(true);
        inst.selection.restore();

        var rect = inst.position.getBoundingRect();

        var left = rect.left + rect.width / 2;
        var top = rect.top + rect.height;

        // Show the image insert popup.
        inst.popups.setContainer('image.insert', inst.$box || $('body'));
        inst.popups.show('image.insert', left, top);
      }
      else {
        $current_image.data('fr-old-src', $current_image.attr('src'));
        $current_image.trigger('click');
      }

      inst.image.insert($img.data('url'), false, _getImageAttrs($img), $current_image);
    }

    /*
     * Delete image.
     */
    function _deleteImage (e) {
      // Image to delete.
      var $img = $(e.currentTarget).siblings('img');

      // Confirmation message.
      var message = editor.language.translate('Are you sure? Image will be deleted.');

      // Ask for confirmation.
      if (confirm(message)) {
        // If the images delete URL is set.
        if (editor.opts.imageManagerDeleteURL) {
          // Before delete image event.
          if (editor.events.trigger('imageManager.beforeDeleteImage', [$img]) !== false) {
            $img.parent().addClass('fr-image-deleting');

            // Make request to delete image from server.
            $.ajax({
              method: editor.opts.imageManagerDeleteMethod,
              url: editor.opts.imageManagerDeleteURL,
              data: $.extend($.extend({ src: $img.attr('src') }, _getImageAttrs($img)), editor.opts.imageManagerDeleteParams),
              crossDomain: editor.opts.requestWithCORS,
              xhrFields: {
                withCredentials: editor.opts.requestWithCredentials
              },
              headers: editor.opts.requestHeaders
            })

              // On success remove the image from the image manager.
              .done(function (data) {
                editor.events.trigger('imageManager.imageDeleted', [data]);
                // A deleted image may break the images order. Reorder them starting with this image.
                var imgs = _getImages(parseInt($img.parent().attr('class').match(/fr-image-(\d+)/)[1], 10) + 1);

                // Remove the image.
                $img.parent().remove();

                // Reorder images.
                _reorderImages(imgs);

                // Modal needs resizing.
                _resizeModal(true);
              })

              // On fail throw error during request.
              .fail(function () {
                var xhr = this.xhr();
                _throwError(ERROR_DURING_DELETE, xhr.response || xhr.responseText);
              });
          }
        }

        // Throw missing imageManagerDeleteURL option error.
        else {
          _throwError(MISSING_DELETE_URL_OPTION);
        }
      }
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
      editor.events.trigger('imageManager.error', [{
        code: code,
        message: error_messages[code]
      }, response]);
    }

    /*
     * Toogle (show or hide) image tags.
     */
    function _toggleTags () {
      var title_height = $head.find('.fr-modal-head-line').outerHeight();
      var tags_height = $image_tags.outerHeight();

      // Use .fr-show-tags.
      $head.toggleClass('.fr-show-tags');

      if ($head.hasClass('.fr-show-tags')) {
        // Show tags by changing height to have transition.
        $head.css('height', title_height + tags_height);
        $image_tags.find('a').css('opacity', 1);
      }

      else {
        // Hide tags by changing height to have transition.
        $head.css('height', title_height);
        $image_tags.find('a').css('opacity', 0);
      }
    }

    /*
     * Show only images with selected tags.
     */
    function _showImagesByTags() {
      // Get all selected tags.
      var $tags = $image_tags.find('.fr-selected-tag');

      // Show only images with selected tags.
      if ($tags.length > 0) {
        // Hide all images.
        $media_files.find('img').parent().show();

        // Show only images with tag.
        $tags.each (function (index, tag) {
          $media_files.find('img').each (function (index, img) {
            var $img = $(img);

            if (!_imageHasTag($img, tag.text)) {
              $img.parent().hide();
            }
          });
        });
      }

      // There are no more tags selected. Show all images.
      else {
        $media_files.find('img').parent().show();
      }

      // Rearrange images.
      var imgs = _getImages();

      // Reorder images.
      _reorderImages(imgs);

      // Load more images if necessary.
      _infiniteScroll();
    }

    /*
     * Select an image tag from the list.
     */
    function _selectTag (e) {
      e.preventDefault();

      // Toggle current tags class.
      var $tag = $(e.currentTarget);
      $tag.toggleClass('fr-selected-tag');

      // Toggle selected tags.
      if (editor.opts.imageManagerToggleTags) $tag.siblings('a').removeClass('fr-selected-tag');

      // Change displayed images.
      _showImagesByTags();
    }

    /*
     * Method to check if an image has a specific tag.
     */
    function _imageHasTag ($image, tag) {
      var tags = $image.attr('data-tag').split(',');

      for (var i = 0; i < tags.length; i++) {
        if (tags[i] == tag) {
          return true;
        }
      }

      return false;
    }

    function _delayedInit() {
      $preloader = $modal.find('#fr-preloader');
      $media_files = $modal.find('#fr-image-list');
      $image_tags = $modal.find('#fr-modal-tags');

      // Columns.
      column_number = _columnNumber();
      _buildColumns();

      // Set height for title (we need this for show tags transition).
      $head.css('height', $head.find('.fr-modal-head-line').outerHeight());

      // Resize media manager modal on window resize.
      editor.events.$on($(editor.o_win), 'resize', function () {
        // Window resize with image manager opened.
        if (images) {
          _resizeModal(true);
        }

        // iOS window resize is triggered when modal first opens (no images loaded).
        else {
          _resizeModal(false);
        }
      });

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

      // Delete image.
      editor.events.bindClick($media_files, '.fr-delete-img', _deleteImage);

      // Make sure we don't trigger blur.
      $modal.on(editor._mousedown + ' ' + editor._mouseup, function (e) {
        e.stopPropagation();
      });

      // Mouse down on anything.
      $modal.on(editor._mousedown, '*', function () {
        editor.events.disableBlur();
      });

      // Infinite scroll
      $body.on('scroll', _infiniteScroll);

      // Click on image tags button.
      editor.events.bindClick($modal, 'i#fr-modal-more-' + editor.sid, _toggleTags);

      // Select an image tag.
      editor.events.bindClick($image_tags, 'a', _selectTag);
    }

    /*
     * Init media manager.
     */
    function _init () {
      if (!editor.$wp && editor.el.tagName != 'IMG') return false;
    }

    return {
      require: ['image'],
      _init: _init,
      show: show,
      hide: hide
    }
  };

  if (!$.FE.PLUGINS.image) {
    throw new Error('Image manager plugin requires image plugin.');
  }

  $.FE.DEFAULTS.imageInsertButtons.push('imageManager');

  $.FE.RegisterCommand('imageManager', {
    title: 'Browse',
    undo: false,
    focus: false,
    modal: true,
    callback: function () {
      this.imageManager.show();
    },
    plugin: 'imageManager'
  })

  // Add the font size icon.
  $.FE.DefineIcon('imageManager', {
    NAME: 'folder'
  });

  // Add the font size icon.
  $.FE.DefineIcon('imageManagerInsert', {
    NAME: 'plus'
  });

  // Add the font size icon.
  $.FE.DefineIcon('imageManagerDelete', {
    NAME: 'trash'
  });

}));
