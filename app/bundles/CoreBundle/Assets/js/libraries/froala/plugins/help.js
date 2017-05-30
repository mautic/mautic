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
    helpSets: [
      {
        title: 'Inline Editor',
        commands: [
          { val: 'OSkeyE',  desc: 'Show the editor' }
        ]
      },
      {
        title: 'Common actions',
        commands: [
          { val: 'OSkeyC',  desc: 'Copy' },
          { val: 'OSkeyX',  desc: 'Cut' },
          { val: 'OSkeyV',  desc: 'Paste' },
          { val: 'OSkeyZ',  desc: 'Undo' },
          { val: 'OSkeyShift+Z',  desc: 'Redo' },
          { val: 'OSkeyK',  desc: 'Insert link' },
          { val: 'OSkeyP',  desc: 'Insert image' }
        ]
      },
      {
        title: 'Basic Formatting',
        commands: [
          { val: 'OSkeyA',  desc: 'Select all' },
          { val: 'OSkeyB',  desc: 'Bold' },
          { val: 'OSkeyI',  desc: 'Italic' },
          { val: 'OSkeyU',  desc: 'Underline' },
          { val: 'OSkeyS',  desc: 'Strikethrough' },
          { val: 'OSkey]',  desc: 'Indent' },
          { val: 'OSkey[',  desc: 'Outdent' }
        ]
      },
      {
        title: 'Quote',
        commands: [
          { val: 'OSkey\'',  desc: 'Increase quote level' },
          { val: 'OSkeyShift+\'',  desc: 'Decrease quote level' }
        ]
      },
      {
        title: 'Image / Video',
        commands: [
          { val: 'OSkey+',  desc: 'Resize larger' },
          { val: 'OSkey-',  desc: 'Resize smaller' }
        ]
      },
      {
        title: 'Table',
        commands: [
          { val: 'Alt+Space',  desc: 'Select table cell' },
          { val: 'Shift+Left/Right arrow',  desc: 'Extend selection one cell' },
          { val: 'Shift+Up/Down arrow',  desc: 'Extend selection one row' }
        ]
      },
      {
        title: 'Navigation',
        commands: [
          { val: 'OSkey/',  desc: 'Shortcuts' },
          { val: 'Alt+F10',  desc: 'Focus popup / toolbar' },
          { val: 'Esc',  desc: 'Return focus to previous position' }
        ]
      }
    ]
  });

  $.FE.PLUGINS.help = function (editor) {
    var $modal;
    var modal_id = 'help';

    var $head;
    var $body;

    /*
     * Init Help.
     */
    function _init () {

    }

    /*
     * Build html body.
     */
    function _buildBody () {
      // Begin body.
      var body = '<div class="fr-help-modal">';

      for (var i = 0; i < $.FE.DEFAULTS.helpSets.length; i++) {
        var set = $.FE.DEFAULTS.helpSets[i];

        // Set shortcuts table.
        // Begin Table.
        var group = '<table>';

        // Set title.
        group += '<thead><tr><th>' + set.title + '</th></tr></thead>';
        group += '<tbody>';

        // Build commands table.
        for (var j = 0; j < set.commands.length; j++) {
          var command = set.commands[j];
          group += '<tr>';

          group += '<td>' + command.desc + '</td>';
          group += '<td>' + command.val.replace('OSkey', editor.helpers.isMac() ? '&#8984;' : 'Ctrl+') + '</td>';

          group += '</tr>';
        }

        // End table.
        group += '</tbody></table>';

        // Append group to body.
        body += group;
      }

      // End body.
      body += '</div>';

      return body;
    }

    /*
     * Show help.
     */
    function show () {
      if (!$modal) {
        var head = '<h4>Shortcuts</h4>';
        var body = _buildBody();

        var modalHash = editor.modals.create(modal_id, head, body);
        $modal = modalHash.$modal;
        $head = modalHash.$head;
        $body = modalHash.$body;

        // Resize help modal on window resize.
        editor.events.$on($(editor.o_win), 'resize', function () {
          editor.modals.resize(modal_id);
        })
      }

      // Show modal.
      editor.modals.show(modal_id);

      // Modal may not fit window size.
      editor.modals.resize(modal_id);
    }

    /*
     * Hide help.
     */
    function hide () {
      editor.modals.hide(modal_id);
    }

    return {
      _init: _init,
      show: show,
      hide: hide
    };
  };

  $.FroalaEditor.DefineIcon('help', { NAME: 'question' })

  $.FE.RegisterShortcut($.FE.KEYCODE.SLASH, 'help', null, '/');

  $.FE.RegisterCommand('help', {
    title: 'Help',
    icon: 'help',
    undo: false,
    focus: false,
    modal: true,
    callback: function () {
      this.help.show();
    },
    plugin: 'help',
    showOnMobile: false
  });

}));
