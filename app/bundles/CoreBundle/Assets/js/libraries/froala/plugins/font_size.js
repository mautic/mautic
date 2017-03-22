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

  

  $.extend($.FE.DEFAULTS, {
    fontSize: ['8', '9', '10', '11', '12', '14', '18', '24', '30', '36', '48', '60', '72', '96'],
    fontSizeSelection: false,
    fontSizeDefaultSelection: '12'
  });

  $.FE.PLUGINS.fontSize = function (editor) {
    function apply (val) {
      editor.format.applyStyle('font-size', val);
    }

    function refreshOnShow($btn, $dropdown) {
      var val = $(editor.selection.element()).css('font-size');
      $dropdown.find('.fr-command.fr-active').removeClass('fr-active').attr('aria-selected', false);
      $dropdown.find('.fr-command[data-param1="' + val + '"]').addClass('fr-active').attr('aria-selected', true);

      var $list = $dropdown.find('.fr-dropdown-list');
      var $active = $dropdown.find('.fr-active').parent();
      if ($active.length) {
        $list.parent().scrollTop($active.offset().top - $list.offset().top - ($list.parent().outerHeight() / 2 - $active.outerHeight() / 2));
      }
      else {
        $list.parent().scrollTop(0);
      }
    }

    function refresh ($btn) {
      if (editor.opts.fontSizeSelection) {
        var val = editor.helpers.getPX($(editor.selection.element()).css('font-size'));
        $btn.find('> span').text(val);
      }
    }

    return {
      apply: apply,
      refreshOnShow: refreshOnShow,
      refresh: refresh
    }
  }

  // Register the font size command.
  $.FE.RegisterCommand('fontSize', {
    type: 'dropdown',
    title: 'Font Size',
    displaySelection: function (editor) {
      return editor.opts.fontSizeSelection;
    },
    displaySelectionWidth: 30,
    defaultSelection: function (editor) {
      return editor.opts.fontSizeDefaultSelection;
    },
    html: function () {
      var c = '<ul class="fr-dropdown-list" role="presentation">';
      var options =  this.opts.fontSize;
      for (var i = 0; i < options.length; i++) {
        var val = options[i];
        c += '<li role="presentation"><a class="fr-command" tabIndex="-1" role="option" data-cmd="fontSize" data-param1="' + val + 'px" title="' + val + '">' + val + '</a></li>';
      }
      c += '</ul>';

      return c;
    },
    callback: function (cmd, val) {
      this.fontSize.apply(val);
    },
    refresh: function ($btn) {
      this.fontSize.refresh($btn);
    },
    refreshOnShow: function ($btn, $dropdown) {
      this.fontSize.refreshOnShow($btn, $dropdown);
    },
    plugin: 'fontSize'
  })

  // Add the font size icon.
  $.FE.DefineIcon('fontSize', {
    NAME: 'text-height'
  });

}));
