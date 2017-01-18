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
    'video.insert': '[_BUTTONS_][_BY_URL_LAYER_][_EMBED_LAYER_]',
    'video.edit': '[_BUTTONS_]',
    'video.size': '[_BUTTONS_][_SIZE_LAYER_]'
  })

  $.extend($.FE.DEFAULTS, {
    videoInsertButtons: ['videoBack', '|', 'videoByURL', 'videoEmbed'],
    videoEditButtons: ['videoDisplay', 'videoAlign', 'videoSize', 'videoRemove'],
    videoResize: true,
    videoSizeButtons: ['videoBack', '|'],
    videoSplitHTML: false,
    videoTextNear: true,
    videoDefaultAlign: 'center',
    videoDefaultDisplay: 'block',
    videoMove: true
  });

  $.FE.VIDEO_PROVIDERS = [
    {
      test_regex: /^.*((youtu.be)|(youtube.com))\/((v\/)|(\/u\/\w\/)|(embed\/)|(watch\?))?\??v?=?([^#\&\?]*).*/,
      url_regex: /(?:https?:\/\/)?(?:www\.)?(?:m\.)?(?:youtube\.com|youtu\.be)\/(?:watch\?v=|embed\/)?([0-9a-zA-Z_\-]+)(.+)?/g,
      url_text: '//www.youtube.com/embed/$1',
      html: '<iframe width="640" height="360" src="{url}?wmode=opaque" frameborder="0" allowfullscreen></iframe>'
    },
    {
      test_regex: /^.*(vimeo\.com\/)((channels\/[A-z]+\/)|(groups\/[A-z]+\/videos\/))?([0-9]+)/,
      url_regex: /(?:https?:\/\/)?(?:www\.)?(?:vimeo\.com)\/(?:channels\/[A-z]+\/|groups\/[A-z]+\/videos\/)?(.+)/g,
      url_text: '//player.vimeo.com/video/$1',
      html: '<iframe width="640" height="360" src="{url}" frameborder="0" allowfullscreen></iframe>'
    },
    {
      test_regex: /^.+(dailymotion.com|dai.ly)\/(video|hub)?\/?([^_]+)[^#]*(#video=([^_&]+))?/,
      url_regex: /(?:https?:\/\/)?(?:www\.)?(?:dailymotion\.com|dai\.ly)\/(?:video|hub)?\/?(.+)/g,
      url_text: '//www.dailymotion.com/embed/video/$1',
      html: '<iframe width="640" height="360" src="{url}" frameborder="0" allowfullscreen></iframe>'
    },
    {
      test_regex: /^.+(screen.yahoo.com)\/[^_&]+/,
      url_regex: '',
      url_text: '',
      html: '<iframe width="640" height="360" src="{url}?format=embed" frameborder="0" allowfullscreen="true" mozallowfullscreen="true" webkitallowfullscreen="true" allowtransparency="true"></iframe>'
    },
    {
      test_regex: /^.+(rutube.ru)\/[^_&]+/,
      url_regex: /(?:https?:\/\/)?(?:www\.)?(?:rutube\.ru)\/(?:video)?\/?(.+)/g,
      url_text: '//rutube.ru/play/embed/$1',
      html: '<iframe width="640" height="360" src="{url}" frameborder="0" allowfullscreen="true" mozallowfullscreen="true" webkitallowfullscreen="true" allowtransparency="true"></iframe>'
    }
  ];

  $.FE.VIDEO_EMBED_REGEX = /^\W*((<iframe.*><\/iframe>)|(<embed.*>))\W*$/i;

  $.FE.PLUGINS.video = function (editor) {
    var $overlay;
    var $handler;
    var $video_resizer;
    var $current_video;

    /**
     * Refresh the image insert popup.
     */
    function _refreshInsertPopup () {
      var $popup = editor.popups.get('video.insert');

      var $url_input = $popup.find('.fr-video-by-url-layer input');
      $url_input.val('').trigger('change');

      var $embed_area = $popup.find('.fr-video-embed-layer textarea');
      $embed_area.val('').trigger('change');
    }

    /**
     * Show the video insert popup.
     */
    function showInsertPopup () {
      var $btn = editor.$tb.find('.fr-command[data-cmd="insertVideo"]');

      var $popup = editor.popups.get('video.insert');
      if (!$popup) $popup = _initInsertPopup();

      if (!$popup.hasClass('fr-active')) {
        editor.popups.refresh('video.insert');
        editor.popups.setContainer('video.insert', editor.$tb);

        var left = $btn.offset().left + $btn.outerWidth() / 2;
        var top = $btn.offset().top + (editor.opts.toolbarBottom ? 10 : $btn.outerHeight() - 10);
        editor.popups.show('video.insert', left, top, $btn.outerHeight());
      }
    }

    /**
     * Show the image edit popup.
     */
    function _showEditPopup () {
      var $popup = editor.popups.get('video.edit');
      if (!$popup) $popup = _initEditPopup();

      if ($popup) {
        editor.popups.setContainer('video.edit', $(editor.opts.scrollableContainer));
        editor.popups.refresh('video.edit');

        var $video_obj = $current_video.find('iframe, embed, video');
        var left = $video_obj.offset().left + $video_obj.outerWidth() / 2;
        var top = $video_obj.offset().top + $video_obj.outerHeight();

        editor.popups.show('video.edit', left, top, $video_obj.outerHeight());
      }
    }

    function _initInsertPopup (delayed) {
      if (delayed) {
        editor.popups.onRefresh('video.insert', _refreshInsertPopup);

        return true;
      }

      // Image buttons.
      var video_buttons = '';
      if (editor.opts.videoInsertButtons.length > 1) {
        video_buttons = '<div class="fr-buttons">' + editor.button.buildList(editor.opts.videoInsertButtons) + '</div>';
      }

      // Video by url layer.
      var by_url_layer = '';
      if (editor.opts.videoInsertButtons.indexOf('videoByURL') >= 0) {
        by_url_layer = '<div class="fr-video-by-url-layer fr-layer fr-active" id="fr-video-by-url-layer-' + editor.id + '"><div class="fr-input-line"><input id="fr-video-by-url-layer-text-' + editor.id + '" type="text" placeholder="http://" tabIndex="1" aria-required="true"></div><div class="fr-action-buttons"><button type="button" class="fr-command fr-submit" data-cmd="videoInsertByURL" tabIndex="2" role="button">' + editor.language.translate('Insert') + '</button></div></div>'
      }

      // Video embed layer.
      var embed_layer = '';
      if (editor.opts.videoInsertButtons.indexOf('videoEmbed') >= 0) {
        embed_layer = '<div class="fr-video-embed-layer fr-layer" id="fr-video-embed-layer-' + editor.id + '"><div class="fr-input-line"><textarea id="fr-video-embed-layer-text' + editor.id + '" type="text" placeholder="' + editor.language.translate('Embedded Code') + '" tabIndex="1" aria-required="true" rows="5"></textarea></div><div class="fr-action-buttons"><button type="button" class="fr-command fr-submit" data-cmd="videoInsertEmbed" tabIndex="2" role="button">' + editor.language.translate('Insert') + '</button></div></div>'
      }

      var template = {
        buttons: video_buttons,
        by_url_layer: by_url_layer,
        embed_layer: embed_layer
      }

      // Set the template in the popup.
      var $popup = editor.popups.create('video.insert', template);

      return $popup;
    }

    /**
     * Show the image upload layer.
     */
    function showLayer (name) {
      var $popup = editor.popups.get('video.insert');

      var left;
      var top;
      if (!$current_video && !editor.opts.toolbarInline) {
        var $btn = editor.$tb.find('.fr-command[data-cmd="insertVideo"]');
        left = $btn.offset().left + $btn.outerWidth() / 2;
        top = $btn.offset().top + (editor.opts.toolbarBottom ? 10 : $btn.outerHeight() - 10);
      }

      if (editor.opts.toolbarInline) {
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

      editor.popups.show('video.insert', left, top, 0);
      editor.accessibility.focusPopup($popup);
    }

    /**
     * Refresh the insert by url button.
     */
    function refreshByURLButton ($btn) {
      var $popup = editor.popups.get('video.insert');
      if ($popup.find('.fr-video-by-url-layer').hasClass('fr-active')) {
        $btn.addClass('fr-active').attr('aria-pressed', true);
      }
    }

    /**
     * Refresh the insert embed button.
     */
    function refreshEmbedButton ($btn) {
      var $popup = editor.popups.get('video.insert');
      if ($popup.find('.fr-video-embed-layer').hasClass('fr-active')) {
        $btn.addClass('fr-active').attr('aria-pressed', true);
      }
    }

    /**
     * Insert video embedded object.
     */
    function insert (embedded_code) {
      // Make sure we have focus.
      editor.events.focus(true);
      editor.selection.restore();

      editor.html.insert('<span contenteditable="false" draggable="true" class="fr-jiv fr-video fr-dv' + (editor.opts.videoDefaultDisplay[0]) + (editor.opts.videoDefaultAlign != 'center' ? ' fr-fv' + editor.opts.videoDefaultAlign[0] : '') + '">' + embedded_code + '</span>', false, editor.opts.videoSplitHTML);

      editor.popups.hide('video.insert');

      var $video = editor.$el.find('.fr-jiv');
      $video.removeClass('fr-jiv');

      $video.toggleClass('fr-draggable', editor.opts.videoMove);

      editor.events.trigger('video.inserted', [$video]);
    }

    /**
     * Insert video by URL.
     */
    function insertByURL (link) {
      if (typeof link == 'undefined') {
        var $popup = editor.popups.get('video.insert');
        link = $popup.find('.fr-video-by-url-layer input[type="text"]').val() || '';
      }

      var video = null;
      if (editor.helpers.isURL(link)) {
        for (var i = 0; i < $.FE.VIDEO_PROVIDERS.length; i++) {
          var vp = $.FE.VIDEO_PROVIDERS[i];
          if (vp.test_regex.test(link)) {
            video = link.replace(vp.url_regex, vp.url_text);
            video = vp.html.replace(/\{url\}/, video);
            break;
          }
        }
      }

      if (video) {
        insert(video);
      }
      else {
        editor.events.trigger('video.linkError', [link]);
      }
    }

    /**
     * Insert embedded video.
     */
    function insertEmbed (code) {
      if (typeof code == 'undefined') {
        var $popup = editor.popups.get('video.insert');
        code = $popup.find('.fr-video-embed-layer textarea').val() || '';
      }

      if (code.length === 0 || !$.FE.VIDEO_EMBED_REGEX.test(code)) {
        editor.events.trigger('video.codeError', [code]);
      }
      else {
        insert(code);
      }
    }

    /**
     * Mouse down to start resize.
     */
    function _handlerMousedown (e) {
      // Check if resizer belongs to current instance.
      if (!editor.core.sameInstance($video_resizer)) return true;

      e.preventDefault();
      e.stopPropagation();

      var c_x = e.pageX || (e.originalEvent.touches ? e.originalEvent.touches[0].pageX : null);
      var c_y = e.pageY || (e.originalEvent.touches ? e.originalEvent.touches[0].pageY : null);

      if (!c_x || !c_y) {
        return false;
      }

      if (!editor.undo.canDo()) editor.undo.saveStep();

      $handler = $(this);
      $handler.data('start-x', c_x);
      $handler.data('start-y', c_y);
      $overlay.show();
      editor.popups.hideAll();

      _unmarkExit();
    }

    /**
     * Do resize.
     */
    function _handlerMousemove (e) {
      // Check if resizer belongs to current instance.
      if (!editor.core.sameInstance($video_resizer)) return true;

      if ($handler) {
        e.preventDefault()

        var c_x = e.pageX || (e.originalEvent.touches ? e.originalEvent.touches[0].pageX : null);
        var c_y = e.pageY || (e.originalEvent.touches ? e.originalEvent.touches[0].pageY : null);

        if (!c_x || !c_y) {
          return false;
        }

        var s_x = $handler.data('start-x');
        var s_y = $handler.data('start-y');

        $handler.data('start-x', c_x);
        $handler.data('start-y', c_y);

        var diff_x = c_x - s_x;
        var diff_y = c_y - s_y;

        var $video_obj = $current_video.find('iframe, embed, video');

        var width = $video_obj.width();
        var height = $video_obj.height();
        if ($handler.hasClass('fr-hnw') || $handler.hasClass('fr-hsw')) {
          diff_x = 0 - diff_x;
        }

        if ($handler.hasClass('fr-hnw') || $handler.hasClass('fr-hne')) {
          diff_y = 0 - diff_y;
        }

        $video_obj.css('width', width + diff_x);
        $video_obj.css('height', height + diff_y);
        $video_obj.removeAttr('width');
        $video_obj.removeAttr('height');

        _repositionResizer();
      }
    }

    /**
     * Stop resize.
     */
    function _handlerMouseup (e) {
      // Check if resizer belongs to current instance.
      if (!editor.core.sameInstance($video_resizer)) return true;

      if ($handler && $current_video) {
        if (e) e.stopPropagation();
        $handler = null;
        $overlay.hide();
        _repositionResizer();
        _showEditPopup();

        editor.undo.saveStep();
      }
    }

    /**
     * Create resize handler.
     */
    function _getHandler (pos) {
      return '<div class="fr-handler fr-h' + pos + '"></div>';
    }

    function _resizeVideo (e, initPageX, direction, step) {
      e.pageX = initPageX;
      e.pageY = initPageX;
      _handlerMousedown.call(this, e);
      e.pageX = e.pageX + direction * Math.floor(Math.pow(1.1, step));
      e.pageY = e.pageY + direction * Math.floor(Math.pow(1.1, step));
      _handlerMousemove.call(this, e);
      _handlerMouseup.call(this, e);

      return step++;
    }

    /**
     * Init video resizer.
     */
    function _initResizer () {
      var doc;

      // No shared video resizer.
      if (!editor.shared.$video_resizer) {
        // Create shared video resizer.
        editor.shared.$video_resizer = $('<div class="fr-video-resizer"></div>');
        $video_resizer = editor.shared.$video_resizer;

        // Bind mousedown event shared.
        editor.events.$on($video_resizer, 'mousedown', function (e) {
          e.stopPropagation();
        }, true);

        // video resize is enabled.
        if (editor.opts.videoResize) {
          $video_resizer.append(_getHandler('nw') + _getHandler('ne') + _getHandler('sw') + _getHandler('se'));

          // Add video resizer overlay and set it.
          editor.shared.$vid_overlay = $('<div class="fr-video-overlay"></div>');
          $overlay = editor.shared.$vid_overlay;
          doc = $video_resizer.get(0).ownerDocument;
          $(doc).find('body').append($overlay);
        }
      } else {
        $video_resizer = editor.shared.$video_resizer;
        $overlay = editor.shared.$vid_overlay;

        editor.events.on('destroy', function () {
          $video_resizer.removeClass('fr-active').appendTo($('body'));
        }, true);
      }

      // Shared destroy.
      editor.events.on('shared.destroy', function () {
        $video_resizer.html('').removeData().remove();
        $video_resizer = null;

        if (editor.opts.videoResize) {
          $overlay.remove();
          $overlay = null;
        }
      }, true);

      // Window resize. Exit from edit.
      if (!editor.helpers.isMobile()) {
        editor.events.$on($(editor.o_win), 'resize.video', function () {
          _exitEdit(true);
        });
      }

      // video resize is enabled.
      if (editor.opts.videoResize) {
        doc = $video_resizer.get(0).ownerDocument;

        editor.events.$on($video_resizer, editor._mousedown, '.fr-handler', _handlerMousedown);
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
          if ($current_video) {
            var ctrlKey = navigator.userAgent.indexOf('Mac OS X') != -1 ? e.metaKey : e.ctrlKey;
            var keycode = e.which;

            if (keycode !== prevKey || e.timeStamp - prevTimestamp > 200) {
              step = 1; // Reset step. Known browser issue: Keyup does not trigger when ctrl is pressed.
            }

            // Increase video size.
            if ((keycode == $.FE.KEYCODE.EQUALS || (editor.browser.mozilla && keycode == $.FE.KEYCODE.FF_EQUALS)) && ctrlKey && !e.altKey) {
              step = _resizeVideo.call(this, e, 1, 1);
            }
            // Decrease video size.
            else if ((keycode == $.FE.KEYCODE.HYPHEN || (editor.browser.mozilla && keycode == $.FE.KEYCODE.FF_HYPHEN)) && ctrlKey && !e.altKey) {
              step = _resizeVideo.call(this, e, 2, -1);
            }

            // Save key code.
            prevKey = keycode;

            // Save timestamp.
            prevTimestamp = e.timeStamp;
          }
        });

        // Reset the step on key up event.
        editor.events.on('keyup', function () {
          step = 1;
        });
      }
    }

    /**
     * Reposition resizer.
     */
    function _repositionResizer () {
      if (!$video_resizer) _initResizer();

      (editor.$wp || $(editor.opts.scrollableContainer)).append($video_resizer);
      $video_resizer.data('instance', editor);

      var $video_obj = $current_video.find('iframe, embed, video');

      $video_resizer
        .css('top', (editor.opts.iframe ? $video_obj.offset().top - 1 : $video_obj.offset().top - editor.$wp.offset().top - 1) + editor.$wp.scrollTop())
        .css('left', (editor.opts.iframe ? $video_obj.offset().left - 1 : $video_obj.offset().left - editor.$wp.offset().left - 1) + editor.$wp.scrollLeft())
        .css('width', $video_obj.outerWidth())
        .css('height', $video_obj.height())
        .addClass('fr-active');
    }

    /**
     * Edit video.
     */
    var touchScroll;
    function _edit (e) {
      if (e && e.type == 'touchend' && touchScroll) {
        return true;
      }

      e.preventDefault();
      e.stopPropagation();

      if (editor.edit.isDisabled()) {
        return false;
      }

      // Hide resizer for other instances.
      for (var i = 0; i < $.FE.INSTANCES.length; i++) {
        if ($.FE.INSTANCES[i] != editor) {
          $.FE.INSTANCES[i].events.trigger('video.hideResizer');
        }
      }

      editor.toolbar.disable();

      // Hide keyboard.
      if (editor.helpers.isMobile()) {
        editor.events.disableBlur();
        editor.$el.blur();
        editor.events.enableBlur();
      }

      $current_video = $(this);
      $(this).addClass('fr-active');

      if (editor.opts.iframe) {
        editor.size.syncIframe();
      }

      _repositionResizer();
      _showEditPopup();

      editor.selection.clear();
      editor.button.bulkRefresh();

      editor.events.trigger('image.hideResizer');
    }

    /**
     * Exit edit.
     */
    function _exitEdit (force_exit) {
      if ($current_video && (_canExit() || force_exit === true)) {
        $video_resizer.removeClass('fr-active');

        editor.toolbar.enable();

        $current_video.removeClass('fr-active');
        $current_video = null;

        _unmarkExit();
      }
    }

    editor.shared.vid_exit_flag = false;
    function _markExit () {
      editor.shared.vid_exit_flag = true;
    }

    function _unmarkExit () {
      editor.shared.vid_exit_flag = false;
    }

    function _canExit () {
      return editor.shared.vid_exit_flag;
    }

    /**
     * Init the video events.
     */
    function _initEvents () {
      editor.events.on('mousedown window.mousedown', _markExit);
      editor.events.on('window.touchmove', _unmarkExit);
      editor.events.on('mouseup window.mouseup', _exitEdit);

      editor.events.on('commands.mousedown', function ($btn) {
        if ($btn.parents('.fr-toolbar').length > 0) {
          _exitEdit();
        }
      });

      editor.events.on('blur video.hideResizer commands.undo commands.redo element.dropped', function () {
        _exitEdit(true);
      });
    }

    /**
     * Init the video edit popup.
     */
    function _initEditPopup () {
      // Image buttons.
      var video_buttons = '';
      if (editor.opts.videoEditButtons.length > 0) {
        video_buttons += '<div class="fr-buttons">';
        video_buttons += editor.button.buildList(editor.opts.videoEditButtons);
        video_buttons += '</div>';

        var template = {
          buttons: video_buttons
        }

        var $popup = editor.popups.create('video.edit', template);

        editor.events.$on(editor.$wp, 'scroll.video-edit', function () {
          if ($current_video && editor.popups.isVisible('video.edit')) {
            _showEditPopup();
          }
        });

        return $popup;
      }

      return false;
    }

    /**
     * Refresh the size popup.
     */
    function _refreshSizePopup () {
      if ($current_video) {
        var $popup = editor.popups.get('video.size');
        var $video_obj = $current_video.find('iframe, embed, video')
        $popup.find('input[name="width"]').val($video_obj.get(0).style.width || $video_obj.attr('width')).trigger('change');
        $popup.find('input[name="height"]').val($video_obj.get(0).style.height || $video_obj.attr('height')).trigger('change');
      }
    }

    /**
     * Show the size popup.
     */
    function showSizePopup () {
      var $popup = editor.popups.get('video.size');
      if (!$popup) $popup = _initSizePopup();

      editor.popups.refresh('video.size');
      editor.popups.setContainer('video.size', $(editor.opts.scrollableContainer));
      var $video_obj = $current_video.find('iframe, embed, video')
      var left = $video_obj.offset().left + $video_obj.width() / 2;
      var top = $video_obj.offset().top + $video_obj.height();

      editor.popups.show('video.size', left, top, $video_obj.height());
    }

    /**
     * Init the image upload popup.
     */
    function _initSizePopup (delayed) {
      if (delayed) {
        editor.popups.onRefresh('video.size', _refreshSizePopup);

        return true;
      }

      // Image buttons.
      var video_buttons = '';
      video_buttons = '<div class="fr-buttons">' + editor.button.buildList(editor.opts.videoSizeButtons) + '</div>';

      // Size layer.
      var size_layer = '';
      size_layer = '<div class="fr-video-size-layer fr-layer fr-active" id="fr-video-size-layer-' + editor.id + '"><div class="fr-video-group"><div class="fr-input-line"><input id="fr-video-size-layer-width-' + editor.id + '" type="text" name="width" placeholder="' + editor.language.translate('Width') + '" tabIndex="1"></div><div class="fr-input-line"><input id="fr-video-size-layer-height-' + editor.id + '" type="text" name="height" placeholder="' + editor.language.translate('Height') + '" tabIndex="1"></div></div><div class="fr-action-buttons"><button type="button" class="fr-command fr-submit" data-cmd="videoSetSize" tabIndex="2" role="button">' + editor.language.translate('Update') + '</button></div></div>';

      var template = {
        buttons: video_buttons,
        size_layer: size_layer
      }

      // Set the template in the popup.
      var $popup = editor.popups.create('video.size', template);

      editor.events.$on(editor.$wp, 'scroll', function () {
        if ($current_video && editor.popups.isVisible('video.size')) {
          showSizePopup();
        }
      });

      return $popup;
    }

    /**
     * Align image.
     */
    function align (val) {
      $current_video.removeClass('fr-fvr fr-fvl');
      if (val == 'left') {
        $current_video.addClass('fr-fvl');
      }
      else if (val == 'right') {
        $current_video.addClass('fr-fvr');
      }

      _repositionResizer();
      _showEditPopup();
    }

    /**
     * Refresh the align icon.
     */
    function refreshAlign ($btn) {
      if (!$current_video) return false;

      if ($current_video.hasClass('fr-fvl')) {
        $btn.find('> *:first').replaceWith(editor.icon.create('align-left'));
      }
      else if ($current_video.hasClass('fr-fvr')) {
        $btn.find('> *:first').replaceWith(editor.icon.create('align-right'));
      }
      else {
        $btn.find('> *:first').replaceWith(editor.icon.create('align-justify'));
      }
    }

    /**
     * Refresh the align option from the dropdown.
     */
    function refreshAlignOnShow ($btn, $dropdown) {
      var alignment = 'justify';
      if ($current_video.hasClass('fr-fvl')) {
        alignment = 'left';
      }
      else if ($current_video.hasClass('fr-fvr')) {
        alignment = 'right';
      }

      $dropdown.find('.fr-command[data-param1="' + alignment + '"]').addClass('fr-active').attr('aria-selected', true);
    }

    /**
     * Align image.
     */
    function display (val) {
      $current_video.removeClass('fr-dvi fr-dvb');
      if (val == 'inline') {
        $current_video.addClass('fr-dvi');
      }
      else if (val == 'block') {
        $current_video.addClass('fr-dvb');
      }

      _repositionResizer();
      _showEditPopup();
    }

    /**
     * Refresh the image display selected option.
     */
    function refreshDisplayOnShow ($btn, $dropdown) {
      var d = 'block';
      if ($current_video.hasClass('fr-dvi')) {
        d = 'inline';
      }

      $dropdown.find('.fr-command[data-param1="' + d + '"]').addClass('fr-active').attr('aria-selected', true);
    }

    /**
     * Remove current selected video.
     */
    function remove () {
      if ($current_video) {
        if (editor.events.trigger('video.beforeRemove', [$current_video]) !== false) {
          var $video = $current_video;
          editor.popups.hideAll();
          _exitEdit(true);

          editor.selection.setBefore($video.get(0)) || editor.selection.setAfter($video.get(0));
          $video.remove();
          editor.selection.restore();

          editor.html.fillEmptyBlocks();

          editor.events.trigger('video.removed', [$video]);
        }
      }
    }

    /**
     * Convert style to classes.
     */
    function _convertStyleToClasses ($video) {
      if (!$video.hasClass('fr-dvi') && !$video.hasClass('fr-dvb')) {
        var flt = $video.css('float');
        $video.css('float', 'none');
        if ($video.css('display') == 'block') {
          $video.css('float', flt);
          if (parseInt($video.css('margin-left'), 10) === 0 && ($video.attr('style') || '').indexOf('margin-right: auto') >= 0) {
            $video.addClass('fr-fvl');
          }
          else if (parseInt($video.css('margin-right'), 10) === 0 && ($video.attr('style') || '').indexOf('margin-left: auto') >= 0) {
            $video.addClass('fr-fvr');
          }

          $video.addClass('fr-dvb');
        }
        else {
          $video.css('float', flt);
          if ($video.css('float') == 'left') {
            $video.addClass('fr-fvl');
          }
          else if ($video.css('float') == 'right') {
            $video.addClass('fr-fvr');
          }

          $video.addClass('fr-dvi');
        }

        $video.css('margin', '');
        $video.css('float', '');
        $video.css('display', '');
        $video.css('z-index', '');
        $video.css('position', '');
        $video.css('overflow', '');
        $video.css('vertical-align', '');
      }

      if (!editor.opts.videoTextNear) {
        $video.removeClass('fr-dvi').addClass('fr-dvb');
      }
    }

    /**
     * Refresh video list.
     */
    function _refreshVideoList () {
      // Find possible candidates that are not wrapped.
      editor.$el.find('video').filter(function () {
        return $(this).parents('span.fr-video').length === 0;
      }).wrap('<span class="fr-video" contenteditable="false"></span>');

      editor.$el.find('embed, iframe').filter(function () {
        if (editor.browser.safari && this.getAttribute('src')) {
          this.setAttribute('src', this.src);
        }

        if ($(this).parents('span.fr-video').length > 0) return false;

        var link = $(this).attr('src');
        for (var i = 0; i < $.FE.VIDEO_PROVIDERS.length; i++) {
          var vp = $.FE.VIDEO_PROVIDERS[i];
          if (vp.test_regex.test(link)) {
            return true;
          }
        }
        return false;
      }).map(function () {
        return $(this).parents('object').length === 0 ? this : $(this).parents('object').get(0);
      }).wrap('<span class="fr-video" contenteditable="false"></span>');

      var videos = editor.$el.find('span.fr-video');
      for (var i = 0; i < videos.length; i++) {
        _convertStyleToClasses($(videos[i]));
      }

      videos.toggleClass('fr-draggable', editor.opts.videoMove);
    }

    function _init () {
      _initEvents();

      if (editor.helpers.isMobile()) {
        editor.events.$on(editor.$el, 'touchstart', 'span.fr-video', function () {
          touchScroll = false;
        })

        editor.events.$on(editor.$el, 'touchmove', function () {
          touchScroll = true;
        });
      }

      editor.events.on('html.set', _refreshVideoList);
      _refreshVideoList();

      editor.events.$on(editor.$el, 'mousedown', 'span.fr-video', function (e) {
        e.stopPropagation();
      })
      editor.events.$on(editor.$el, 'click touchend', 'span.fr-video', _edit);

      editor.events.on('keydown', function (e) {
        var key_code = e.which;
        if ($current_video && (key_code == $.FE.KEYCODE.BACKSPACE || key_code == $.FE.KEYCODE.DELETE)) {
          e.preventDefault();
          remove();
          return false;
        }

        if ($current_video && key_code == $.FE.KEYCODE.ESC) {
          _exitEdit(true);
          e.preventDefault();
          return false;
        }

        if ($current_video && key_code != $.FE.KEYCODE.F10 && !editor.keys.isBrowserAction(e)) {
          e.preventDefault();
          return false;
        }
      }, true);

      // ESC from accessibility.
      editor.events.on('toolbar.esc', function () {
        if ($current_video) {
          editor.events.disableBlur();
          editor.events.focus();
          return false;
        }
      }, true);

      // Make sure we don't leave empty tags.
      editor.events.on('keydown', function () {
        editor.$el.find('span.fr-video:empty').remove();
      })

      _initInsertPopup(true);
      _initSizePopup(true);
    }

    /**
     * Get back to the video main popup.
     */
    function back () {
      if ($current_video) {
        editor.events.disableBlur();
        $current_video.trigger('click');
      }
      else {
        editor.events.disableBlur();
        editor.selection.restore();
        editor.events.enableBlur();

        editor.popups.hide('video.insert');
        editor.toolbar.showInline();
      }
    }

    /**
     * Set size based on the current video size.
     */
    function setSize (width, height) {
      if ($current_video) {
        var $popup = editor.popups.get('video.size');
        var $video_obj = $current_video.find('iframe, embed, video');
        $video_obj.css('width', width || $popup.find('input[name="width"]').val());
        $video_obj.css('height', height || $popup.find('input[name="height"]').val());

        if ($video_obj.get(0).style.width) $video_obj.removeAttr('width');
        if ($video_obj.get(0).style.height) $video_obj.removeAttr('height');

        $popup.find('input:focus').blur();
        setTimeout(function () {
          $current_video.trigger('click');
        }, editor.helpers.isAndroid() ? 50 : 0);
      }
    }

    function get () {
      return $current_video;
    }

    return {
      _init: _init,
      showInsertPopup: showInsertPopup,
      showLayer: showLayer,
      refreshByURLButton: refreshByURLButton,
      refreshEmbedButton: refreshEmbedButton,
      insertByURL: insertByURL,
      insertEmbed: insertEmbed,
      insert: insert,
      align: align,
      refreshAlign: refreshAlign,
      refreshAlignOnShow: refreshAlignOnShow,
      display: display,
      refreshDisplayOnShow: refreshDisplayOnShow,
      remove: remove,
      showSizePopup: showSizePopup,
      back: back,
      setSize: setSize,
      get: get
    }
  }

  // Register the font size command.
  $.FE.RegisterCommand('insertVideo', {
    title: 'Insert Video',
    undo: false,
    focus: true,
    refreshAfterCallback: false,
    popup: true,
    callback: function () {
      if (!this.popups.isVisible('video.insert')) {
        this.video.showInsertPopup();
      }
      else {
        if (this.$el.find('.fr-marker').length) {
          this.events.disableBlur();
          this.selection.restore();
        }
        this.popups.hide('video.insert');
      }
    },
    plugin: 'video'
  })

  // Add the font size icon.
  $.FE.DefineIcon('insertVideo', {
    NAME: 'video-camera'
  });

  // Image by URL button inside the insert image popup.
  $.FE.DefineIcon('videoByURL', { NAME: 'link' });
  $.FE.RegisterCommand('videoByURL', {
    title: 'By URL',
    undo: false,
    focus: false,
    toggle: true,
    callback: function () {
      this.video.showLayer('video-by-url');
    },
    refresh: function ($btn) {
      this.video.refreshByURLButton($btn);
    }
  })

  // Image by URL button inside the insert image popup.
  $.FE.DefineIcon('videoEmbed', { NAME: 'code' });
  $.FE.RegisterCommand('videoEmbed', {
    title: 'Embedded Code',
    undo: false,
    focus: false,
    toggle: true,
    callback: function () {
      this.video.showLayer('video-embed');
    },
    refresh: function ($btn) {
      this.video.refreshEmbedButton($btn);
    }
  })

  $.FE.RegisterCommand('videoInsertByURL', {
    undo: true,
    focus: true,
    callback: function () {
      this.video.insertByURL();
    }
  })

  $.FE.RegisterCommand('videoInsertEmbed', {
    undo: true,
    focus: true,
    callback: function () {
      this.video.insertEmbed();
    }
  })

  // Image display.
  $.FE.DefineIcon('videoDisplay', { NAME: 'star' })
  $.FE.RegisterCommand('videoDisplay', {
    title: 'Display',
    type: 'dropdown',
    options: {
      inline: 'Inline',
      block: 'Break Text'
    },
    callback: function (cmd, val) {
      this.video.display(val);
    },
    refresh: function ($btn) {
      if (!this.opts.videoTextNear) $btn.addClass('fr-hidden');
    },
    refreshOnShow: function ($btn, $dropdown) {
      this.video.refreshDisplayOnShow($btn, $dropdown);
    }
  })

  // Image align.
  $.FE.DefineIcon('videoAlign', { NAME: 'align-center' })
  $.FE.RegisterCommand('videoAlign', {
    type: 'dropdown',
    title: 'Align',
    options: {
      left: 'Align Left',
      justify: 'None',
      right: 'Align Right'
    },
    html: function () {
      var c = '<ul class="fr-dropdown-list" role="presentation">';
      var options =  $.FE.COMMANDS.videoAlign.options;
      for (var val in options) {
        if (options.hasOwnProperty(val)) {
          c += '<li role="presentation"><a class="fr-command fr-title" tabIndex="-1" role="option" data-cmd="videoAlign" data-param1="' + val + '" title="' + this.language.translate(options[val]) + '">' + this.icon.create('align-' + val) + '<span class="fr-sr-only">' + this.language.translate(options[val]) + '</span></a></li>';
        }
      }
      c += '</ul>';

      return c;
    },
    callback: function (cmd, val) {
      this.video.align(val);
    },
    refresh: function ($btn) {
      this.video.refreshAlign($btn);
    },
    refreshOnShow: function ($btn, $dropdown) {
      this.video.refreshAlignOnShow($btn, $dropdown);
    }
  })

  // Video remove.
  $.FE.DefineIcon('videoRemove', { NAME: 'trash' })
  $.FE.RegisterCommand('videoRemove', {
    title: 'Remove',
    callback: function () {
      this.video.remove();
    }
  })

  // Video size.
  $.FE.DefineIcon('videoSize', { NAME: 'arrows-alt' })
  $.FE.RegisterCommand('videoSize', {
    undo: false,
    focus: false,
    popup: true,
    title: 'Change Size',
    callback: function () {
      this.video.showSizePopup();
    }
  });

  // Video back.
  $.FE.DefineIcon('videoBack', { NAME: 'arrow-left' });
  $.FE.RegisterCommand('videoBack', {
    title: 'Back',
    undo: false,
    focus: false,
    back: true,
    callback: function () {
      this.video.back();
    },
    refresh: function ($btn) {
      var $current_video = this.video.get();
      if (!$current_video && !this.opts.toolbarInline) {
        $btn.addClass('fr-hidden');
        $btn.next('.fr-separator').addClass('fr-hidden');
      }
      else {
        $btn.removeClass('fr-hidden');
        $btn.next('.fr-separator').removeClass('fr-hidden');
      }
    }
  });

  $.FE.RegisterCommand('videoSetSize', {
    undo: true,
    focus: false,
    title: 'Update',
    refreshAfterCallback: false,
    callback: function () {
      this.video.setSize();
    }
  })

}));
