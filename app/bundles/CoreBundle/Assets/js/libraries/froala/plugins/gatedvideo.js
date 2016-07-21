/*!
 * froala_editor v2.3.3 (https://www.froala.com/wysiwyg-editor)
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

  $.extend($.FE.POPUP_TEMPLATES, {
    'gatedvideo.insert': '[_BUTTONS_][_BY_URL_LAYER_]',
    'gatedvideo.edit': '[_BUTTONS_]',
    'gatedvideo.size': '[_BUTTONS_][_SIZE_LAYER_]'
  })

  $.extend($.FE.DEFAULTS, {
    gatedVideoInsertButtons: ['gatedVideoBack', '|', 'gatedVideoByURL'],
    gatedVideoEditButtons: ['gatedVideoDisplay', 'gatedVideoAlign', 'gatedVideoSize', 'gatedVideoRemove'],
    gatedVideoResize: true,
    gatedVideoSizeButtons: ['gatedVideoBack', '|'],
    gatedVideoSplitHTML: false,
    gatedVideoTextNear: true,
    gatedVideoDefaultAlign: 'center',
    gatedVideoDefaultDisplay: 'block',
    gatedVideoMove: true
  });

  $.FE.VIDEO_PROVIDERS = [
    {
      test_regex: /^.*((youtu.be)|(youtube.com))\/((v\/)|(\/u\/\w\/)|(embed\/)|(watch\?))?\??v?=?([^#\&\?]*).*/,
      provider: 'youtube',
      html: '<video width="640" height="360" data-form-id="{formId}" data-gate-time="{gateTime}"><source type="video/youtube" src="{url}"></video>'
    },
    {
      test_regex: /^.*(vimeo\.com\/)((channels\/[A-z]+\/)|(groups\/[A-z]+\/videos\/))?([0-9]+)/,
      provider: 'vimeo',
      html: '<video width="640" height="360" data-form-id="{formId}" data-gate-time="{gateTime}"><source type="video/vimdeo" src="{url}"></video>'
    }
  ];

  $.FE.GATED_VIDEO_EMBED_REGEX = /^\W*(<video.*><\/video>)\W*$/i;

  $.FE.PLUGINS.gatedVideo = function (editor) {
    var $overlay;
    var $handler;
    var $video_resizer;
    var $current_video;

    /**
     * Refresh the image insert popup.
     */
    function _refreshInsertPopup () {
      var $popup = editor.popups.get('gatedvideo.insert');

      var $url_input = $popup.find('.fr-gatedvideo-by-url-layer input');
      $url_input.val('').trigger('change');
    }

    /**
     * Show the video insert popup.
     */
    function showInsertPopup () {
      var $btn = editor.$tb.find('.fr-command[data-cmd="insertGatedVideo"]');

      var $popup = editor.popups.get('gatedvideo.insert');
      if (!$popup) $popup = _initInsertPopup();

      if (!$popup.hasClass('fr-active')) {
        editor.popups.refresh('gatedvideo.insert');
        editor.popups.setContainer('gatedvideo.insert', editor.$tb);

        var left = $btn.offset().left + $btn.outerWidth() / 2;
        var top = $btn.offset().top + (editor.opts.toolbarBottom ? 10 : $btn.outerHeight() - 10);
        editor.popups.show('gatedvideo.insert', left, top, $btn.outerHeight());
      }
    }

    /**
     * Show the image edit popup.
     */
    function _showEditPopup () {
      var $popup = editor.popups.get('gatedvideo.edit');
      if (!$popup) $popup = _initEditPopup();

      editor.popups.setContainer('gatedvideo.edit', $(editor.opts.scrollableContainer));
      editor.popups.refresh('gatedvideo.edit');

      var $video_obj = $current_video.find('gatedVideo');
      var left = $video_obj.offset().left + $video_obj.outerWidth() / 2;
      var top = $video_obj.offset().top + $video_obj.outerHeight();

      editor.popups.show('gatedvideo.edit', left, top, $video_obj.outerHeight());
    }

    function _initInsertPopup (delayed) {
      if (delayed) {
        editor.popups.onRefresh('gatedvideo.insert', _refreshInsertPopup);

        return true;
      }

      // Image buttons.
      var video_buttons = '';
      if (editor.opts.gatedVideoInsertButtons.length > 1) {
        video_buttons = '<div class="fr-buttons">' + editor.button.buildList(editor.opts.gatedVideoInsertButtons) + '</div>';
    }

      // Video by url layer.
      var by_url_layer = '';
      if (editor.opts.gatedVideoInsertButtons.indexOf('gatedVideoByURL') >= 0) {
        by_url_layer = '<div class="fr-gatedvideo-by-url-layer fr-layer fr-active" id="fr-gatedvideo-by-url-layer-' + editor.id + '">' +
            '<div class="fr-input-line"><input type="text" name="url" placeholder="Video URL" tabIndex="1"></div>' +
            '<div class="fr-input-line"><input type="text" name="gateTime" placeholder="' + editor.language.translate('Gate Time (in seconds)') + '" value="15" tabIndex="2"></div>' +
            '<div class="fr-input-line"><label>' + editor.language.translate('Form') + '</label>' +
            '<select name="formId" tabIndex="3" style="width:100%"><option value="0">' + editor.language.translate('Please select a form.') + '</option>';

        for (var i = 0; mauticForms[i]; i++) {
          by_url_layer += '<option value="' + mauticForms[i].value + '">' + mauticForms[i].label + '</option>';
        }

        by_url_layer += '</select></div>' +
            '<div class="fr-action-buttons"><button type="button" class="fr-command fr-submit" data-cmd="gatedVideoInsertByURL" tabIndex="2">' + editor.language.translate('Insert') + '</button></div></div>'
      }

      var template = {
        buttons: video_buttons,
        by_url_layer: by_url_layer
      }

      // Set the template in the popup.
      var $popup = editor.popups.create('gatedvideo.insert', template);

      return $popup;
    }

    /**
     * Show the image upload layer.
     */
    function showLayer (name) {
      var $popup = editor.popups.get('gatedvideo.insert');

      var left;
      var top;
      if (!$current_video && !editor.opts.toolbarInline) {
        var $btn = editor.$tb.find('.fr-command[data-cmd="insertGatedVideo"]');
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

      editor.popups.show('gatedvideo.insert', left, top, 0);
    }

    /**
     * Refresh the insert by url button.
     */
    function refreshByURLButton ($btn) {
      var $popup = editor.popups.get('gatedvideo.insert');
      if ($popup.find('.fr-gatedvideo-by-url-layer').hasClass('fr-active')) {
        $btn.addClass('fr-active');
      }
    }

    /**
     * Refresh the insert embed button.
     */
    function refreshEmbedButton ($btn) {
      var $popup = editor.popups.get('gatedvideo.insert');
      if ($popup.find('.fr-gatedvideo-embed-layer').hasClass('fr-active')) {
        $btn.addClass('fr-active');
      }
    }

    /**
     * Insert video embedded object.
     */
    function insert (embedded_code) {
      // Make sure we have focus.
      editor.events.focus(true);
      editor.selection.restore();

      editor.html.insert('<span contenteditable="false" draggable="true" class="fr-jiv fr-gatedvideo fr-dv' + (editor.opts.gatedVideoDefaultDisplay[0]) + (editor.opts.gatedVideoDefaultAlign != 'center' ? ' fr-fv' + editor.opts.gatedVideoDefaultAlign[0] : '') + '">' + embedded_code + '</span>', false, editor.opts.gatedVideoSplitHTML);

      editor.popups.hide('gatedvideo.insert');

      var $video = editor.$el.find('.fr-jiv');
      $video.removeClass('fr-jiv');

      $video.toggleClass('fr-draggable', editor.opts.gatedVideoMove);

      editor.events.trigger('gatedvideo.inserted', [$video]);
    }

    /**
     * Insert video by URL.
     */
    function insertByURL (link, gateTime, formId) {
      var $popup = editor.popups.get('gatedvideo.insert');

      if (typeof link == 'undefined') {
        link = $popup.find('.fr-gatedvideo-by-url-layer input[name="url"]').val() || '';
      }

      if (typeof gateTime == 'undefined') {
        gateTime = $popup.find('.fr-gatedvideo-by-url-layer input[name="gateTime"]').val() || '';
      }

      if (typeof formId == 'undefined') {
        formId = $popup.find('.fr-gatedvideo-by-url-layer select[name="formId"]').val() || '';
      }

      var video = null;
      if (editor.helpers.isURL(link)) {
        for (var i = 0; i < $.FE.VIDEO_PROVIDERS.length; i++) {
          var vp = $.FE.VIDEO_PROVIDERS[i];
          if (vp.test_regex.test(link)) {
            video = link.replace(vp.url_regex, vp.url_text);
            video = vp.html.replace('{url}', video).replace('{formId}', formId).replace('{gateTime}', gateTime);
            break;
          }
        }
      }

      if (video) {
        insert(video);
      }
      else {
        editor.events.trigger('gatedvideo.linkError', [link]);
      }
    }

    /**
     * Insert embedded video.
     */
    function insertEmbed (code) {
      if (typeof code == 'undefined') {
        var $popup = editor.popups.get('gatedvideo.insert');
        code = $popup.find('.fr-gatedvideo-embed-layer textarea').val() || '';
      }

      if (code.length === 0 || !$.FE.GATED_VIDEO_EMBED_REGEX.test(code)) {
        editor.events.trigger('gatedvideo.codeError', [code]);
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

        var $video_obj = $current_video.find('video');

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

    /**
     * Init video resizer.
     */
    function _initResizer () {
      var doc;

      // No shared video resizer.
      if (!editor.shared.$video_resizer) {
        // Create shared video resizer.
        editor.shared.$video_resizer = $('<div class="fr-gatedvideo-resizer"></div>');
        $video_resizer = editor.shared.$video_resizer;

        // Bind mousedown event shared.
        editor.events.$on($video_resizer, 'mousedown', function (e) {
          e.stopPropagation();
        }, true);

        // video resize is enabled.
        if (editor.opts.gatedVideoResize) {
          $video_resizer.append(_getHandler('nw') + _getHandler('ne') + _getHandler('sw') + _getHandler('se'));

          // Add video resizer overlay and set it.
          editor.shared.$vid_overlay = $('<div class="fr-gatedvideo-overlay"></div>');
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

        if (editor.opts.gatedVideoResize) {
          $overlay.remove();
          $overlay = null;
        }
      }, true);

      // Window resize. Exit from edit.
      if (!editor.helpers.isMobile()) {
        editor.events.$on($(editor.o_win), 'resize.gatedVideo', function () {
          _exitEdit(true);
        });
      }

      // video resize is enabled.
      if (editor.opts.gatedVideoResize) {
        doc = $video_resizer.get(0).ownerDocument;

        editor.events.$on($video_resizer, editor._mousedown, '.fr-handler', _handlerMousedown);
        editor.events.$on($(doc), editor._mousemove, _handlerMousemove);
        editor.events.$on($(doc.defaultView || doc.parentWindow), editor._mouseup, _handlerMouseup);

        editor.events.$on($overlay, 'mouseleave', _handlerMouseup);
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
        .addClass('fr-active')
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
          $.FE.INSTANCES[i].events.trigger('gatedvideo.hideResizer');
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
      if (editor.opts.gatedVideoEditButtons.length >= 1) {
        video_buttons += '<div class="fr-buttons">';
        video_buttons += editor.button.buildList(editor.opts.gatedVideoEditButtons);
        video_buttons += '</div>';
      }

      var template = {
        buttons: video_buttons
      }

      var $popup = editor.popups.create('gatedvideo.edit', template);

      editor.events.$on(editor.$wp, 'scroll.gatedVideo-edit', function () {
        if ($current_video && editor.popups.isVisible('gatedvideo.edit')) {
          _showEditPopup();
        }
      });

      return $popup;
    }

    /**
     * Refresh the size popup.
     */
    function _refreshSizePopup () {
      if ($current_video) {
        var $popup = editor.popups.get('gatedvideo.size');
        var $video_obj = $current_video.find('iframe, embed, video')
        $popup.find('input[name="width"]').val($video_obj.get(0).style.width || $video_obj.attr('width')).trigger('change');
        $popup.find('input[name="height"]').val($video_obj.get(0).style.height || $video_obj.attr('height')).trigger('change');
      }
    }

    /**
     * Show the size popup.
     */
    function showSizePopup () {
      var $popup = editor.popups.get('gatedvideo.size');
      if (!$popup) $popup = _initSizePopup();

      editor.popups.refresh('gatedvideo.size');
      editor.popups.setContainer('gatedvideo.size', $(editor.opts.scrollableContainer));
      var $video_obj = $current_video.find('iframe, embed, video')
      var left = $video_obj.offset().left + $video_obj.width() / 2;
      var top = $video_obj.offset().top + $video_obj.height();

      editor.popups.show('gatedvideo.size', left, top, $video_obj.height());
    }

    /**
     * Init the image upload popup.
     */
    function _initSizePopup (delayed) {
      if (delayed) {
        editor.popups.onRefresh('gatedvideo.size', _refreshSizePopup);

        return true;
      }

      // Image buttons.
      var video_buttons = '';
      video_buttons = '<div class="fr-buttons">' + editor.button.buildList(editor.opts.gatedVideoSizeButtons) + '</div>';

      // Size layer.
      var size_layer = '';
      size_layer = '<div class="fr-gatedvideo-size-layer fr-layer fr-active" id="fr-gatedvideo-size-layer-' + editor.id + '"><div class="fr-gatedvideo-group"><div class="fr-input-line"><input type="text" name="width" placeholder="' + editor.language.translate('Width') + '" tabIndex="1"></div><div class="fr-input-line"><input type="text" name="height" placeholder="' + editor.language.translate('Height') + '" tabIndex="1"></div></div><div class="fr-action-buttons"><button type="button" class="fr-command fr-submit" data-cmd="videoSetSize" tabIndex="2">' + editor.language.translate('Update') + '</button></div></div>';

      var template = {
        buttons: video_buttons,
        size_layer: size_layer
      }

      // Set the template in the popup.
      var $popup = editor.popups.create('gatedvideo.size', template);

      editor.events.$on(editor.$wp, 'scroll', function () {
        if ($current_video && editor.popups.isVisible('gatedvideo.size')) {
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

      $dropdown.find('.fr-command[data-param1="' + alignment + '"]').addClass('fr-active');
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

      $dropdown.find('.fr-command[data-param1="' + d + '"]').addClass('fr-active');
    }

    /**
     * Remove current selected video.
     */
    function remove () {
      if ($current_video) {
        if (editor.events.trigger('gatedvideo.beforeRemove', [$current_video]) !== false) {
          var $video = $current_video;
          editor.popups.hideAll();
          _exitEdit(true);

          editor.selection.setBefore($video.get(0)) || editor.selection.setAfter($video.get(0));
          $video.remove();
          editor.selection.restore();

          editor.html.fillEmptyBlocks();

          editor.events.trigger('gatedvideo.removed', [$video]);
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

      if (!editor.opts.gatedVideoTextNear) {
        $video.removeClass('fr-dvi').addClass('fr-dvb');
      }
    }

    /**
     * Refresh video list.
     */
    function _refreshVideoList () {
      // Find possible candidates that are not wrapped.
      editor.$el.find('gatedVideo').filter(function () {
        return $(this).parents('span.fr-gatedvideo').length === 0;
      }).wrap('<span class="fr-gatedvideo" contenteditable="false"></span>');

      editor.$el.find('embed, iframe').filter(function () {
        if (editor.browser.safari && this.getAttribute('src')) {
          this.setAttribute('src', this.src);
        }

        if ($(this).parents('span.fr-gatedvideo').length > 0) return false;

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
      }).wrap('<span class="fr-gatedvideo" contenteditable="false"></span>');

      var videos = editor.$el.find('span.fr-gatedvideo');
      for (var i = 0; i < videos.length; i++) {
        _convertStyleToClasses($(videos[i]));
      }

      videos.toggleClass('fr-draggable', editor.opts.gatedVideoMove);
    }

    function _init () {
      _initEvents();

      if (editor.helpers.isMobile()) {
        editor.events.$on(editor.$el, 'touchstart', 'span.fr-gatedvideo', function () {
          touchScroll = false;
        })

        editor.events.$on(editor.$el, 'touchmove', function () {
          touchScroll = true;
        });
      }

      editor.events.on('html.set', _refreshVideoList);
      _refreshVideoList();

      editor.events.$on(editor.$el, 'mousedown', 'span.fr-gatedvideo', function (e) {
        e.stopPropagation();
      })
      editor.events.$on(editor.$el, 'click touchend', 'span.fr-gatedvideo', _edit);

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

        if ($current_video && !editor.keys.ctrlKey(e)) {
          e.preventDefault();
          return false;
        }
      }, true);

      // Make sure we don't leave empty tags.
      editor.events.on('keydown', function () {
        editor.$el.find('span.fr-gatedvideo:empty').remove();
      })

      _initInsertPopup(true);
      _initSizePopup(true);
    }

    /**
     * Get back to the video main popup.
     */
    function back () {
      if ($current_video) {
        $current_video.trigger('click');
      }
      else {
        editor.events.disableBlur();
        editor.selection.restore();
        editor.events.enableBlur();

        editor.popups.hide('gatedvideo.insert');
        editor.toolbar.showInline();
      }
    }

    /**
     * Set size based on the current video size.
     */
    function setSize (width, height) {
      if ($current_video) {
        var $popup = editor.popups.get('gatedvideo.size');
        var $video_obj = $current_video.find('iframe, embed, video');
        $video_obj.css('width', width || $popup.find('input[name="width"]').val());
        $video_obj.css('height', height || $popup.find('input[name="height"]').val());

        if ($video_obj.get(0).style.width) $video_obj.removeAttr('width');
        if ($video_obj.get(0).style.height) $video_obj.removeAttr('height');

        $popup.find('input').blur();
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
  $.FE.RegisterCommand('insertGatedVideo', {
    title: 'Insert Video',
    undo: false,
    focus: true,
    refreshAfterCallback: false,
    popup: true,
    callback: function () {
      if (!this.popups.isVisible('gatedvideo.insert')) {
        this.gatedVideo.showInsertPopup();
      }
      else {
        if (this.$el.find('.fr-marker')) {
          this.events.disableBlur();
          this.selection.restore();
        }
        this.popups.hide('gatedvideo.insert');
      }
    },
    plugin: 'gatedVideo'
  })

  // Add the font size icon.
  $.FE.DefineIcon('insertGatedVideo', {
    NAME: 'video-camera'
  });

  // Image by URL button inside the insert image popup.
  $.FE.DefineIcon('gatedVideoByURL', { NAME: 'link' });
  $.FE.RegisterCommand('gatedVideoByURL', {
    title: 'By URL',
    undo: false,
    focus: false,
    callback: function () {
      this.gatedVideo.showLayer('gatedVideo-by-url');
    },
    refresh: function ($btn) {
      this.gatedVideo.refreshByURLButton($btn);
    }
  })

  $.FE.RegisterCommand('gatedVideoInsertByURL', {
    undo: true,
    focus: true,
    callback: function () {
      this.gatedVideo.insertByURL();
    }
  })

  // Image display.
  $.FE.DefineIcon('gatedVideoDisplay', { NAME: 'star' })
  $.FE.RegisterCommand('gatedVideoDisplay', {
    title: 'Display',
    type: 'dropdown',
    options: {
      inline: 'Inline',
      block: 'Break Text'
    },
    callback: function (cmd, val) {
      this.gatedVideo.display(val);
    },
    refresh: function ($btn) {
      if (!this.opts.gatedVideoTextNear) $btn.addClass('fr-hidden');
    },
    refreshOnShow: function ($btn, $dropdown) {
      this.gatedVideo.refreshDisplayOnShow($btn, $dropdown);
    }
  })

  // Image align.
  $.FE.DefineIcon('gatedVideoAlign', { NAME: 'align-center' })
  $.FE.RegisterCommand('gatedVideoAlign', {
    type: 'dropdown',
    title: 'Align',
    options: {
      left: 'Align Left',
      justify: 'None',
      right: 'Align Right'
    },
    html: function () {
      var c = '<ul class="fr-dropdown-list">';
      var options =  $.FE.COMMANDS.gatedVideoAlign.options;
      for (var val in options) {
        if (options.hasOwnProperty(val)) {
          c += '<li><a class="fr-command fr-title" data-cmd="videoAlign" data-param1="' + val + '" title="' + this.language.translate(options[val]) + '">' + this.icon.create('align-' + val) + '</a></li>';
        }
      }
      c += '</ul>';

      return c;
    },
    callback: function (cmd, val) {
      this.gatedVideo.align(val);
    },
    refresh: function ($btn) {
      this.gatedVideo.refreshAlign($btn);
    },
    refreshOnShow: function ($btn, $dropdown) {
      this.gatedVideo.refreshAlignOnShow($btn, $dropdown);
    }
  })

  // Video remove.
  $.FE.DefineIcon('gatedVideoRemove', { NAME: 'trash' })
  $.FE.RegisterCommand('gatedVideoRemove', {
    title: 'Remove',
    callback: function () {
      this.gatedVideo.remove();
    }
  })

  // Video size.
  $.FE.DefineIcon('gatedVideoSize', { NAME: 'arrows-alt' })
  $.FE.RegisterCommand('gatedVideoSize', {
    undo: false,
    focus: false,
    title: 'Change Size',
    callback: function () {
      this.gatedVideo.showSizePopup();
    }
  });

  // Video back.
  $.FE.DefineIcon('gatedVideoBack', { NAME: 'arrow-left' });
  $.FE.RegisterCommand('gatedVideoBack', {
    title: 'Back',
    undo: false,
    focus: false,
    back: true,
    callback: function () {
      this.gatedVideo.back();
    },
    refresh: function ($btn) {
      var $current_video = this.gatedVideo.get();
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

  $.FE.RegisterCommand('gatedVideoSetSize', {
    undo: true,
    focus: false,
    callback: function () {
      this.gatedVideo.setSize();
    }
  })

}));
