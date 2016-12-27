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

  

  $.extend($.FE.DEFAULTS, {
    codeMirror: true,
    codeMirrorOptions: {
      lineNumbers: true,
      tabMode: 'indent',
      indentWithTabs: true,
      lineWrapping: true,
      mode: 'text/html',
      tabSize: 2
    },
    codeBeautifierOptions: {
      end_with_newline: true,
      indent_inner_html: true,
      extra_liners: ['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'blockquote', 'pre', 'ul', 'ol', 'table', 'dl'],
      brace_style: 'expand',
      indent_char: '\t',
      indent_size: 1,
      wrap_line_length: 0
    },
    codeViewKeepActiveButtons: ['fullscreen']
  })

  $.FE.PLUGINS.codeView = function (editor) {
    var $html_area;
    var code_mirror;

    /**
     * Check if code view is enabled.
     */
    function isActive () {
      return editor.$box.hasClass('fr-code-view');
    }

    function get () {
      if (code_mirror) {
        return code_mirror.getValue();
      } else {
        return $html_area.val();
      }
    }

    /**
     * Get back to edit mode.
     */
    function _showText ($btn) {
      var html = get();

      // Code mirror enabled.
      editor.html.set(html);

      // Blur the element.
      editor.$el.blur();

      // Toolbar no longer disabled.
      editor.$tb.find(' > .fr-command').not($btn).removeClass('fr-disabled').attr('aria-disabled', false);
      $btn.removeClass('fr-active').attr('aria-pressed', false);

      editor.events.focus(true);
      editor.placeholder.refresh();

      editor.undo.saveStep();
    }

    /**
     * Get to code mode.
     */
    function _showHTML ($btn) {
      if (!$html_area) {
        _initArea();

        // Enable code mirror.
        if (!code_mirror && editor.opts.codeMirror && typeof CodeMirror != 'undefined') {
          code_mirror = CodeMirror.fromTextArea($html_area.get(0), editor.opts.codeMirrorOptions);
        }
        else {
          editor.events.$on($html_area, 'keydown keyup change input', function () {
            if (!editor.opts.height) {
              if (!this.rows) {
                this.rows = 1;
              }

              // Textarea has no content anymore.
              if (this.value.length === 0) {
                this.rows = 1;
              }

              else {
                this.style.height = 'auto';

                // Decrease height in case text is deleted.
                while (this.rows > 1 && this.scrollHeight <= this.offsetHeight) {
                  this.rows -= 1;
                }

                // Increase textarea height.
                while (this.scrollHeight > this.offsetHeight && (!editor.opts.heightMax || this.offsetHeight < editor.opts.heightMax)) {
                  this.rows += 1;
                }
              }
            }
            else {
              this.rows = null;
            }
          });
        }
      }

      editor.undo.saveStep();

      // Clean white tags but ignore selection.
      editor.html.cleanEmptyTags();
      editor.html.cleanWhiteTags(true);

      // Blur the element.
      if (editor.core.hasFocus()) {
        if (!editor.core.isEmpty()) {
          editor.selection.save();
          editor.$el.find('.fr-marker[data-type="true"]:first').replaceWith('<span class="fr-tmp fr-sm">F</span>');
          editor.$el.find('.fr-marker[data-type="false"]:last').replaceWith('<span class="fr-tmp fr-em">F</span>');
        }
      }

      // Get HTML.
      var html = editor.html.get(false, true);
      editor.$el.find('span.fr-tmp').remove();

      editor.$box.toggleClass('fr-code-view', true);

      if (editor.core.hasFocus()) editor.$el.blur();

      html = html.replace(/<span class="fr-tmp fr-sm">F<\/span>/, 'FROALA-SM');
      html = html.replace(/<span class="fr-tmp fr-em">F<\/span>/, 'FROALA-EM');

      // Beautify HTML.
      if (editor.codeBeautifier) {
        html = editor.codeBeautifier.run(html, editor.opts.codeBeautifierOptions);
      }

      var s_index;
      var e_index;

      // Code mirror is enabled.
      if (code_mirror) {
        s_index = html.indexOf('FROALA-SM');
        e_index = html.indexOf('FROALA-EM');

        if (s_index > e_index) {
          s_index = e_index;
        }
        else {
          e_index = e_index - 9;
        }

        html = html.replace(/FROALA-SM/g, '').replace(/FROALA-EM/g, '')
        var s_line = html.substring(0, s_index).length - html.substring(0, s_index).replace(/\n/g, '').length;
        var e_line = html.substring(0, e_index).length - html.substring(0, e_index).replace(/\n/g, '').length;

        s_index = html.substring(0, s_index).length - html.substring(0, html.substring(0, s_index).lastIndexOf('\n') + 1).length;
        e_index = html.substring(0, e_index).length - html.substring(0, html.substring(0, e_index).lastIndexOf('\n')  + 1).length;

        code_mirror.setSize(null, editor.opts.height ? editor.opts.height : 'auto');
        if (editor.opts.heightMin) editor.$box.find('.CodeMirror-scroll').css('min-height', editor.opts.heightMin);
        code_mirror.setValue(html);
        code_mirror.focus();
        code_mirror.setSelection({ line: s_line, ch: s_index }, { line: e_line, ch: e_index })
        code_mirror.refresh();
        code_mirror.clearHistory();
      }

      // No code mirror.
      else {
        s_index = html.indexOf('FROALA-SM');
        e_index = html.indexOf('FROALA-EM') - 9;

        if (editor.opts.heightMin) {
          $html_area.css('min-height', editor.opts.heightMin);
        }

        if (editor.opts.height) {
          $html_area.css('height', editor.opts.height);
        }

        if (editor.opts.heightMax) {
          $html_area.css('max-height', editor.opts.height || editor.opts.heightMax);
        }

        $html_area.val(html.replace(/FROALA-SM/g, '').replace(/FROALA-EM/g, '')).trigger('change');
        var scroll_top = $(editor.o_doc).scrollTop();
        $html_area.focus();
        $html_area.get(0).setSelectionRange(s_index, e_index);
        $(editor.o_doc).scrollTop(scroll_top);
      }

      // Disable buttons.
      editor.$tb.find(' > .fr-command').not($btn).filter(function () {
        return editor.opts.codeViewKeepActiveButtons.indexOf($(this).data('cmd')) < 0;
      }).addClass('fr-disabled').attr('aria-disabled', true);
      $btn.addClass('fr-active').attr('aria-pressed', true);

      if (!editor.helpers.isMobile() && editor.opts.toolbarInline) {
        editor.toolbar.hide();
      }
    }

    /**
     * Toggle the code view.
     */
    function toggle (val) {
      if (typeof val == 'undefined') val = !isActive();

      var $btn = editor.$tb.find('.fr-command[data-cmd="html"]');

      if (!val) {
        editor.$box.toggleClass('fr-code-view', false);
        _showText($btn);
      } else {
        editor.popups.hideAll();
        _showHTML($btn);
      }
    }

    /**
     * Destroy.
     */
    function _destroy () {
      if (isActive()) {
        toggle(editor.$tb.find('button[data-cmd="html"]'));
      }

      if (code_mirror) code_mirror.toTextArea();
      $html_area.val('').removeData().remove();
      $html_area = null;

      if ($back_button) {
        $back_button.remove();
        $back_button = null;
      }
    }

    function _initArea () {
      // Add the coding textarea to the wrapper.
      $html_area = $('<textarea class="fr-code" tabIndex="-1">');
      editor.$wp.append($html_area);

      $html_area.attr('dir', editor.opts.direction);

      // Exit code view button for inline toolbar.
      if (!editor.$box.hasClass('fr-basic')) {
        $back_button = $('<a data-cmd="html" title="Code View" class="fr-command fr-btn html-switch' + (editor.helpers.isMobile() ? '' : ' fr-desktop') + '" role="button" tabIndex="-1"><i class="fa fa-code"></i></button>');
        editor.$box.append($back_button);

        editor.events.bindClick(editor.$box, 'a.html-switch', function () {
          toggle(false);
        });
      }

      var cancel = function () {
        return !isActive();
      }

      // Disable refresh of the buttons while enabled.
      editor.events.on('buttons.refresh', cancel);
      editor.events.on('copy', cancel, true);
      editor.events.on('cut', cancel, true);
      editor.events.on('paste', cancel, true);

      editor.events.on('destroy', _destroy, true);

      editor.events.on('html.set', function () {
        if (isActive()) toggle(true);
      });

      editor.events.on('form.submit', function () {
        if (isActive()) {
          // Code mirror enabled.
          editor.html.set(get());

          editor.events.trigger('contentChanged', [], true);
        }
      }, true);
    }

    /**
     * Initialize.
     */
    var $back_button;
    function _init () {
      if (!editor.$wp) return false;
    }

    return {
      _init: _init,
      toggle: toggle,
      isActive: isActive,
      get: get
    }
  };

  $.FE.RegisterCommand('html', {
    title: 'Code View',
    undo: false,
    focus: false,
    forcedRefresh: true,
    toggle: true,
    callback: function () {
      this.codeView.toggle();
    },
    plugin: 'codeView'
  })

  $.FE.DefineIcon('html', {
    NAME: 'code'
  });

}));
