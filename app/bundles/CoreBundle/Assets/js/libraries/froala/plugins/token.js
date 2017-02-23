/*!
 * froala_editor v2.3.3 (https://www.froala.com/wysiwyg-editor)
 * License https://froala.com/wysiwyg-editor/terms/
 * Copyright 2014-2016 Mautic team
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

  'use strict';

    $.extend($.FE.DEFAULTS, {
        tokens: {},
        tokenSelection: false,
        tokenDefaultSelection: 'Token'
    });

  $.FE.PLUGINS.token = function (editor) {

    function apply (val) {
      editor.html.insert(val);
    }

    return {
      apply: apply
    }
  };

  // Register the token command.
  $.FE.RegisterCommand('token', {
      type: 'dropdown',
      displaySelection: function (editor) {
          return editor.opts.tokenSelection;
      },
      defaultSelection: function (editor) {
          return editor.opts.tokenDefaultSelection;
      },
      displaySelectionWidth: 120,
      html: function () {
          var c = '<ul class="fr-dropdown-list" role="presentation">';
          var self = this;
          var method = location.href.match(/email/i)? 'email:getBuilderTokens' : 'page:getBuilderTokens';
          Mautic.getTokens(method, function(tokens) {
              mQuery.each(tokens, function(k,v){
                  if (k.match(/assetlink=/i)){
                      delete tokens[k];
                      var nv = v.replace('a:', '');
                      k = '<a title=\'Asset Link\' href=\'' + k + '\'>' + nv + '</a>';
                      tokens[k] = nv;
                  }
                  if (k.match(/pagelink=/i)){
                      delete tokens[k];
                      var nv = v.replace('a:', '');
                      k = '<a title=\'Page Link\' href=\'' + k + '\'>' + nv + '</a>';
                      tokens[k] = nv;
                  }
                  if (k.match(/contactfield=company/i) && !v.match(/company/i)){
                      tokens[k] = 'Company ' + v;
                  }
              });
              self.opts.tokens = tokens;
          });
          var options = this.opts.tokens;
          var k, keys = [];
          for (k in options) {
              if (options.hasOwnProperty(k)) {
                  keys.push(k);
              }
          }
          keys.sort();
          for (var i = 0; i < keys.length; i++) {
              var val = keys[i];
              var str = ' <div class=\'badge-wrapper\'><span class=\'badge\'>_BADGE_</span></div>';
              var badge = (val.match(/page link/i))? str.replace(/_BADGE_/, 'page') : (val.match(/asset link/i))? str.replace(/_BADGE_/, 'asset') : (val.match(/form=/i))? str.replace(/_BADGE_/,'form') : (val.match(/focus=/i))? str.replace(/_BADGE_/,'focus') : '';
              var title = options[val];
              if (title.length>24) title = title.substr(0, 24) + '...';
              c += '<li role="presentation"><a class="fr-command" tabIndex="-1" role="option" data-cmd="token" data-param1="' + val + '" title="' + title + '">' + title + badge + '</a></li>';
          }
          c += '</ul>';

          return c;
      },
      title: 'Insert token',
      callback: function (cmd, val) {
          this.token.apply(val);
      },
      plugin: 'token'
  });

  // Add the token icon.
  $.FE.DefineIcon('token', {
    NAME: 'tag'
  });

}));
