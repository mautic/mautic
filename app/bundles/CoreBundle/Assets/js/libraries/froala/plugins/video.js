/*!
 * froala_editor v2.4.2 (https://www.froala.com/wysiwyg-editor)
 * License https://froala.com/wysiwyg-editor/terms/
 * Copyright 2014-2017 Froala Labs
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
    'video.insert': '[_BUTTONS_][_BY_URL_LAYER_][_EMBED_LAYER_][_UPLOAD_LAYER_][_PROGRESS_BAR_]',
    'video.edit': '[_BUTTONS_]',
    'video.size': '[_BUTTONS_][_SIZE_LAYER_]'
  })

  $.extend($.FE.DEFAULTS, {
    videoAllowedTypes: ['mp4', 'webm', 'ogg'],
    videoDefaultAlign: 'center',
    videoDefaultDisplay: 'block',
    videoDefaultWidth: 600,
    videoEditButtons: ['videoReplace', 'videoRemove', '|', 'videoDisplay', 'videoAlign', 'videoSize'],
    videoInsertButtons: ['videoBack', '|', 'videoByURL', 'videoEmbed', 'videoUpload'],
    videoMaxSize: 50 * 1024 * 1024,
    videoMove: true,
    videoResize: true,
    videoSizeButtons: ['videoBack', '|'],
    videoSplitHTML: false,
    videoTextNear: true,
    videoUploadMethod: 'POST',
    videoUploadParam: 'file',
    videoUploadParams: {},
    videoUploadToS3: false,
    videoUploadURL: 'https://i.froala.com/upload'
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

    var BAD_LINK = 1;
    var MISSING_LINK = 2;
    var ERROR_DURING_UPLOAD = 3;
    var BAD_RESPONSE = 4;
    var MAX_SIZE_EXCEEDED = 5;
    var BAD_FILE_TYPE = 6;
    var NO_CORS_IE = 7;

    var error_messages = {};
    error_messages[BAD_LINK] = 'Video cannot be loaded from the passed link.',
    error_messages[MISSING_LINK] = 'No link in upload response.',
    error_messages[ERROR_DURING_UPLOAD] = 'Error during file upload.',
    error_messages[BAD_RESPONSE] = 'Parsing response failed.',
    error_messages[MAX_SIZE_EXCEEDED] = 'File is too large.',
    error_messages[BAD_FILE_TYPE] = 'Video file type is invalid.',
    error_messages[NO_CORS_IE] = 'Files can be uploaded only to same domain in IE 8 and IE 9.'

    /**
     * Refresh the video insert popup.
     */
    function _refreshInsertPopup () {
      var $popup = editor.popups.get('video.insert');

      var $url_input = $popup.find('.fr-video-by-url-layer input');
      $url_input.val('').trigger('change');

      var $embed_area = $popup.find('.fr-video-embed-layer textarea');
      $embed_area.val('').trigger('change');

      $embed_area = $popup.find('.fr-video-upload-layer input');
      $embed_area.val('').trigger('change');
    }

    /**
     * Show the video insert popup.
     */
    function showInsertPopup () {
      var $btn = editor.$tb.find('.fr-command[data-cmd="insertVideo"]');

      var $popup = editor.popups.get('video.insert');
      if (!$popup) $popup = _initInsertPopup();

      hideProgressBar();
      if (!$popup.hasClass('fr-active')) {
        editor.popups.refresh('video.insert');
        editor.popups.setContainer('video.insert', editor.$tb);

        var left = $btn.offset().left + $btn.outerWidth() / 2;
        var top = $btn.offset().top + (editor.opts.toolbarBottom ? 10 : $btn.outerHeight() - 10);
        editor.popups.show('video.insert', left, top, $btn.outerHeight());
      }
    }

    /**
     * Show the video edit popup.
     */
    function _showEditPopup () {
      var $popup = editor.popups.get('video.edit');
      if (!$popup) $popup = _initEditPopup();

      if ($popup) {
        editor.popups.setContainer('video.edit', editor.$sc);
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
        editor.popups.onHide('image.insert', _hideInsertPopup);

        return true;
      }

      // Video buttons.
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

      // Video upload layer.
      var upload_layer = '';
      if (editor.opts.videoInsertButtons.indexOf('videoUpload') >= 0) {
        upload_layer = '<div class="fr-video-upload-layer fr-layer" id="fr-video-upload-layer-' + editor.id + '"><strong>' + editor.language.translate('Drop video') + '</strong><br>(' + editor.language.translate('or click') + ')<div class="fr-form"><input type="file" accept="video/' + editor.opts.videoAllowedTypes.join(', video/').toLowerCase() + '" tabIndex="-1" aria-labelledby="fr-video-upload-layer-' + editor.id + '" role="button"></div></div>'
      }

      // Progress bar.
      var progress_bar_layer = '<div class="fr-video-progress-bar-layer fr-layer"><h3 tabIndex="-1" class="fr-message">Uploading</h3><div class="fr-loader"><span class="fr-progress"></span></div><div class="fr-action-buttons"><button type="button" class="fr-command fr-dismiss" data-cmd="videoDismissError" tabIndex="2" role="button">OK</button></div></div>';


      var template = {
        buttons: video_buttons,
        by_url_layer: by_url_layer,
        embed_layer: embed_layer,
        upload_layer: upload_layer,
        progress_bar: progress_bar_layer
      }

      // Set the template in the popup.
      var $popup = editor.popups.create('video.insert', template);

      _bindInsertEvents($popup);

      return $popup;
    }

    /**
     * Show the video upload layer.
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
     * Refresh the insert upload button.
     */
    function refreshUploadButton ($btn) {
      var $popup = editor.popups.get('video.insert');
      if ($popup.find('.fr-video-upload-layer').hasClass('fr-active')) {
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

      // Flag to tell if the video is replaced.
      var replaced = false;

      // If current video found we have to replace it.
      if ($current_video) {

        // Remove the old video.
        remove();

        // Mark that the video is replaced.
        replaced = true;
      }

      editor.html.insert('<span contenteditable="false" draggable="true" class="fr-jiv fr-video">' + embedded_code + '</span>', false, editor.opts.videoSplitHTML);

      editor.popups.hide('video.insert');

      var $video = editor.$el.find('.fr-jiv');
      $video.removeClass('fr-jiv');

      _setStyle($video, editor.opts.videoDefaultDisplay, editor.opts.videoDefaultAlign);

      $video.toggleClass('fr-draggable', editor.opts.videoMove);

      editor.events.trigger(replaced ? 'video.replaced' : 'video.inserted', [$video]);
    }

    function _loadedCallback () {
      var $video = $(this);

      editor.popups.hide('video.insert');

      $video.removeClass('fr-uploading');

      // Select the image.
      if ($video.parent().next().is('br')) {
        $video.parent().next().remove();
      }

      _editVideo($video.parent());

      editor.events.trigger('video.loaded', [$video.parent()]);
    }

    /**
     * Insert html video into the editor.
     */

    function insertHtmlVideo (link, sanitize, data, $existing_video, response) {
      editor.edit.off();
      _setProgressMessage('Loading video');

      if (sanitize) link = editor.helpers.sanitizeURL(link);

      var video = document.createElement('video');

      video.oncanplay = function () {
        var $video;
        var attr;

        if ($existing_video) {
          if (!editor.undo.canDo() && !$existing_video.find('video').hasClass('fr-uploading')) editor.undo.saveStep();

          var old_src = $existing_video.find('video').data('fr-old-src');

          var replaced = $existing_video.data('fr-replaced');
          $existing_video.data('fr-replaced', false);

          if (editor.$wp) {
            // Clone existing video.
            $video = $existing_video.clone();
            $video.find('video').removeData('fr-old-src').removeClass('fr-uploading');

            // Remove load event.
            $video.find('video').off('canplay');

            // Set new SRC.
            if (old_src) $existing_video.find('video').attr('src', old_src);

            // Replace existing video with its clone.
            $existing_video.replaceWith($video);
          } else {
            $video = $existing_video;
          }

          // Remove old data.
          var atts = $video.find('video').get(0).attributes;
          for (var i = 0; i < atts.length; i++) {
            var att = atts[i];
            if (att.nodeName.indexOf('data-') === 0) {
              $video.find('video').removeAttr(att.nodeName);
            }
          }

          // Set new data.
          if (typeof data != 'undefined') {
            for (attr in data) {
              if (data.hasOwnProperty(attr)) {
                if (attr != 'link') {
                  $video.find('video').attr('data-' + attr, data[attr]);
                }
              }
            }
          }

          $video.find('video').on('canplay', _loadedCallback);
          $video.find('video').attr('src', link);
          editor.edit.on();
          _syncVideos();
          editor.undo.saveStep();

          // Cursor will not appear if we don't make blur.
          editor.$el.blur();

          editor.events.trigger(replaced ? 'video.replaced' : 'video.inserted', [$video, response]);
        }
      }

      video.onerror = function () {
        _throwError(BAD_LINK);
      }

      showProgressBar('Loading video');

      video.src = link;
    }

    /**
     * Show progress bar.
     */

    function showProgressBar (no_message) {
      var $popup = editor.popups.get('video.insert');
      if (!$popup) $popup = _initInsertPopup();

      $popup.find('.fr-layer.fr-active').removeClass('fr-active').addClass('fr-pactive');
      $popup.find('.fr-video-progress-bar-layer').addClass('fr-active');
      $popup.find('.fr-buttons').hide();

      if ($current_video) {
        var $current_video_obj = $current_video.find('video');
        editor.popups.setContainer('video.insert', editor.$sc);
        var left = $current_video_obj.offset().left + $current_video_obj.width() / 2;
        var top = $current_video_obj.offset().top + $current_video_obj.height();

        editor.popups.show('video.insert', left, top, $current_video_obj.outerHeight());
      }

      if (typeof no_message == 'undefined') {
        _setProgressMessage('Uploading', 0);
      }
    }

    /**
     * Hide progress bar.
     */
    function hideProgressBar (dismiss) {
      var $popup = editor.popups.get('video.insert');
      if ($popup) {
        $popup.find('.fr-layer.fr-pactive').addClass('fr-active').removeClass('fr-pactive');
        $popup.find('.fr-video-progress-bar-layer').removeClass('fr-active');
        $popup.find('.fr-buttons').show();

        // Dismiss error message.
        if (dismiss || editor.$el.find('video.fr-error').length) {
          editor.events.focus();

          if (editor.$el.find('video.fr-error').length) {
            editor.$el.find('video.fr-error').parent().remove();
            editor.undo.saveStep();
            editor.undo.run();
            editor.undo.dropRedo();
          }
          if (!editor.$wp && $current_video) {
            var $video = $current_video;
            _exitEdit(true);
            editor.selection.setAfter($video.find('video').get(0));
            editor.selection.restore();
          }
          editor.popups.hide('video.insert');
        }
      }
    }

    /**
     * Set a progress message.
     */

    function _setProgressMessage (message, progress) {
      var $popup = editor.popups.get('video.insert');

      if ($popup) {
        var $layer = $popup.find('.fr-video-progress-bar-layer');
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
      var $popup = editor.popups.get('video.insert');
      var $layer = $popup.find('.fr-video-progress-bar-layer');
      $layer.addClass('fr-error');
      var $message_header = $layer.find('h3');
      $message_header.text(message);
      editor.events.disableBlur();
      $message_header.focus();
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

    function _editVideo ($video) {
      _edit.call($video.get(0));
    }

    /**
     * Parse video response.
     */

    function _parseResponse (response) {
      try {
        if (editor.events.trigger('video.uploaded', [response], true) === false) {
          editor.edit.on();
          return false;
        }

        var resp = JSON.parse(response);
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
     * Parse video response.
     */

    function _parseXMLResponse (response) {
      try {
        var link = $(response).find('Location').text();
        var key = $(response).find('Key').text();

        if (editor.events.trigger('video.uploadedToS3', [link, key, response], true) === false) {
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
     * Video was uploaded to the server and we have a response.
     */

    function _videoUploaded ($video) {
      _setProgressMessage('Loading video');

      var status = this.status;
      var response = this.response;
      var responseXML = this.responseXML;
      var responseText = this.responseText;

      try {
        if (editor.opts.videoUploadToS3) {
          if (status == 201) {
            var link = _parseXMLResponse(responseXML);
            if (link) {
              insertHtmlVideo(link, false, [], $video, response || responseXML);
            }
          } else {
            _throwError(BAD_RESPONSE, response || responseXML);
          }
        } else {
          if (status >= 200 && status < 300) {
            var resp = _parseResponse(responseText);
            if (resp) {
              insertHtmlVideo(resp.link, false, resp, $video, response || responseText);
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
     * Video upload error.
     */

    function _videoUploadError () {
      _throwError(BAD_RESPONSE, this.response || this.responseText || this.responseXML);
    }

    /**
     * Video upload progress.
     */

    function _videoUploadProgress (e) {
      if (e.lengthComputable) {
        var complete = (e.loaded / e.total * 100 | 0);
        _setProgressMessage('Uploading', complete);
      }
    }

    /**
     * Video upload aborted.
     */
    function _videoUploadAborted () {
      editor.edit.on();
      hideProgressBar(true);
    }

    function _addVideo (link, data, loadCallback) {
      // Build video data string.
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

      var width = editor.opts.videoDefaultWidth;
      if (width && width != 'auto') {
        width = width + 'px';
      }

      // Create video object and set the load event.
      var $video = $('<span contenteditable="false" draggable="true" class="fr-video fr-dv' + (editor.opts.videoDefaultDisplay[0]) + (editor.opts.videoDefaultAlign != 'center' ? ' fr-fv' + editor.opts.videoDefaultAlign[0] : '') + '"><video src="' + link + '" ' + data_str + (width ? ' style="width: ' + width + ';"' : '') + '" controls>' + editor.language.translate('Your browser does not support HTML5 video.') + '</video></span>');
      $video.toggleClass('fr-draggable', editor.opts.videoMove);

      $video.find('video').on('canplay', loadCallback);

      // Make sure we have focus.
      // Call the event.
      editor.edit.on();
      editor.events.focus(true);
      editor.selection.restore();

      editor.undo.saveStep();

      // Insert marker and then replace it with the video.
      if (editor.opts.videoSplitHTML) {
        editor.markers.split();
      } else {
        editor.markers.insert();
      }

      var $marker = editor.$el.find('.fr-marker');
      $marker.replaceWith($video);

      editor.html.wrap();
      editor.selection.clear();

      return $video;
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

      return ++step;
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
              step = _resizeVideo.call(this, e, 1, 1, step);
            }
            // Decrease video size.
            else if ((keycode == $.FE.KEYCODE.HYPHEN || (editor.browser.mozilla && keycode == $.FE.KEYCODE.FF_HYPHEN)) && ctrlKey && !e.altKey) {
              step = _resizeVideo.call(this, e, 2, -1, step);
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
     * Keep videos in sync when content changed.
     */
    var videos;

    function _syncVideos () {
      // Get current videos.
      var c_videos = Array.prototype.slice.call(editor.el.querySelectorAll('video'));

      // Current videos src.
      var video_srcs = [];
      var i;
      for (i = 0; i < c_videos.length; i++) {
        video_srcs.push(c_videos[i].getAttribute('src'));

        $(c_videos[i]).toggleClass('fr-draggable', editor.opts.videoMove);
        if (c_videos[i].getAttribute('class') === '') c_videos[i].removeAttribute('class');
        if (c_videos[i].getAttribute('style') === '') c_videos[i].removeAttribute('style');
      }

      // Loop previous videos and check their src.
      if (videos) {
        for (i = 0; i < videos.length; i++) {
          if (video_srcs.indexOf(videos[i].getAttribute('src')) < 0) {
            editor.events.trigger('video.removed', [$(videos[i])]);
          }
        }
      }

      // Current videos are the old ones.
      videos = c_videos;
    }

    /**
     * Reposition resizer.
     */
    function _repositionResizer () {
      if (!$video_resizer) _initResizer();

      (editor.$wp || editor.$sc).append($video_resizer);
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

      if (e && editor.edit.isDisabled()) {
        e.stopPropagation();
        e.preventDefault();
        return false;
      }

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

      _selectVideo()
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
     * Start the uploading process.
     */
    function _startUpload (xhr, form_data, video) {
      function _sendRequest () {
        var $video = $(this);

        $video.off('canplay');

        $video.addClass('fr-uploading');

        if ($video.parent().next().is('br')) {
          $video.parent().next().remove();
        }

        editor.placeholder.refresh();

        // Select the video.
        if (!$video.parent().is($current_video)) _editVideo($video.parent());

        _repositionResizer();
        showProgressBar();

        editor.edit.off();

        // Set upload events.
        xhr.onload = function () {
          _videoUploaded.call(xhr, $video.parent());
        };
        xhr.onerror = _videoUploadError;
        xhr.upload.onprogress = _videoUploadProgress;
        xhr.onabort = _videoUploadAborted;

        // Set abort event.
        $video.off('abortUpload').on('abortUpload', function () {
          if (xhr.readyState != 4) {
            xhr.abort();
          }
        });

        // Send data.
        xhr.send(form_data);
      }

      var reader = new FileReader();
      var $video;
      reader.addEventListener('load', function () {
        var link = reader.result;

        // Convert video to local blob.
        var binary = atob(reader.result.split(',')[1]);
        var array = [];
        for (var i = 0; i < binary.length; i++) {
          array.push(binary.charCodeAt(i));
        }

        // Get local video link.
        link = window.URL.createObjectURL(new Blob([new Uint8Array(array)], {
          type: 'video/mp4'
        }));

        // No video.
        if (!$current_video) {
          $video = _addVideo(link, null, _sendRequest);
        } else {

          // Flag to tell if the video is replaced.
          $current_video.data('fr-replaced', true);

          var $current_video_obj = $current_video.find('video');

          // Current video has video tag.
          if ($current_video_obj.length) {
            $current_video_obj.on('canplay', _sendRequest);
            editor.edit.on();
            editor.undo.saveStep();
            $current_video_obj.data('fr-old-src', $current_video_obj.attr('src'));
            $current_video_obj.attr('src', link);
          }
          // Iframe, embed ot other video type.
          else {
            remove();
            $video = _addVideo(link, null, _sendRequest);
          }
        }
      }, false);

      reader.readAsDataURL(video);
    }

    /**
     * Do video upload.
     */
    function upload (videos) {
      // Make sure we have what to upload.
      if (typeof videos != 'undefined' && videos.length > 0) {
        // Check if we should cancel the video upload.
        if (editor.events.trigger('video.beforeUpload', [videos]) === false) {
          return false;
        }

        var video = videos[0];

        // Check video max size.
        if (video.size > editor.opts.videoMaxSize) {
          _throwError(MAX_SIZE_EXCEEDED);
          return false;
        }

        // Check video types.
        if (editor.opts.videoAllowedTypes.indexOf(video.type.replace(/video\//g, '')) < 0) {
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
          if (editor.opts.videoUploadToS3 !== false) {
            form_data.append('key', editor.opts.videoUploadToS3.keyStart + (new Date()).getTime() + '-' + (video.name || 'untitled'));
            form_data.append('success_action_status', '201');
            form_data.append('X-Requested-With', 'xhr');
            form_data.append('Content-Type', video.type);

            for (key in editor.opts.videoUploadToS3.params) {
              if (editor.opts.videoUploadToS3.params.hasOwnProperty(key)) {
                form_data.append(key, editor.opts.videoUploadToS3.params[key]);
              }
            }
          }

          // Add upload params.
          for (key in editor.opts.videoUploadParams) {
            if (editor.opts.videoUploadParams.hasOwnProperty(key)) {
              form_data.append(key, editor.opts.videoUploadParams[key]);
            }
          }

          // Set the video in the request.
          form_data.append(editor.opts.videoUploadParam, video);

          // Create XHR request.
          var url = editor.opts.videoUploadURL;
          if (editor.opts.videoUploadToS3) {
            if (editor.opts.videoUploadToS3.uploadURL) {
              url = editor.opts.videoUploadToS3.uploadURL;
            }
            else {
              url = 'https://' + editor.opts.videoUploadToS3.region + '.amazonaws.com/' + editor.opts.videoUploadToS3.bucket;
            }
          }
          var xhr = editor.core.getXHR(url, editor.opts.videoUploadMethod);

          _startUpload(xhr, form_data, video);
        }
      }
    }

    /**
     * Video drop inside the upload zone.
     */

    function _bindInsertEvents ($popup) {
      // Drag over the dropable area.
      editor.events.$on($popup, 'dragover dragenter', '.fr-video-upload-layer', function () {
        $(this).addClass('fr-drop');
        return false;
      });

      // Drag end.
      editor.events.$on($popup, 'dragleave dragend', '.fr-video-upload-layer', function () {
        $(this).removeClass('fr-drop');
        return false;
      });

      // Drop.
      editor.events.$on($popup, 'drop', '.fr-upload-upload-layer', function (e) {
        e.preventDefault();
        e.stopPropagation();

        $(this).removeClass('fr-drop');

        var dt = e.originalEvent.dataTransfer;
        if (dt && dt.files) {
          var inst = $popup.data('instance') || editor;
          inst.events.disableBlur();
          inst.video.upload(dt.files);
          inst.events.enableBlur();
        }
      });

      editor.events.$on($popup, 'change', '.fr-video-upload-layer input[type="file"]', function () {
        if (this.files) {
          var inst = $popup.data('instance') || editor;
          inst.events.disableBlur();
          $popup.find('input:focus').blur();
          inst.events.enableBlur();
          inst.video.upload(this.files);
        }
        // Else IE 9 case.

        // Chrome fix.
        $(this).val('');
      });
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
     * Throw a video error.
     */

    function _throwError (code, response) {
      editor.edit.on();
      if ($current_video) $current_video.find('video').addClass('fr-error');
      _showErrorMessage(editor.language.translate('Something went wrong. Please try again.'));

      editor.events.trigger('video.error', [{
          code: code,
          message: error_messages[code]
        },
        response
      ]);
    }

    /**
     * Init the video edit popup.
     */
    function _initEditPopup () {
      // Video buttons.
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

            editor.events.disableBlur();
            _editVideo($current_video);
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

      hideProgressBar();
      editor.popups.refresh('video.size');
      editor.popups.setContainer('video.size', editor.$sc);
      var $video_obj = $current_video.find('iframe, embed, video')
      var left = $video_obj.offset().left + $video_obj.width() / 2;
      var top = $video_obj.offset().top + $video_obj.height();

      editor.popups.show('video.size', left, top, $video_obj.height());
    }

    /**
     * Init the video upload popup.
     */
    function _initSizePopup (delayed) {
      if (delayed) {
        editor.popups.onRefresh('video.size', _refreshSizePopup);

        return true;
      }

      // Video buttons.
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

          editor.events.disableBlur();
          _editVideo($current_video);
        }
      });

      return $popup;
    }

    /**
     * Get video alignment.
     */
    function getAlign ($video) {
      if (typeof $video == 'undefined') $video = $current_video;

      if ($video) {
        // Video has left class.
        if ($video.hasClass('fr-fvl')) {
          return 'left';
        }
        // Video has right class.
        else if ($video.hasClass('fr-fvr')) {
          return 'right';
        }
        // Video has display class set.
        else if ($video.hasClass('fr-dvb') || $video.hasClass('fr-dvi')) {
          return 'center';
        }
        else {
          // Video has display block.
          if ($video.css('display') == 'block') {
            // Margin left is 0.
            // Margin right is auto.
            if ($video.css('text-algin') == 'left') {
              return 'left';
            }

            // Margin left is auto.
            // Margin right is 0.
            else if ($video.css('text-align') == 'right') {
              return 'right';
            }
          }

          // Display inline.
          else {
            // Float left.
            if ($video.css('float') == 'left') {
              return 'left';
            }

            // Float right.
            else if ($video.css('float') == 'right') {
              return 'right';
            }
          }
        }
      }

      return 'center';
    }

    /**
     * Align video.
     */
    function align (val) {
      $current_video.removeClass('fr-fvr fr-fvl');

      // Easy case. Use classes.
      if (!editor.opts.htmlUntouched && editor.opts.useClasses) {
        if (val == 'left') {
          $current_video.addClass('fr-fvl');
        } else if (val == 'right') {
          $current_video.addClass('fr-fvr');
        }
      }
      else {
        _setStyle($current_video, getDisplay(), val);
      }

      _repositionResizer();
      _showEditPopup();
    }

    /**
     * Refresh the align icon.
     */
    function refreshAlign ($btn) {
      if (!$current_video) return false;

      $btn.find('> *:first').replaceWith(editor.icon.create('video-align-' + getAlign()));
    }

    /**
     * Refresh the align option from the dropdown.
     */
    function refreshAlignOnShow ($btn, $dropdown) {
      if ($current_video) {
        $dropdown.find('.fr-command[data-param1="' + getAlign() + '"]').addClass('fr-active').attr('aria-selected', true);
      }
    }

    /**
     * Get video display.
     */
    function getDisplay ($video) {
      if (typeof $video == 'undefined') $video = $current_video;

      // Set float to none.
      var flt = $video.css('float');
      $video.css('float', 'none');

      // Video has display block.
      if ($video.css('display') == 'block') {
        // Set float to the initial value.
        $video.css('float', '');
        if ($video.css('float') != flt) $video.css('float', flt);

        return 'block';
      }

      // Display inline.
      else {
        // Set float.
        $video.css('float', '');
        if ($video.css('float') != flt) $video.css('float', flt);

        return 'inline';
      }

      return 'inline';
    }


    /**
     * Set video display.
     */
    function display (val) {
      $current_video.removeClass('fr-dvi fr-dvb');

      // Easy case. Use classes.
      if (!editor.opts.htmlUntouched && editor.opts.useClasses) {
        if (val == 'inline') {
          $current_video.addClass('fr-dvi');
        } else if (val == 'block') {
          $current_video.addClass('fr-dvb');
        }
      }
      else {
        _setStyle($current_video, val, getAlign());
      }

      _repositionResizer();
      _showEditPopup();
    }

    /**
     * Refresh the video display selected option.
     */
    function refreshDisplayOnShow ($btn, $dropdown) {
      if ($current_video) {
        $dropdown.find('.fr-command[data-param1="' + getDisplay() + '"]').addClass('fr-active').attr('aria-selected', true);
      }
    }

    /**
     * Show the replace popup.
     */

    function replace () {
      var $popup = editor.popups.get('video.insert');
      if (!$popup) $popup = _initInsertPopup();

      if (!editor.popups.isVisible('video.insert')) {
        hideProgressBar();
        editor.popups.refresh('video.insert');
        editor.popups.setContainer('video.insert', editor.$sc);
      }

      var left = $current_video.offset().left + $current_video.width() / 2;
      var top = $current_video.offset().top + $current_video.height();

      editor.popups.show('video.insert', left, top, $current_video.outerHeight());
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
     * Hide image upload popup.
     */

    function _hideInsertPopup () {
      hideProgressBar();
    }

    function _setStyle ($video, _display, _align) {
      if (!editor.opts.htmlUntouched && editor.opts.useClasses) {
        $video.removeClass('fr-fvl fr-fvr fr-dvb fr-dvi');
        $video.addClass('fr-fv' + _align[0] + ' fr-dv' + _display[0]);
      }
      else {
        if (_display == 'inline') {
          $video.css({
            display: 'inline-block'
          })

          if (_align == 'center') {
            $video.css({
              'float': 'none'
            })
          }
          else if (_align == 'left') {
            $video.css({
              'float': 'left'
            })
          }
          else {
            $video.css({
              'float': 'right'
            })
          }
        }
        else {
          $video.css({
            display: 'block',
            clear: 'both'
          })

          if (_align == 'left') {
            $video.css({
              textAlign: 'left'
            })
          }
          else if (_align == 'right') {
            $video.css({
              textAlign: 'right'
            })
          }
          else {
            $video.css({
              textAlign: 'center'
            })
          }
        }
      }
    }

    /**
     * Convert style to classes.
     */
    function _convertStyleToClasses ($video) {
      if (!$video.hasClass('fr-dvi') && !$video.hasClass('fr-dvb')) {
        $video.addClass('fr-fi' + getAlign($video)[0]);
        $video.addClass('fr-di' + getDisplay($video)[0]);
      }
    }

    /**
     * Convert classes to style.
     */
    function _convertClassesToStyle ($video) {
      var d = $video.hasClass('fr-dvb') ? 'block' : $video.hasClass('fr-dvi') ? 'inline' : null;
      var a = $video.hasClass('fr-fvl') ? 'left' : $video.hasClass('fr-fvr') ? 'right' : getAlign($video);

      _setStyle($video, d, a);

      $video.removeClass('fr-dvb fr-dvi fr-fvr fr-fvl');
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

      var videos = editor.$el.find('span.fr-video, video');

      for (var i = 0; i < videos.length; i++) {
        var $video = $(videos[i]);

        if (!editor.opts.htmlUntouched && editor.opts.useClasses) {
          _convertStyleToClasses($video);

          if (!editor.opts.videoTextNear) {
            $video.removeClass('fr-dvi').addClass('fr-dvb');
          }
        }
        else if (!editor.opts.htmlUntouched && !editor.opts.useClasses) {
          _convertClassesToStyle($video);
        }
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

      // focusEditor from accessibility.
      editor.events.on('toolbar.focusEditor', function () {
        if ($current_video) {
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
     * Place selection around current video.
     */
    function _selectVideo () {
      if ($current_video) {
        editor.selection.clear();
        var range = editor.doc.createRange();
        range.selectNode($current_video.get(0));
        var selection = editor.selection.get();
        selection.addRange(range);
      }
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
      refreshUploadButton: refreshUploadButton,
      upload: upload,
      insertByURL: insertByURL,
      insertEmbed: insertEmbed,
      insert: insert,
      align: align,
      refreshAlign: refreshAlign,
      refreshAlignOnShow: refreshAlignOnShow,
      display: display,
      refreshDisplayOnShow: refreshDisplayOnShow,
      remove: remove,
      hideProgressBar: hideProgressBar,
      showSizePopup: showSizePopup,
      replace: replace,
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

  // Video by URL button inside the insert video popup.
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

  // Video embed button inside the insert video popup.
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

  // Video upload button inside the insert video popup.
  $.FE.DefineIcon('videoUpload', { NAME: 'upload' });
  $.FE.RegisterCommand('videoUpload', {
    title: 'Upload Video',
    undo: false,
    focus: false,
    toggle: true,
    callback: function () {
      this.video.showLayer('video-upload');
    },
    refresh: function ($btn) {
      this.video.refreshUploadButton($btn);
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

  // Video display.
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

  // Video align.
  $.FE.DefineIcon('video-align', { NAME: 'align-left' });
  $.FE.DefineIcon('video-align-left', { NAME: 'align-left' });
  $.FE.DefineIcon('video-align-right', { NAME: 'align-right' });
  $.FE.DefineIcon('video-align-center', { NAME: 'align-justify' });

  // Video align.
  $.FE.DefineIcon('videoAlign', { NAME: 'align-center' })
  $.FE.RegisterCommand('videoAlign', {
    type: 'dropdown',
    title: 'Align',
    options: {
      left: 'Align Left',
      center: 'None',
      right: 'Align Right'
    },
    html: function () {
      var c = '<ul class="fr-dropdown-list" role="presentation">';
      var options =  $.FE.COMMANDS.videoAlign.options;
      for (var val in options) {
        if (options.hasOwnProperty(val)) {
          c += '<li role="presentation"><a class="fr-command fr-title" tabIndex="-1" role="option" data-cmd="videoAlign" data-param1="' + val + '" title="' + this.language.translate(options[val]) + '">' + this.icon.create('video-align-' + val) + '<span class="fr-sr-only">' + this.language.translate(options[val]) + '</span></a></li>';
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

  // Video replace.
  $.FE.DefineIcon('videoReplace', {
    NAME: 'exchange'
  })
  $.FE.RegisterCommand('videoReplace', {
    title: 'Replace',
    undo: false,
    focus: false,
    popup: true,
    refreshAfterCallback: false,
    callback: function () {
      this.video.replace();
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

  $.FE.RegisterCommand('videoDismissError', {
    title: 'OK',
    undo: false,
    callback: function () {
      this.video.hideProgressBar(true);
    }
  })

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
