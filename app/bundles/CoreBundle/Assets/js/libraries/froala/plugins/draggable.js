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

  
  // Extend defaults.
  $.extend($.FE.DEFAULTS, {
    dragInline: true
  });


  $.FE.PLUGINS.draggable = function (editor) {
    function _dragStart (e) {
      if (e.originalEvent && e.originalEvent.target && e.originalEvent.target.nodeType == Node.TEXT_NODE) {
        return true;
      }

      // Image with link.
      if (e.target && e.target.tagName == 'A' && e.target.childNodes.length == 1 && e.target.childNodes[0].tagName == 'IMG') {
        e.target = e.target.childNodes[0];
      }

      if (!$(e.target).hasClass('fr-draggable')) {
        e.preventDefault();
        return false;
      }

      // Save in undo step if we cannot do.
      if (!editor.undo.canDo()) {
        editor.undo.saveStep();
      }

      if (editor.opts.dragInline) {
        editor.$el.attr('contenteditable', true);
      }
      else {
        editor.$el.attr('contenteditable', false);
      }

      if (editor.opts.toolbarInline) editor.toolbar.hide();

      $(e.target).addClass('fr-dragging');

      if (!editor.browser.msie && !editor.browser.edge) {
        editor.selection.clear();
      }

      e.originalEvent.dataTransfer.setData('text', 'Froala');
    }

    function _tagOK (tag_under) {
      return !(tag_under && (tag_under.tagName == 'HTML' || tag_under.tagName == 'BODY' || editor.node.isElement(tag_under)));
    }

    function _setHelperSize (top, left, width) {
      if (editor.opts.iframe) {
        top += editor.$iframe.offset().top;
        left += editor.$iframe.offset().left;
      }

      if ($draggable_helper.offset().top != top) $draggable_helper.css('top', top);
      if ($draggable_helper.offset().left != left) $draggable_helper.css('left', left);
      if ($draggable_helper.width() != width) $draggable_helper.css('width', width);
    }

    function _positionHelper (e) {
      // The tag under the mouse cursor.
      var tag_under = editor.doc.elementFromPoint(e.originalEvent.pageX - editor.win.pageXOffset, e.originalEvent.pageY - editor.win.pageYOffset);
      if (!_tagOK(tag_under)) {

        // Look above for the closest tag.
        var top_offset = 0;
        var top_tag = tag_under;
        while (!_tagOK(top_tag) && top_tag == tag_under && e.originalEvent.pageY - editor.win.pageYOffset - top_offset > 0) {
          top_offset++;
          top_tag = editor.doc.elementFromPoint(e.originalEvent.pageX - editor.win.pageXOffset, e.originalEvent.pageY - editor.win.pageYOffset - top_offset);
        }
        if (!_tagOK(top_tag) || ($draggable_helper && editor.$el.find(top_tag).length === 0 && top_tag != $draggable_helper.get(0))) { top_tag = null; }

        // Look below for the closest tag.
        var bottom_offset = 0;
        var bottom_tag = tag_under;
        while (!_tagOK(bottom_tag) && bottom_tag == tag_under && e.originalEvent.pageY - editor.win.pageYOffset + bottom_offset < $(editor.doc).height()) {
          bottom_offset++;
          bottom_tag = editor.doc.elementFromPoint(e.originalEvent.pageX - editor.win.pageXOffset, e.originalEvent.pageY - editor.win.pageYOffset + bottom_offset);
        }

        if (!_tagOK(bottom_tag) || ($draggable_helper &&  editor.$el.find(bottom_tag).length === 0  && bottom_tag != $draggable_helper.get(0))) { bottom_tag = null; }

        if (bottom_tag == null && top_tag) tag_under = top_tag;
        else if (bottom_tag && top_tag == null) tag_under = bottom_tag;
        else if (bottom_tag && top_tag) {
          tag_under = (top_offset < bottom_offset ? top_tag : bottom_tag);
        }
        else {
          tag_under = null;
        }
      }

      // Stop if tag under is draggable helper.
      if ($(tag_under).hasClass('fr-drag-helper')) return false;

      // Get block parent.
      if (tag_under && !editor.node.isBlock(tag_under)) {
        tag_under = editor.node.blockParent(tag_under);
      }

      // Normalize TABLE parent.
      if (tag_under && ['TD', 'TH', 'TR', 'THEAD', 'TBODY'].indexOf(tag_under.tagName) >= 0) {
        tag_under = $(tag_under).parents('table').get(0);
      }

      // Normalize LIST parent.
      if (tag_under && ['LI'].indexOf(tag_under.tagName) >= 0) {
        tag_under = $(tag_under).parents('UL, OL').get(0);
      }

      if (tag_under && !$(tag_under).hasClass('fr-drag-helper')) {
        // Init helper.
        if (!$draggable_helper) {
          if (!$.FE.$draggable_helper) $.FE.$draggable_helper = $('<div class="fr-drag-helper"></div>');

          $draggable_helper = $.FE.$draggable_helper;

          editor.events.on('shared.destroy', function () {
            $draggable_helper.html('').removeData().remove();
            $draggable_helper = null;
          }, true);
        }

        var above;
        var mouse_y = e.originalEvent.pageY;

        if (mouse_y < $(tag_under).offset().top + $(tag_under).outerHeight() / 2) above = true;
        else above = false;

        var $tag_under = $(tag_under);
        var margin = 0 ;

        // Should go below and there is no tag below.
        if (!above && $tag_under.next().length === 0) {
          if ($draggable_helper.data('fr-position') != 'after' || !$tag_under.is($draggable_helper.data('fr-tag'))) {
            margin = parseFloat($tag_under.css('margin-bottom')) || 0;

            _setHelperSize(
              $tag_under.offset().top + $(tag_under).height() + margin / 2  - editor.$box.offset().top,
              $tag_under.offset().left - editor.win.pageXOffset - editor.$box.offset().left,
              $tag_under.width()
            );

            $draggable_helper.data('fr-position', 'after');
          }
        }
        else {
          // Should go below then we take the next tag.
          if (!above) {
            $tag_under = $tag_under.next();
          }

          if ($draggable_helper.data('fr-position') != 'before' || !$tag_under.is($draggable_helper.data('fr-tag'))) {
            if ($tag_under.prev().length > 0) {
              margin = parseFloat($tag_under.prev().css('margin-bottom')) || 0;
            }
            margin = Math.max(margin, parseFloat($tag_under.css('margin-top')) || 0);

            _setHelperSize(
              $tag_under.offset().top - margin / 2  - editor.$box.offset().top,
              $tag_under.offset().left - editor.win.pageXOffset  - editor.$box.offset().left,
              $tag_under.width()
            )

            $draggable_helper.data('fr-position', 'before');
          }
        }

        $draggable_helper.data('fr-tag', $tag_under);

        $draggable_helper.addClass('fr-visible');
        $draggable_helper.appendTo(editor.$box);
      }
      else if ($draggable_helper && editor.$box.find($draggable_helper).length > 0) {
        $draggable_helper.removeClass('fr-visible');
      }
    }

    function _dragOver (e) {
      e.originalEvent.dataTransfer.dropEffect = 'move';

      if (!editor.opts.dragInline) {
        e.preventDefault();

        _positionHelper(e);
      }

      else if (!_getDraggedEl() && (editor.browser.msie || editor.browser.edge)) {
        e.preventDefault();
      }
    }

    function _dragEnter (e) {
      e.originalEvent.dataTransfer.dropEffect = 'move';

      if (!editor.opts.dragInline) {
        e.preventDefault();
      }
    }

    function _documentDragEnd (e) {
      editor.$el.attr('contenteditable', true);
      var $draggedEl = editor.$el.find('.fr-dragging');

      if ($draggable_helper && $draggable_helper.hasClass('fr-visible') && editor.$box.find($draggable_helper).length) {
        _drop(e);
      }
      else if ($draggedEl.length) {
        e.preventDefault();
        e.stopPropagation();
      }

      if ($draggable_helper && editor.$box.find($draggable_helper).length) {
        $draggable_helper.removeClass('fr-visible');
      }

      $draggedEl.removeClass('fr-dragging');
    }

    function _getDraggedEl () {
      var $draggedEl = null;

      // Search of the instance we're dragging from.
      for (var i = 0; i < $.FE.INSTANCES.length; i++) {
        $draggedEl = $.FE.INSTANCES[i].$el.find('.fr-dragging');
        if ($draggedEl.length) {
          return $draggedEl.get(0);
        }
      }
    }

    function _drop (e) {
      var $draggedEl;
      var inst;

      // Inst is the intance we're dragging from.
      for (var i = 0; i < $.FE.INSTANCES.length; i++) {
        $draggedEl = $.FE.INSTANCES[i].$el.find('.fr-dragging');
        if ($draggedEl.length) {
          inst = $.FE.INSTANCES[i];
          break;
        }
      }

      // There is a dragged element.
      if ($draggedEl.length) {
        // Cancel anything else.
        e.preventDefault();
        e.stopPropagation();

        // Look for draggable helper.
        if ($draggable_helper && $draggable_helper.hasClass('fr-visible') && editor.$box.find($draggable_helper).length) {
          $draggable_helper.data('fr-tag')[$draggable_helper.data('fr-position')]('<span class="fr-marker"></span>');
          $draggable_helper.removeClass('fr-visible');
        }
        else {
          var ok = editor.markers.insertAtPoint(e.originalEvent);
          if (ok === false) return false;
        }

        // Remove dragging class.
        $draggedEl.removeClass('fr-dragging');

        // Image with link.
        var $droppedEl = $draggedEl;
        if ($draggedEl.parent().is('A')) {
          $droppedEl = $draggedEl.parent();
        }

        // Replace marker with the dragged element.
        if (!editor.core.isEmpty()) {
          var $marker = editor.$el.find('.fr-marker');
          $marker.replaceWith($.FE.MARKERS);
          editor.selection.restore();
        }
        else {
          editor.events.focus();
        }

        // Save undo step if the current instance is different than the original one.
        if (inst != editor && !editor.undo.canDo()) editor.undo.saveStep();

        // Place new elements.
        if (!editor.core.isEmpty()) {
          var marker = editor.markers.insert();
          $(marker).replaceWith($droppedEl);
          $draggedEl.after($.FE.MARKERS);
          editor.selection.restore();
        }
        else {
          editor.$el.html($droppedEl);
        }

        // Hide all popups.
        editor.popups.hideAll();
        editor.selection.save();
        editor.$el.find(editor.html.emptyBlockTagsQuery()).not('TD, TH, LI, .fr-inner').remove();
        editor.html.wrap();
        editor.html.fillEmptyBlocks();
        editor.selection.restore();
        editor.undo.saveStep();
        if (editor.opts.iframe) editor.size.syncIframe();

        // Mark changes in the original instance as well.
        if (inst != editor) {
          inst.popups.hideAll();
          inst.$el.find(inst.html.emptyBlockTagsQuery()).not('TD, TH, LI, .fr-inner').remove();
          inst.html.wrap();
          inst.html.fillEmptyBlocks();
          inst.undo.saveStep();
          inst.events.trigger('element.dropped');
          if (inst.opts.iframe) inst.size.syncIframe();
        }

        editor.events.trigger('element.dropped', [$droppedEl]);

        // Stop bubbling.
        return false;
      }
    }

    /**
     * Do cleanup when the html is taken.
     */
    function _cleanOnGet(el) {
      // Remove drag helper.
      if (el && el.tagName == 'DIV' && editor.node.hasClass(el, 'fr-drag-helper')) {
        el.parentNode.removeChild(el);
      }
      // Remove from nested elements too.
      else if (el && el.nodeType == Node.ELEMENT_NODE) {
        var els = el.querySelectorAll('div.fr-drag-helper');
        for (var i = 0; i < els.length; i++) {
          els[i].parentNode.removeChild(els[i]);
        }
      }
    }

    /*
     * Initialize.
     */
    var $draggable_helper;
    function _init () {
      // Force drag inline when ENTER_BR is active.
      if (editor.opts.enter == $.FE.ENTER_BR) editor.opts.dragInline = true;

      // Starting to drag.
      editor.events.on('dragstart', _dragStart, true);

      // Inline dragging is off.
      editor.events.on('dragover', _dragOver, true);
      editor.events.on('dragenter', _dragEnter, true);

      // Document drop. Remove moving class.
      editor.events.on('document.dragend', _documentDragEnd, true);
      editor.events.on('document.drop', _documentDragEnd, true);

      // Drop.
      editor.events.on('drop', _drop, true);

      // Clean getting the HTML.
      editor.events.on('html.processGet', _cleanOnGet)
    }

    return {
      _init: _init
    }
  }

}));
