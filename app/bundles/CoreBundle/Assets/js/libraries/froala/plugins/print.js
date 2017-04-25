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

  

  $.FE.PLUGINS.print = function (editor) {
    function run () {
      // Get editor content for printing.
      var contents = editor.$el.html();

      // Get or create the iframe for printing.
      var print_iframe = null;
      if (editor.shared.print_iframe) {
        print_iframe = editor.shared.print_iframe;
      }
      else {
        print_iframe = document.createElement('iframe');
        print_iframe.name = 'fr-print';
        print_iframe.style.position = 'fixed';
        print_iframe.style.top = '0';
        print_iframe.style.left = '-9999px';
        print_iframe.style.height = '100%';
        print_iframe.style.width = '0';
        print_iframe.style.overflow = 'hidden';
        print_iframe.style['z-index'] = '9999';
        print_iframe.style.tabIndex = '-1';
        document.body.appendChild(print_iframe);

        // Iframe ready.
        print_iframe.onload = function () {
          setTimeout(function () {
            // Focus iframe window.
            editor.events.disableBlur();
            window.frames['fr-print'].focus();

            // Open printing window.
            window.frames['fr-print'].print();

            // Refocus editor's window.
            editor.$win.get(0).focus();

            // Focus editor.
            editor.events.disableBlur();
            editor.events.focus();
          }, 0);
        };

        editor.shared.print_iframe = print_iframe;
      }

      // Build printing document.
      var frame_doc = print_iframe.contentWindow;
      frame_doc.document.open();
      frame_doc.document.write('<!DOCTYPE html><html><head><title>' + document.title + '</title>');

      // Add styles.
      Array.prototype.forEach.call(document.querySelectorAll('style'), function (style_el) {
        style_el = style_el.cloneNode(true);
        frame_doc.document.write(style_el.outerHTML);
      });

      // Add css links.
      var style_elements = document.querySelectorAll('link[rel=stylesheet]');
      Array.prototype.forEach.call(style_elements, function (link_el) {
        var new_link_el = document.createElement('link');
        new_link_el.rel  = link_el.rel;
        new_link_el.href = link_el.href;
        new_link_el.media = 'print';
        new_link_el.type = 'text/css';
        new_link_el.media = 'all';
        frame_doc.document.write(new_link_el.outerHTML);
      });

      frame_doc.document.write('</head><body style="text-align: ' + (editor.opts.direction == 'rtl' ? 'right' : 'left') + '; direction: ' + editor.opts.direction + ';"><div class="fr-view">');

      // Add editor contents.
      frame_doc.document.write(contents);

      frame_doc.document.write('</div></body></html>');
      frame_doc.document.close();
    }

    return {
      run: run
    }
  }

  $.FE.DefineIcon('print', { NAME: 'print' });
  $.FE.RegisterCommand('print', {
    title: 'Print',
    undo: false,
    focus: false,
    plugin: 'print',
    callback: function () {
      this.print.run();
    }
  });

}));
