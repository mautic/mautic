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

  

  $.FE.PLUGINS.fullscreen = function (editor) {
    var old_scroll;

    /**
     * Check if fullscreen mode is active.
     */
    function isActive () {
      return editor.$box.hasClass('fr-fullscreen');
    }

    /**
     * Turn fullscreen on.
     */
    var $placeholder;
    var height;
    var max_height;
    var z_index;
    function _on () {
      old_scroll = $(editor.o_win).scrollTop();
      editor.$box.toggleClass('fr-fullscreen');
      $('body').toggleClass('fr-fullscreen');
      $placeholder = $('<div style="display: none;"></div>');
      editor.$box.after($placeholder);

      if (editor.helpers.isMobile()) {
        editor.$tb.data('parent', editor.$tb.parent());
        editor.$tb.prependTo(editor.$box);
        if (editor.$tb.data('sticky-dummy')) {
          editor.$tb.after(editor.$tb.data('sticky-dummy'));
        }
      }

      height = editor.opts.height;
      max_height = editor.opts.heightMax;
      z_index = editor.opts.zIndex;

      editor.opts.height = editor.o_win.innerHeight - (editor.opts.toolbarInline ? 0 : editor.$tb.outerHeight());
      editor.opts.zIndex = 9990;
      editor.opts.heightMax = null;
      editor.size.refresh();

      if (editor.opts.toolbarInline) editor.toolbar.showInline();

      var $parent_node = editor.$box.parent();
      while (!$parent_node.is('body')) {
        $parent_node
          .data('z-index', $parent_node.css('z-index'))
          .css('z-index', '9990');
        $parent_node = $parent_node.parent();
      }

      editor.events.trigger('charCounter.update');
      editor.$win.trigger('scroll');
    }

    /**
     * Turn fullscreen off.
     */
    function _off () {
      editor.$box.toggleClass('fr-fullscreen');
      $('body').toggleClass('fr-fullscreen');

      editor.$tb.prependTo(editor.$tb.data('parent'));
      if (editor.$tb.data('sticky-dummy')) {
        editor.$tb.after(editor.$tb.data('sticky-dummy'));
      }

      editor.opts.height = height;
      editor.opts.heightMax = max_height;
      editor.opts.zIndex = z_index;
      editor.size.refresh();

      $(editor.o_win).scrollTop(old_scroll)

      if (editor.opts.toolbarInline) editor.toolbar.showInline();

      editor.events.trigger('charCounter.update');

      if (editor.opts.toolbarSticky) {
        if (editor.opts.toolbarStickyOffset) {
          if (editor.opts.toolbarBottom) {
            editor.$tb
              .css('bottom', editor.opts.toolbarStickyOffset)
              .data('bottom', editor.opts.toolbarStickyOffset);
          }
          else {
            editor.$tb
              .css('top', editor.opts.toolbarStickyOffset)
              .data('top', editor.opts.toolbarStickyOffset);
          }
        }
      }

      var $parent_node = editor.$box.parent();
      while (!$parent_node.is('body')) {
        if ($parent_node.data('z-index')) {
          $parent_node.css('z-index', '');
          if ($parent_node.css('z-index') != $parent_node.data('z-index')) {
            $parent_node.css('z-index', $parent_node.data('z-index'));
          }
          $parent_node.removeData('z-index');
        }

        $parent_node = $parent_node.parent();
      }

      editor.$win.trigger('scroll');
    }

    /**
     * Exec fullscreen.
     */
    function toggle () {
      if (!isActive()) {
        _on();
      }
      else {
        _off();
      }

      refresh(editor.$tb.find('.fr-command[data-cmd="fullscreen"]'));
    }

    function refresh ($btn) {
      var active = isActive();

      $btn.toggleClass('fr-active', active).attr('aria-pressed', active);
      $btn.find('> *:not(.fr-sr-only)').replaceWith(!active ? editor.icon.create('fullscreen') : editor.icon.create('fullscreenCompress'));
    }

    function _init () {
      if (!editor.$wp) return false;

      editor.events.$on($(editor.o_win), 'resize', function () {
        if (isActive()) {
          _off();
          _on();
        }
      });

      editor.events.on('toolbar.hide', function () {
        if (isActive() && editor.helpers.isMobile()) return false;
      })
    }

    return {
      _init: _init,
      toggle: toggle,
      refresh: refresh,
      isActive: isActive
    }
  }

  // Register the font size command.
  $.FE.RegisterCommand('fullscreen', {
    title: 'Fullscreen',
    undo: false,
    focus: false,
    accessibilityFocus: true,
    forcedRefresh: true,
    toggle: true,
    callback: function () {
      this.fullscreen.toggle();
    },
    refresh: function ($btn) {
      this.fullscreen.refresh($btn);
    },
    plugin: 'fullscreen'
  })

  // Add the font size icon.
  $.FE.DefineIcon('fullscreen', {
    NAME: 'expand'
  });
  $.FE.DefineIcon('fullscreenCompress', {
    NAME: 'compress'
  });

}));
