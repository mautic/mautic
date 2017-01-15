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

  

  $.FE.PLUGINS.lists = function (editor) {
    function _openFlag(tag_name) {
      return '<span class="fr-open-' + tag_name.toLowerCase() + '"></span>';
    }

    function _closeFlag(tag_name) {
      return '<span class="fr-close-' + tag_name.toLowerCase() + '"></span>';
    }

    /**
     * Replace list type.
     */
    function _replace(blocks, tag_name) {
      var lists = [];
      for (var i = 0; i < blocks.length; i++) {
        var parent_node = blocks[i].parentNode;
        if (blocks[i].tagName == 'LI' && parent_node.tagName != tag_name && lists.indexOf(parent_node) < 0) {
          lists.push(parent_node);
        }
      }

      for (i = lists.length - 1; i >= 0; i--) {
        var $l = $(lists[i]);
        $l.replaceWith('<' + tag_name.toLowerCase() + '>' + $l.html() + '</' + tag_name.toLowerCase() + '>');
      }
    }

    /**
     * Format blocks.
     */
    function _format(blocks, tag_name) {
      _replace(blocks, tag_name);

      // Format those blocks that are not LI.
      var default_tag = editor.html.defaultTag();
      for (var i = 0; i < blocks.length; i++) {
        if (blocks[i].tagName != 'LI') {
          // Default tag.
          if (default_tag && blocks[i].tagName.toLowerCase() == default_tag) {
            $(blocks[i]).replaceWith('<' + tag_name + '><li' + editor.node.attributes(blocks[i]) + '>' + $(blocks[i]).html() + '</li></' + tag_name + '>');
          }
          else {
            $(blocks[i]).wrap('<' + tag_name + '><li></li></' + tag_name + '>');
          }
        }
      }

      editor.clean.lists();
    }

    /**
     * Unformat.
     */
    function _unformat(blocks) {
      var i;
      var j;

      // If there are LI that have parents selected, then remove them.
      for (i = blocks.length - 1; i >= 0; i--) {
        for (j = i - 1; j >= 0; j--) {
          if ($(blocks[j]).find(blocks[i]).length || blocks[j] == blocks[i]) {
            blocks.splice(i, 1);
            break;
          }
        }
      }

      // Unwrap remaining LI.
      var lists = [];
      for (i = 0; i < blocks.length; i++) {
        var $li = $(blocks[i]);
        var parent_node = blocks[i].parentNode;

        $li.before(_closeFlag(parent_node.tagName));

        // Nested case.
        if (parent_node.parentNode.tagName == 'LI') {
          $li.before(_closeFlag('LI'));
          $li.after(_openFlag('LI'));
        }
        else {
          // Append BR if the node is not empty.
          if (!editor.node.isEmpty($li.get(0), true) && $li.find(editor.html.blockTagsQuery()).length === 0) {
            $li.append('<br>');
          }
          $li.append(_openFlag('LI'));
          $li.prepend(_closeFlag('LI'));
        }

        $li.after(_openFlag(parent_node.tagName));

        // Nested case. We should look for an upper parent.
        if (parent_node.parentNode.tagName == 'LI') {
          parent_node = parent_node.parentNode.parentNode;
        }

        if (lists.indexOf(parent_node) < 0) {
          lists.push(parent_node);
        }
      }

      // Replace the open and close tags.
      for (i = 0; i < lists.length; i++) {
        var $l = $(lists[i]);
        var html = $l.html();
        html = html.replace(/<span class="fr-close-([a-z]*)"><\/span>/g, '</$1>');
        html = html.replace(/<span class="fr-open-([a-z]*)"><\/span>/g, '<$1>');
        $l.replaceWith(editor.node.openTagString($l.get(0)) + html + editor.node.closeTagString($l.get(0)));
      }

      // Clean empty lists.
      editor.$el.find('li:empty').remove();
      editor.$el.find('ul:empty, ol:empty').remove();

      editor.clean.lists();
      editor.html.wrap();
    }

    /**
     * Check if should unformat lists.
     */
    function _shouldUnformat(blocks, tag_name) {
      var do_unformat = true;
      for (var i = 0; i < blocks.length; i++) {
        // Something else than LI is selected.
        if (blocks[i].tagName != 'LI') {
          return false;
        }

        // There is a different kind of list selected. Replace is the appropiate action.
        if (blocks[i].parentNode.tagName != tag_name) {
          do_unformat = false;
        }
      }

      return do_unformat;
    }

    /**
     * Call the list actions.
     */
    function format(tag_name) {
      // Wrap.
      editor.selection.save();
      editor.html.wrap(true, true, true, true);
      editor.selection.restore();

      var blocks = editor.selection.blocks();

      // Normalize nodes by keeping the LI.
      // <li><h1>foo<h1></li> will return h1.
      for (var i = 0; i < blocks.length; i++) {
        if (blocks[i].tagName != 'LI' && blocks[i].parentNode.tagName == 'LI') {
          blocks[i] = blocks[i].parentNode;
        }
      }

      // Save selection so that we can play at wish.
      editor.selection.save();

      // Decide if to format or unformat list.
      if (_shouldUnformat(blocks, tag_name)) {
        _unformat(blocks);
      }
      else {
        _format(blocks, tag_name);
      }

      // Unwrap.
      editor.html.unwrap();

      // Restore the selection.
      editor.selection.restore();
    }

    /**
     * Refresh list buttons.
     */
    function refresh($btn, tag_name) {
      var $el = $(editor.selection.element());
      if ($el.get(0) != editor.el) {
        var li = $el.get(0);
        if (li.tagName != 'LI') {
          li = $el.parents('li').get(0);
        }

        if (li && li.parentNode.tagName == tag_name && editor.el.contains(li.parentNode)) {
          $btn.addClass('fr-active');
        }
      }
    }

    /**
     * Indent selected list items.
     */
    function _indent (blocks) {
      editor.selection.save();
      for (var i = 0; i < blocks.length; i++) {
        // There should be a previous li.
        var prev_li = blocks[i].previousSibling;
        if (prev_li) {
          var nl = $(blocks[i]).find('> ul, > ol').last().get(0);

          // Current LI has a nested list.
          if (nl) {
            // Build a new list item and prepend it to the list.
            var $li = $('<li>').prependTo($(nl));

            // Get first node of the list item.
            var node = editor.node.contents(blocks[i])[0];

            // While node and it is not a list, append to the new list item.
            while (node && !editor.node.isList(node)) {
              var tmp = node.nextSibling;
              $li.append(node);
              node = tmp;
            }

            // Append current list to the previous node.
            $(prev_li).append($(nl));
            $(blocks[i]).remove();
          }
          else {
            var prev_nl = $(prev_li).find('> ul, > ol').last().get(0);
            if (prev_nl) {
              $(prev_nl).append($(blocks[i]));
            }
            else {
              var $new_nl = $('<' + blocks[i].parentNode.tagName + '>');
              $(prev_li).append($new_nl);
              $new_nl.append($(blocks[i]));
            }
          }
        }
      }

      editor.clean.lists();
      editor.selection.restore();
    }

    /**
     * Outdent selected list items.
     */
    function _outdent (blocks) {
      editor.selection.save();
      _unformat(blocks);
      editor.selection.restore();
    }

    /**
     * Hook into the indent/outdent events.
     */
    function _afterCommand (cmd) {
      if (cmd == 'indent' || cmd == 'outdent') {
        var do_indent = false;
        var blocks = editor.selection.blocks();
        var blks = [];
        for (var i = 0; i < blocks.length; i++) {
          if (blocks[i].tagName == 'LI') {
            do_indent = true;
            blks.push(blocks[i]);
          }
          else if (blocks[i].parentNode.tagName == 'LI') {
            do_indent = true;
            blks.push(blocks[i].parentNode);
          }
        }

        if (do_indent) {
          if (cmd == 'indent') _indent(blks);
          else _outdent(blks);
        }
      }
    }

    /**
     * Init.
     */
    function _init () {
      editor.events.on('commands.after', _afterCommand);

      // TAB key in lists.
      editor.events.on('keydown', function (e) {
        if (e.which == $.FE.KEYCODE.TAB) {
          var blocks = editor.selection.blocks();
          var blks = [];
          for (var i = 0; i < blocks.length; i++) {
            if (blocks[i].tagName == 'LI') {
              blks.push(blocks[i]);
            }
            else if (blocks[i].parentNode.tagName == 'LI') {
              blks.push(blocks[i].parentNode)
            }
          }

          // There is more than one list item selected.
          // Selection is at the beginning of the selected list item.
          if (blks.length > 1 || (blks.length && (editor.selection.info(blks[0]).atStart || editor.node.isEmpty(blks[0])))) {
            e.preventDefault();
            e.stopPropagation();

            if (!e.shiftKey) _indent(blks);
            else _outdent(blks);

            return false;
          }
        }
      }, true);
    }

    return {
      _init: _init,
      format: format,
      refresh: refresh
    }
  }

  // Register the font size command.
  $.FE.RegisterCommand('formatUL', {
    title: 'Unordered List',
    refresh: function ($btn) {
      this.lists.refresh($btn, 'UL');
    },
    callback: function () {
      this.lists.format('UL');
    },
    plugin: 'lists'
  })

  // Register the font size command.
  $.FE.RegisterCommand('formatOL', {
    title: 'Ordered List',
    refresh: function ($btn) {
      this.lists.refresh($btn, 'OL');
    },
    callback: function () {
      this.lists.format('OL');
    },
    plugin: 'lists'
  })

  // Add the list icons.
  $.FE.DefineIcon('formatUL', {
    NAME: 'list-ul'
  });

  $.FE.DefineIcon('formatOL', {
    NAME: 'list-ol'
  });

}));
