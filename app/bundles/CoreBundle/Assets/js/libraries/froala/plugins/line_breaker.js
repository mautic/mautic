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
    lineBreakerTags: ['table', 'hr', 'form', 'dl', 'span.fr-video'],
    lineBreakerOffset: 15
  });

  $.FE.PLUGINS.lineBreaker = function (editor) {
    var $line_breaker;
    var mouseDownFlag;
    var mouseMoveTimer;

    /*
     * Show line breaker.
     * Compute top, left, width and show the line breaker.
     * tag1 and tag2 are the tags between which the line breaker must be showed.
     * If tag1 is null then tag2 is the first tag in the editor.
     * If tag2 is null then tag1 is the last tag in the editor.
     */
    function _show ($tag1, $tag2) {
      // Line breaker's possition and width.
      var breakerTop;
      var breakerLeft;
      var breakerWidth;
      var parent_tag;
      var parent_top;
      var parent_bottom;
      var tag_top;
      var tag_bottom;

      // Mouse is over the first tag in the editor. Show line breaker above tag2.
      if ($tag1 == null) {
        // Compute line breaker's possition and width.
        parent_tag = $tag2.parent();
        parent_top = parent_tag.offset().top;
        tag_top = $tag2.offset().top;

        breakerTop = tag_top - Math.min((tag_top - parent_top) / 2, editor.opts.lineBreakerOffset);
        breakerWidth = parent_tag.outerWidth();
        breakerLeft = parent_tag.offset().left;

      // Mouse is over the last tag in the editor. Show line breaker below tag1.
      } else if ($tag2 == null) {
        // Compute line breaker's possition and width.
        parent_tag = $tag1.parent();
        parent_bottom = parent_tag.offset().top + parent_tag.outerHeight();
        tag_bottom = $tag1.offset().top + $tag1.outerHeight();

        breakerTop = tag_bottom + Math.min((parent_bottom - tag_bottom) / 2, editor.opts.lineBreakerOffset);
        breakerWidth = parent_tag.outerWidth();
        breakerLeft = parent_tag.offset().left;

      // Mouse is between the 2 tags.
      } else {
        // Compute line breaker's possition and width.
        parent_tag = $tag1.parent();
        var tag1_bottom = $tag1.offset().top + $tag1.height();
        var tag2_top = $tag2.offset().top;

        // Tags may be on the same line, so there is no need for line breaker.
        if (tag1_bottom > tag2_top) {
          return false;
        }

        breakerTop = (tag1_bottom + tag2_top) / 2;
        breakerWidth = parent_tag.outerWidth();
        breakerLeft = parent_tag.offset().left;
      }

      if (editor.opts.iframe) {
        breakerLeft += editor.$iframe.offset().left - $(editor.o_win).scrollLeft();
        breakerTop += editor.$iframe.offset().top - $(editor.o_win).scrollTop();
      }

      editor.$box.append($line_breaker);

      // Set line breaker's top, left and width.
      $line_breaker.css('top', breakerTop - editor.win.pageYOffset);
      $line_breaker.css('left', breakerLeft - editor.win.pageXOffset);
      $line_breaker.css('width', breakerWidth);

      $line_breaker.data('tag1', $tag1);
      $line_breaker.data('tag2', $tag2);

      // Show the line breaker.
      $line_breaker.addClass('fr-visible').data('instance', editor);
    }

    /*
     * Check tag siblings.
     * The line breaker hould appear if there is no sibling or if the sibling is also in the line breaker tags list.
     */
    function _checkTagSiblings ($tag, mouseY) {
      // Tag's Y top and bottom coordinate.
      var tag_top = $tag.offset().top;
      var tag_bottom = $tag.offset().top + $tag.outerHeight();
      var $sibling;
      var tag;

      // Only if the mouse is close enough to the bottom or top edges.
      if (Math.abs(tag_bottom - mouseY) <= editor.opts.lineBreakerOffset ||
          Math.abs(mouseY - tag_top) <= editor.opts.lineBreakerOffset) {

        // Mouse is near bottom check for next sibling.
        if (Math.abs(tag_bottom - mouseY) < Math.abs(mouseY - tag_top)) {
          tag = $tag.get(0);

          var next_node = tag.nextSibling;
          while (next_node && next_node.nodeType == Node.TEXT_NODE && next_node.textContent.length === 0) {
            next_node = next_node.nextSibling;
          }

          // Tag has next sibling.
          if (next_node) {
            $sibling = _checkTag(next_node);

            // Sibling is in the line breaker tags list.
            if ($sibling) {
              // Show line breaker.
              _show($tag, $sibling);
              return true;
            }

          // No next sibling.
          } else {
            // Show line breaker
            _show($tag, null);
            return true;
          }
        }

        // Mouse is near top check for prev sibling.
        else {
          tag = $tag.get(0);

          // No prev sibling.
          if (!tag.previousSibling) {
            // Show line breaker
            _show(null, $tag);
            return true;

          // Tag has prev sibling.
          } else {
            $sibling = _checkTag(tag.previousSibling);

            // Sibling is in the line breaker tags list.
            if ($sibling) {
              // Show line breaker.
              _show($sibling, $tag);
              return true;
            }
          }
        }
      }

      $line_breaker.removeClass('fr-visible').removeData('instance');
    }

    /*
     * Check if tag is in the line breaker list and in the editor as well.
     * Returns the tag from the line breaker list or false if the tag is not in the list.
     */
    function _checkTag (tag) {
      if (tag) {
        var $tag = $(tag);

        // Make sure tag is inside the editor.
        if (editor.$el.find($tag).length === 0) return null;

        // Tag is in the line breaker tags list.
        if (tag.nodeType != Node.TEXT_NODE && $tag.is(editor.opts.lineBreakerTags.join(','))) {
          return $tag;
        }

        // Tag's parent is in the line breaker tags list.
        else if ($tag.parents(editor.opts.lineBreakerTags.join(',')).length > 0) {
          tag = $tag.parents(editor.opts.lineBreakerTags.join(',')).get(0);

          return $(tag);
        }
      }

      return null;
    }

    /*
     * Get the tag under the mouse cursor.
     */
    function _tagUnder (e) {
      mouseMoveTimer = null;

      // The tag for which the line breaker should be showed.
      var $tag = null;

      // The tag under the mouse cursor.
      var tag_under = editor.doc.elementFromPoint(e.pageX - editor.win.pageXOffset, e.pageY - editor.win.pageYOffset);
      var i;
      var tag_above;
      var tag_below;

      // Tag is the editor element. Look for closest tag above and bellow.
      if (tag_under && (tag_under.tagName == 'HTML' || tag_under.tagName == 'BODY' || editor.node.isElement(tag_under))) {
        // Look 1px up and 1 down until a tag is found or the line breaker offset is reached.
        for (i = 1; i <= editor.opts.lineBreakerOffset; i++) {
          // Look for tag above.
          tag_above = editor.doc.elementFromPoint(e.pageX - editor.win.pageXOffset, e.pageY - editor.win.pageYOffset - i);

          // We found a tag above.
          if (tag_above && !editor.node.isElement(tag_above) && tag_above != editor.$wp.get(0) && $(tag_above).parents(editor.$wp).length) {
            $tag = _checkTag(tag_above);
            break;
          }

          // Look for tag below.
          tag_below = editor.doc.elementFromPoint(e.pageX - editor.win.pageXOffset, e.pageY - editor.win.pageYOffset + i);

          // We found a tag bellow.
          if (tag_below && !editor.node.isElement(tag_below) && tag_below != editor.$wp.get(0) && $(tag_below).parents(editor.$wp).length) {
            $tag = _checkTag(tag_below);
            break;
          }
        }

      // Tag is not the editor element.
      } else {
        // Check if the tag is in the line breaker list.
        $tag = _checkTag(tag_under);
      }

      // Check tag siblings.
      if ($tag) {
        _checkTagSiblings($tag, e.pageY);
      }
      else if (editor.core.sameInstance($line_breaker)) {
        $line_breaker.removeClass('fr-visible').removeData('instance');
      }
    }

    /*
     * Set mouse timer to improve performance.
     */
    function _mouseTimer (e) {
      if ($line_breaker.hasClass('fr-visible') && !editor.core.sameInstance($line_breaker)) return false;

      if (editor.popups.areVisible() || editor.el.querySelector('.fr-selected-cell')) {
        $line_breaker.removeClass('fr-visible');
        return true;
      }

      if (mouseDownFlag === false) {
        if (mouseMoveTimer) {
          clearTimeout(mouseMoveTimer);
        }

        mouseMoveTimer = setTimeout(_tagUnder, 30, e);
      }
    }

    /*
     * Hide line breaker and prevent timer from showing it again.
     */
    function _hide () {
      if (mouseMoveTimer) {
        clearTimeout(mouseMoveTimer);
      }

      if ($line_breaker.hasClass('fr-visible')) {
        $line_breaker.removeClass('fr-visible').removeData('instance');
      }
    }

    /*
     * Notify that mouse is down and prevent line breaker from showing.
     * This may happen either for selection or for drag.
     */
    function _mouseDown () {
      mouseDownFlag = true;
      _hide();
    }

    /*
     * Notify that mouse is no longer pressed.
     */
    function _mouseUp () {
      mouseDownFlag = false;
    }

    /*
     * Add new line between the tags.
     */
    function _doLineBreak (e) {
      if (!editor.core.sameInstance($line_breaker)) return true;

      e.preventDefault();

      // Hide the line breaker.
      $line_breaker.removeClass('fr-visible').removeData('instance');

      // Tags between which that line break needs to be done.
      var $tag1 = $line_breaker.data('tag1');
      var $tag2 = $line_breaker.data('tag2');

      // P, DIV or none.
      var default_tag = editor.html.defaultTag();

      // The line break needs to be done before the first element in the editor.
      if ($tag1 == null) {
        // If the tag is in a TD tag then just add <br> no matter what the default_tag is.
        if (default_tag && $tag2.parent().get(0).tagName != 'TD') {
          $tag2.before('<' + default_tag + '>' + $.FE.MARKERS + '<br></' + default_tag + '>')
        }
        else {
          $tag2.before($.FE.MARKERS + '<br>');
        }

      // The line break needs to be done either after the last element in the editor or between the 2 tags.
      // Either way the line break is after the first tag.
      } else {
        // If the tag is in a TD tag then just add <br> no matter what the default_tag is.
        if (default_tag && $tag1.parent().get(0).tagName != 'TD' && $tag1.parents(default_tag).length === 0) {
          $tag1.after('<' + default_tag + '>' + $.FE.MARKERS + '<br></' + default_tag + '>')
        }
        else {
          $tag1.after($.FE.MARKERS + '<br>');
        }
      }

      // Cursor is now at the beginning of the new line.
      editor.selection.restore();
    }

    /*
     * Initialize the line breaker.
     */
    function _initLineBreaker () {
      // Append line breaker HTML to editor wrapper.
      if (!editor.shared.$line_breaker) {
        editor.shared.$line_breaker = $('<div class="fr-line-breaker"><a class="fr-floating-btn" role="button" tabIndex="-1" title="' + editor.language.translate('Break') + '"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><rect x="21" y="11" width="2" height="8"/><rect x="14" y="17" width="7" height="2"/><path d="M14.000,14.000 L14.000,22.013 L9.000,18.031 L14.000,14.000 Z"/></svg></a></div>');
      }

      $line_breaker = editor.shared.$line_breaker;

      // Editor shared destroy.
      editor.events.on('shared.destroy', function () {
        $line_breaker.html('').removeData().remove();
        $line_breaker = null;
      }, true);

      // Editor destroy.
      editor.events.on('destroy', function () {
        $line_breaker.removeData('instance').removeClass('fr-visible').appendTo('body');
        clearTimeout(mouseMoveTimer);
      }, true)

      editor.events.$on($line_breaker, 'mouseleave', _hide, true);

      editor.events.$on($line_breaker, 'mousemove', function (e) {
        e.stopPropagation();
      }, true)

      // Add new line break.
      editor.events.$on($line_breaker, 'mousedown', 'a', function (e) {
        e.stopPropagation();
      }, true);
      editor.events.$on($line_breaker, 'click', 'a', _doLineBreak, true);
    }

    /*
     * Tear up.
     */
    function _init () {
      if (!editor.$wp) return false;

      _initLineBreaker();

      // Remember if mouse is clicked so the line breaker does not appear.
      mouseDownFlag = false;

      // Check tags under the mouse to see if the line breaker needs to be shown.
      editor.events.$on(editor.$win, 'mousemove', _mouseTimer);

      // Hide the line breaker if the page is scrolled.
      editor.events.$on($(editor.win), 'scroll', _hide);

      // Hide the line breaker on cell edit.
      editor.events.on('popups.show.table.edit', _hide);

      // Hide the line breaker after command is ran.
      editor.events.on('commands.after', _hide);

      // Prevent line breaker from showing while selecting text or dragging images.
      editor.events.$on($(editor.win), 'mousedown', _mouseDown);

      // Mouse is not pressed anymore, line breaker may be shown.
      editor.events.$on($(editor.win), 'mouseup', _mouseUp);
    }

    return {
      _init: _init
    }
  };

}));
