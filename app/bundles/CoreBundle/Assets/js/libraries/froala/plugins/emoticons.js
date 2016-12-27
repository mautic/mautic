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
    emoticons: '[_BUTTONS_][_EMOTICONS_]'
  })

  // Extend defaults.
  $.extend($.FE.DEFAULTS, {
    emoticonsStep: 8,
    emoticonsSet: [
      { code: '1f600', desc: 'Grinning face' },
      { code: '1f601', desc: 'Grinning face with smiling eyes' },
      { code: '1f602', desc: 'Face with tears of joy' },
      { code: '1f603', desc: 'Smiling face with open mouth' },
      { code: '1f604', desc: 'Smiling face with open mouth and smiling eyes' },
      { code: '1f605', desc: 'Smiling face with open mouth and cold sweat' },
      { code: '1f606', desc: 'Smiling face with open mouth and tightly-closed eyes' },
      { code: '1f607', desc: 'Smiling face with halo' },

      { code: '1f608', desc: 'Smiling face with horns' },
      { code: '1f609', desc: 'Winking face' },
      { code: '1f60a', desc: 'Smiling face with smiling eyes' },
      { code: '1f60b', desc: 'Face savoring delicious food' },
      { code: '1f60c', desc: 'Relieved face' },
      { code: '1f60d', desc: 'Smiling face with heart-shaped eyes' },
      { code: '1f60e', desc: 'Smiling face with sunglasses' },
      { code: '1f60f', desc: 'Smirking face' },

      { code: '1f610', desc: 'Neutral face' },
      { code: '1f611', desc: 'Expressionless face' },
      { code: '1f612', desc: 'Unamused face' },
      { code: '1f613', desc: 'Face with cold sweat' },
      { code: '1f614', desc: 'Pensive face' },
      { code: '1f615', desc: 'Confused face' },
      { code: '1f616', desc: 'Confounded face' },
      { code: '1f617', desc: 'Kissing face' },

      { code: '1f618', desc: 'Face throwing a kiss' },
      { code: '1f619', desc: 'Kissing face with smiling eyes' },
      { code: '1f61a', desc: 'Kissing face with closed eyes' },
      { code: '1f61b', desc: 'Face with stuck out tongue' },
      { code: '1f61c', desc: 'Face with stuck out tongue and winking eye' },
      { code: '1f61d', desc: 'Face with stuck out tongue and tightly-closed eyes' },
      { code: '1f61e', desc: 'Disappointed face' },
      { code: '1f61f', desc: 'Worried face' },

      { code: '1f620', desc: 'Angry face' },
      { code: '1f621', desc: 'Pouting face' },
      { code: '1f622', desc: 'Crying face' },
      { code: '1f623', desc: 'Persevering face' },
      { code: '1f624', desc: 'Face with look of triumph' },
      { code: '1f625', desc: 'Disappointed but relieved face' },
      { code: '1f626', desc: 'Frowning face with open mouth' },
      { code: '1f627', desc: 'Anguished face' },

      { code: '1f628', desc: 'Fearful face' },
      { code: '1f629', desc: 'Weary face' },
      { code: '1f62a', desc: 'Sleepy face' },
      { code: '1f62b', desc: 'Tired face' },
      { code: '1f62c', desc: 'Grimacing face' },
      { code: '1f62d', desc: 'Loudly crying face' },
      { code: '1f62e', desc: 'Face with open mouth' },
      { code: '1f62f', desc: 'Hushed face' },

      { code: '1f630', desc: 'Face with open mouth and cold sweat' },
      { code: '1f631', desc: 'Face screaming in fear' },
      { code: '1f632', desc: 'Astonished face' },
      { code: '1f633', desc: 'Flushed face' },
      { code: '1f634', desc: 'Sleeping face' },
      { code: '1f635', desc: 'Dizzy face' },
      { code: '1f636', desc: 'Face without mouth' },
      { code: '1f637', desc: 'Face with medical mask' }
    ],
    emoticonsButtons: ['emoticonsBack', '|'],
    emoticonsUseImage: true
  });

  $.FE.PLUGINS.emoticons = function (editor) {
    /*
     * Show the emoticons popup.
     */
    function _showEmoticonsPopup () {
      var $btn = editor.$tb.find('.fr-command[data-cmd="emoticons"]');

      var $popup = editor.popups.get('emoticons');
      if (!$popup) $popup = _initEmoticonsPopup();

      if (!$popup.hasClass('fr-active')) {
        // Emoticons popup.
        editor.popups.refresh('emoticons');
        editor.popups.setContainer('emoticons', editor.$tb);

        // Emoticons popup left and top position.
        var left = $btn.offset().left + $btn.outerWidth() / 2;
        var top = $btn.offset().top + (editor.opts.toolbarBottom ? 10 : $btn.outerHeight() - 10);

        editor.popups.show('emoticons', left, top, $btn.outerHeight());
      }
    }

    /*
     * Hide emoticons popup.
     */
    function _hideEmoticonsPopup () {
      // Hide popup.
      editor.popups.hide('emoticons');
    }

    /**
     * Init the emoticons popup.
     */
    function _initEmoticonsPopup () {
      var emoticons_buttons = '';

      if (editor.opts.toolbarInline) {
        // Emoticons buttons.
        if (editor.opts.emoticonsButtons.length > 0) {
          emoticons_buttons = '<div class="fr-buttons fr-emoticons-buttons">' + editor.button.buildList(editor.opts.emoticonsButtons) + '</div>';
        }
      }

      var template = {
        buttons: emoticons_buttons,
        emoticons: _emoticonsHTML()
      };

      // Create popup.
      var $popup = editor.popups.create('emoticons', template);

      // Assing tooltips to buttons.
      editor.tooltip.bind($popup, '.fr-emoticon');

      _addAccessibility($popup);

      return $popup;
    }

    /*
     * HTML for the emoticons popup.
     */
    function _emoticonsHTML () {
      // Create emoticons html.
      var emoticons_html = '<div style="text-align: center">';

      // Add emoticons.
      for (var i = 0; i < editor.opts.emoticonsSet.length; i++) {
        if (i !== 0 && i % editor.opts.emoticonsStep === 0) {
          emoticons_html += '<br>';
        }

        emoticons_html += '<span class="fr-command fr-emoticon" tabIndex="-1" data-cmd="insertEmoticon" title="' + editor.language.translate(editor.opts.emoticonsSet[i].desc) + '" role="button" data-param1="' + editor.opts.emoticonsSet[i].code + '">' + (editor.opts.emoticonsUseImage ? '<img src="' + 'https://cdnjs.cloudflare.com/ajax/libs/emojione/2.0.1/assets/svg/' + editor.opts.emoticonsSet[i].code + '.svg' + '"/>' : '&#x' + editor.opts.emoticonsSet[i].code + ';') + '<span class="fr-sr-only">' + editor.language.translate(editor.opts.emoticonsSet[i].desc) + '&nbsp;&nbsp;&nbsp;</span></span>';
      }

      if (editor.opts.emoticonsUseImage) emoticons_html += '<p style="font-size: 12px; text-align: center; padding: 0 5px;">Emoji free by <a class="fr-link" tabIndex="-1" href="http://emojione.com/" target="_blank" rel="nofollow" role="link" aria-label="Open Emoji One website.">Emoji One</a></p>';
      emoticons_html += '</div>';

      return emoticons_html;
    }

    /*
     * Register keyboard events.
     */
    function _addAccessibility ($popup) {
      // Register popup event.
      editor.events.on('popup.tab', function (e) {
        var $focused_item = $(e.currentTarget);
        // Skip if popup is not visible or focus is elsewere.
        if (!editor.popups.isVisible('emoticons') || !$focused_item.is('span, a')) {
          return true;
        }

        var key_code = e.which;
        var status;
        var index;
        var $el;

        // Tabbing.
        if ($.FE.KEYCODE.TAB == key_code) {
          // Extremities reached.
          if (($focused_item.is('span.fr-emoticon') && e.shiftKey) || ($focused_item.is('a') && !e.shiftKey)) {
            var $tb = $popup.find('.fr-buttons');
            // Focus back the popup's toolbar if exists.
            status = !editor.accessibility.focusToolbar($tb, (e.shiftKey ? true : false));
          }

          if (status !== false) {
            // Build elements that should be focused next.
            var $tabElements = $popup.find('span.fr-emoticon:focus:first, span.fr-emoticon:visible:first, a');
            if ($focused_item.is('span.fr-emoticon')) {
              $tabElements = $tabElements.not('span.fr-emoticon:not(:focus)');
            }
            // Get focused item position.
            index = $tabElements.index($focused_item);

            // Backwards.
            if (e.shiftKey) {
              index = (((index - 1) % $tabElements.length) + $tabElements.length) % $tabElements.length; // Javascript negative modulo bug.
            // Forward.
            } else {
              index = (index + 1) % $tabElements.length;
            }

            // Find next element to focus.
            $el = $tabElements.get(index);

            editor.events.disableBlur();
            $el.focus();
            status = false;
          }
        }
        // Arrows.
        else if ($.FE.KEYCODE.ARROW_UP == key_code || $.FE.KEYCODE.ARROW_DOWN == key_code || $.FE.KEYCODE.ARROW_LEFT == key_code || $.FE.KEYCODE.ARROW_RIGHT == key_code) {
          if ($focused_item.is('span.fr-emoticon')) {

            // Get all current emoticons.
            var $emoticons = $focused_item.parent().find('span.fr-emoticon');

            // Get focused item position.
            index = $emoticons.index($focused_item);

            // Get emoticons matrix dimensions.
            var columns = editor.opts.emoticonsStep;
            var lines = Math.floor($emoticons.length / columns);

            // Get focused item coordinates.
            var column = index % columns;
            var line = Math.floor(index / columns);

            var nextIndex = line * columns + column;
            var dimension = lines * columns;

            // Calculate next index. Go to the other opposite site of the matrix if there is no next adjacent element.
            // Up/Down: Traverse matrix lines.
            // Left/Right: Traverse the matrix as it is a vector.
            if ($.FE.KEYCODE.ARROW_UP == key_code) {
              nextIndex = (((nextIndex - columns) % dimension) + dimension) % dimension; // Javascript negative modulo bug.
            }
            else if ($.FE.KEYCODE.ARROW_DOWN == key_code) {
              nextIndex = (nextIndex + columns) % dimension;
            }
            else if ($.FE.KEYCODE.ARROW_LEFT == key_code) {
              nextIndex = (((nextIndex - 1) % dimension) + dimension) % dimension; // Javascript negative modulo bug.
            }
            else if ($.FE.KEYCODE.ARROW_RIGHT == key_code) {
              nextIndex = (nextIndex + 1) % dimension;
            }

            // Get the next element based on the new index.
            $el = $($emoticons.get(nextIndex));

            // Focus.
            editor.events.disableBlur();
            $el.focus();

            status = false;
          }
        }
        // ENTER or SPACE.
        else if ($.FE.KEYCODE.ENTER == key_code) {
          if ($focused_item.is('a')) {
            $focused_item[0].click();
          }
          else {
            editor.button.exec($focused_item);
          }
          status = false;
        }

        // Prevent propagation.
        if (status === false) {
          e.preventDefault();
          e.stopPropagation();
        }

        return status;
      }, true);
    }

    /*
     * Insert emoticon.
     */
    function insert (emoticon, img) {
      // Insert emoticon.
      editor.html.insert('<span class="fr-emoticon fr-deletable' + (img ? ' fr-emoticon-img' : '') + '"' + (img ? ' style="background: url(' + img + ');"' : '') + '>' + (img ? '&nbsp;' : emoticon) + '</span>' + '&nbsp;' + $.FE.MARKERS, true);
    }

    /*
     * Go back to the inline editor.
     */
    function back () {
      editor.popups.hide('emoticons');
      editor.toolbar.showInline();
    }

    /*
     * Init emoticons.
     */
    function _init () {
      var setDeletable = function () {
        var emtcs = editor.el.querySelectorAll('.fr-emoticon:not(.fr-deletable)');
        for (var i = 0; i < emtcs.length; i++) {
          emtcs[i].className += ' fr-deletable';
        }
      }
      setDeletable();

      editor.events.on('html.set', setDeletable);

      // Replace emoticons with unicode.
      editor.events.on('html.get', function (html) {
        for (var i = 0; i < editor.opts.emoticonsSet.length; i++) {
          var em = editor.opts.emoticonsSet[i];
          var text = $('<div>').html(em.code).text();
          html = html.split(text).join(em.code);
        }

        return html;
      });

      var inEmoticon = function () {
        if (!editor.selection.isCollapsed()) return false;

        var s_el = editor.selection.element();
        var e_el = editor.selection.endElement();

        if (s_el && editor.node.hasClass(s_el, 'fr-emoticon')) return s_el;
        if (e_el && editor.node.hasClass(e_el, 'fr-emoticon')) return e_el;

        var range = editor.selection.ranges(0);
        var container = range.startContainer;
        if (container.nodeType == Node.ELEMENT_NODE) {
          if (container.childNodes.length > 0 && range.startOffset > 0) {
            var node = container.childNodes[range.startOffset - 1];
            if (editor.node.hasClass(node, 'fr-emoticon')) {
              return node;
            }
          }
        }

        return false;
      }

      editor.events.on('keydown', function (e) {
        if (editor.keys.isCharacter(e.which) && editor.selection.inEditor()) {
          var range = editor.selection.ranges(0);
          var el = inEmoticon();
          if (el) {
            if (range.startOffset === 0 && editor.selection.element() === el) {
              $(el).before($.FE.MARKERS + $.FE.INVISIBLE_SPACE);
            }
            else {
              $(el).after($.FE.INVISIBLE_SPACE + $.FE.MARKERS);
            }
            editor.selection.restore();
          }
        }
      });

      editor.events.on('keyup', function (e) {
        var emtcs = editor.el.querySelectorAll('.fr-emoticon');

        for (var i = 0; i < emtcs.length; i++) {
          if (typeof emtcs[i].textContent != 'undefined' && emtcs[i].textContent.replace(/\u200B/gi, '').length === 0) {
            $(emtcs[i]).remove();
          }
        }

        if (!(e.which >= $.FE.KEYCODE.ARROW_LEFT && e.which <= $.FE.KEYCODE.ARROW_DOWN)) {
          var el = inEmoticon();
          if (editor.node.hasClass(el, 'fr-emoticon-img')) {
            $(el).append($.FE.MARKERS);
            editor.selection.restore();
          }
        }
      });
    }

    return {
      _init: _init,
      insert: insert,
      showEmoticonsPopup: _showEmoticonsPopup,
      hideEmoticonsPopup: _hideEmoticonsPopup,
      back: back
    }
  }

  // Toolbar emoticons button.
  $.FE.DefineIcon('emoticons', { NAME: 'smile-o' });
  $.FE.RegisterCommand('emoticons', {
    title: 'Emoticons',
    undo: false,
    focus: true,
    refreshOnCallback: false,
    popup: true,
    callback: function () {
      if (!this.popups.isVisible('emoticons')) {
        this.emoticons.showEmoticonsPopup();
      }
      else {
        if (this.$el.find('.fr-marker').length) {
          this.events.disableBlur();
          this.selection.restore();
        }
        this.popups.hide('emoticons');
      }
    },
    plugin: 'emoticons'
  });

  // Insert emoticon command.
  $.FE.RegisterCommand('insertEmoticon', {
    callback: function (cmd, code) {
      // Insert emoticon.
      this.emoticons.insert('&#x' + code + ';', this.opts.emoticonsUseImage ? 'https://cdnjs.cloudflare.com/ajax/libs/emojione/2.0.1/assets/svg/' + code + '.svg' : null);

      // Hide emoticons popup.
      this.emoticons.hideEmoticonsPopup();
    }
  });

  // Emoticons back.
  $.FE.DefineIcon('emoticonsBack', { NAME: 'arrow-left' });
  $.FE.RegisterCommand('emoticonsBack', {
    title: 'Back',
    undo: false,
    focus: false,
    back: true,
    refreshAfterCallback: false,
    callback: function () {
      this.emoticons.back();
    }
  });

}));
