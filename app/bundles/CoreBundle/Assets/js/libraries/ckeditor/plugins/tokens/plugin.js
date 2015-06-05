/**
 * @file
 * Written by Albert Skibinski <albert@merge.nl>
 * http://www.merge.nl
 *
 * Modified for Mautic to use for general tokens
 */

///////////////////////////////////////////////////////////////
// Polyfill for IE9
///////////////////////////////////////////////////////////////

/*
 * polyfill for IE9 to allow for multiple arguments in setTimeout
 * http://stackoverflow.com/questions/12404528/ie-parameters-get-undefined-when-using-them-in-settimeout
 */
if (document.all && !window.setTimeout.isPolyfill) {
    var __nativeST__ = window.setTimeout;
    window.setTimeout = function (vCallback, nDelay /*, argumentToPass1, argumentToPass2, etc. */) {
        var aArgs = Array.prototype.slice.call(arguments, 2);
        return __nativeST__(vCallback instanceof Function ? function () {
            vCallback.apply(null, aArgs);
        } : vCallback, nDelay);
    };
    window.setTimeout.isPolyfill = true;
}

if (document.all && !window.setInterval.isPolyfill) {
    var __nativeSI__ = window.setInterval;
    window.setInterval = function (vCallback, nDelay /*, argumentToPass1, argumentToPass2, etc. */) {
        var aArgs = Array.prototype.slice.call(arguments, 2);
        return __nativeSI__(vCallback instanceof Function ? function () {
            vCallback.apply(null, aArgs);
        } : vCallback, nDelay);
    };
    window.setInterval.isPolyfill = true;
}

///////////////////////////////////////////////////////////////
//      CKEDITOR_tokens helper class
///////////////////////////////////////////////////////////////

/*
 * Helper class needed to handle tokens.
 * This class is a singleton for each instance of CKEDITOR.
 *
 * @param {Object} editor An instance of a CKEDITOR
 * @returns {null}
 */
function CKEDITOR_tokens(editor) {
    this.editor = editor;
    this.observe = 0;
    this.char_input = [];
    this.timeout_id = null;
    this.ajaxUrl = '';

    if (CKEDITOR_tokens.caller !== CKEDITOR_tokens.get_instance) {
        throw new Error("This object cannot be instanciated");
    }
}

/*
 * Collection of pairs editor id / instance of CKEDITOR_tokens
 *
 * @type Array
 */
CKEDITOR_tokens.instances = [];

/*
 * Delay of the timeout between the last key pressed and the ajax query. It's use to prevent ajax flooding when user types fast.
 *
 * @type Number
 */

CKEDITOR_tokens.timeout_delay = 500;

/*
 * Minimum number of characters needed to start searching for users (includes the @).
 *
 * @type Number
 */

CKEDITOR_tokens.start_observe_count = 3;

/*
 * Method used to get an instance of CKEDITOR_tokens linked to an instance of CKEDITOR.
 * Its design is based on the singleton design pattern.
 *
 * @param {Object} editor An instance of a CKEDITOR
 * @returns An instance of CKEDITOR_tokens
 */
CKEDITOR_tokens.get_instance = function (editor) {
    // we browse our collection of instances
    for (var i in this.instances) {
        // if we find an CKEDITOR instance in our collection
        if (this.instances[i].id === editor.id) {
            // we return the instance of CKEDITOR_tokens that match
            return this.instances[i].instance;
        }
    }

    // if no match was found, we add a row in our collection with the current CKEDITOR id and we instanciate CKEDITOR_tokens
    this.instances.push({
        id: editor.id,
        instance: new CKEDITOR_tokens(editor)
    });
    // we return the instance of CKEDITOR_tokens that was just created
    return this.instances[this.instances.length - 1].instance;
};

/*
 * This method delete the div containing the suggestions
 *
 * @returns {null}
 */
CKEDITOR_tokens.prototype.delete_tooltip = function () {
    mQuery('.token-suggestions').remove();
};

/*
 * This method start the observation of the typed characters
 *
 * @returns {null}
 */
CKEDITOR_tokens.prototype.start_observing = function () {
    this.observe = 1;
};

/*
 * This method halts the observation of the typed characters and flush the properties used by CKEDITOR_tokens
 *
 * @returns {null}
 */
CKEDITOR_tokens.prototype.stop_observing = function () {
    this.observe = 0;
    this.char_input = [];
    this.delete_tooltip();
};

/*
 * This methods send an ajax query to durpal ckeditor_tokens module and retrieve matching token.
 *
 * @param {Object} selection result of CKEDITOR.editor.getSelection()
 * @returns {null}
 */
CKEDITOR_tokens.prototype.get_tokens = function (selection) {
    if (null !== this.timeout_id) {
        clearTimeout(this.timeout_id);
    }
    this.timeout_id = setTimeout(this.timeout_callback, CKEDITOR_tokens.timeout_delay, [this, selection]);
}

/*
 * This methods send an ajax query to durpal ckeditor_tokens module and retrieve matching user.
 *
 * @param {Array} args An Array of parameters containing the current instance of CKEDITOR_tokens and selection (cf. CKEDITOR_tokens.prototype.get_tokens)
 * @returns {null}
 */
CKEDITOR_tokens.prototype.timeout_callback = function (args) {
    var tokens = args[0];
    var selection = args[1];
    var str = tokens.char_input.join('');

    //if less than 3 char are input (including @) we don't try to get people
    if (str.length < CKEDITOR_tokens.start_observe_count) {
        tokens.delete_tooltip();
        return;
    }

    var $ = mQuery;

    var editor = tokens.editor;
    var element_id = editor.element.getId();
    var range = selection.getRanges()[0];
    var startOffset = parseInt(range.startOffset - str.length) || 0;
    var element = range.startContainer.$;
    var tokenAction = $('#' + element_id).data('token-callback');

    $.get(CKEDITOR_tokens.ajaxUrl + '?action=' + tokenAction, {query: str}, function (rsp) {
        var ckel = $('#' + element_id);
        var par  = ckel.parent();

        $('.token-suggestions').remove();

        if (rsp && rsp.html) {

            var position = 'absolute';
            var dummyElement = editor.document.createElement('img',
                {
                    attributes: {
                        src: 'data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=',
                        width: 0,
                        height: 0,
                        id: 'dummy-element'
                    }
                });

            editor.insertElement(dummyElement);


            if (editor.editable().isInline()) {
                // Dummy offset
                var dummyEl     = $('#dummy-element');
                var dummyOffset = $(dummyEl).offset();
                var dummyTop    = dummyOffset.top;
                var dummyLeft   = dummyOffset.left;

                var x = dummyLeft;
                var y = dummyTop;

                var appendToMe = $('body');
            } else {
                // Parent offset
                var parentOffset = $(par).offset();
                var parentTop    = parentOffset.top;
                var parentLeft   = parentOffset.left;

                // Editor offset
                var el = $('#cke_' + element_id + ' iframe');
                var editorOffset  = $(el).offset();
                var editorTop     = editorOffset.top;
                var editorLeft    = editorOffset.left;

                // Editor scrolling
                var scrollTop = $('#cke_' + element_id + ' iframe').contents().scrollTop();
                var scrollLeft = $('#cke_' + element_id + ' iframe').contents().scrollLeft();;

                // Dummy offset
                var dummyEl     = $('#cke_' + element_id + ' iframe').contents().find('#dummy-element');
                var dummyOffset = $(dummyEl).offset();
                var dummyTop    = dummyOffset.top;
                var dummyLeft   = dummyOffset.left;

                var x = (editorLeft - parentLeft) + (dummyLeft - scrollLeft);
                var y = (editorTop - parentTop) + (dummyTop - scrollTop);

                var appendToMe = par;
            }

            // Give some buffer
            x += 10;
            y += 10;

            // Keep it from going off the page right and bottom
            var documentWidth = ($(el).parents('.builder-content').length) ? $(el).parents('.builder-content').width() : $(document).width();
            if ((x + 240) > documentWidth) {
                x = x - str.length - 240;
            }

            var documentHeight = ($(el).parents('.builder-content').length) ? $(el).parents('.builder-content').height() : $(document).height();
            if ((y + 200) > documentHeight) {
                y = y - 205;
            }

            // Keep it from going off the page top and left
            if (x < 0) {
                x = 0;
            }

            if (y < 0) {
                y = 0;
            }

            $(dummyEl).remove();

            if(editor.editable().isInline()) {
                $('#cke_' + element_id).hide();
            }

            $('<div class="token-suggestions" style="position: ' + position + '; top: ' + y + 'px; left: ' + x + 'px; z-index: 10000;">' + rsp.html + '</div>').appendTo(appendToMe);

            $(document).on('keyup.tokenSuggestion', (function(e) {
                if (e.keyCode == 27) { // esc keycode
                    $(document).off('click.tokenSuggestions');
                    $(document).off('keyup.tokenSuggestion');

                    var tokens = CKEDITOR_tokens.get_instance(editor);
                    tokens.stop_observing();

                    $('.token-suggestions').remove();
                }
            }));

            $(document).on('click.tokenSuggestions', function(event) {
                if (event.target && !mQuery(event.target).hasClass('inline-token')) {
                    $(document).off('click.tokenSuggestions');
                    $(document).off('keyup.tokenSuggestion');

                    var tokens = CKEDITOR_tokens.get_instance(editor);
                    tokens.stop_observing();

                    $('.token-suggestions').remove();
                }
            });
        }

        $('.inline-token').click(function (e) {
            $(document).off('click.tokenSuggestions');
            $(document).off('keyup.tokenSuggestion');

            if(editor.editable().isInline()) {
                $('#cke_' + element_id).show();
            }

            e.preventDefault();

            var tokens = CKEDITOR_tokens.get_instance(editor);
            tokens.stop_observing();

            // Shorten text node
            element.textContent = element.textContent.substr(0, startOffset);

            if ($(this).data('visual')) {
                // Placeholder
                var tokenContent = document.createElement('strong');

                tokenContent.setAttribute('data-token', $(this).data('token'));
                tokenContent.setAttribute('contenteditable', 'false');

                var description = document.createTextNode('**' + $(this).data('description') + '**');
                tokenContent.appendChild(description);
            } else if ($(this).data('link')) {
                var tokenContent = document.createElement('a');
                tokenContent.setAttribute('href', $(this).data('token'));
                var description = document.createTextNode($(this).data('description'));
                tokenContent.appendChild(description);
            } else {
                var tokenContent = document.createTextNode($(this).data('token'));
            }

            // Insert link after text node
            // this is used when the link is inserted not at the end of the text
            if (element.nextSibling) {
                element.parentNode.insertBefore(tokenContent, element.nextSibling);
            }
            // at the end of the editor text
            else {
                element.parentNode.appendChild(tokenContent);
            }

            editor.focus();
            var range = editor.createRange();
                el = new CKEDITOR.dom.element(tokenContent.parentNode);
            range.moveToElementEditablePosition(el, tokenContent.parentNode.textContent.length);
            range.select();
        });
    });

};

/*
 * This method returns if a char should stop the observation.
 *
 * @param {int} charcode A character key code
 * @returns {Boolean} Whether or not the char should stop the observation
 */
CKEDITOR_tokens.prototype.break_on = function (charcode) {
    // 13 = enter
    // 37 = left key
    // 38 = up key
    // 39 = right key
    // 40 = down key
    // 46 = delete
    // 91 = home/end (?)
    var special = [13, 37, 38, 39, 40, 46, 91];
    for (var i = 0; i < special.length; i++) {
        if (special[i] == charcode) {
            return true;
        }
    }
    return false;
};


///////////////////////////////////////////////////////////////
//      Plugin implementation
///////////////////////////////////////////////////////////////
(function ($, ajaxUrl) {
    CKEDITOR.plugins.add('tokens', {
        icons: '',
        init: function (editor) {
            var tokens = CKEDITOR_tokens.get_instance(editor);
            CKEDITOR_tokens.ajaxUrl = ajaxUrl;
            /* The only way (it seems) to get a reliable, cross-browser and platform return for which key was pressed,
             * is using the jquery which function onkeypress. On keydown or up returns different values!
             * see also: http://jsfiddle.net/SpYk3/NePCm/
             */
            editor.on('contentDom', function (e) {
                var editable = editor.editable();

                /* we need the keyup listener to detect things like backspace,
                 * which does not register on keypress... javascript is weird...
                 */
                editable.attachListener(editable, 'keyup', function (evt) {
                    if (evt.data.$.which === 8) { // 8 == backspace
                        tokens.char_input.pop();
                        var selection = this.editor.getSelection();
                        tokens.get_tokens(selection);
                    }

                    // things which should trigger a stop observing, like Enter, home, etc.
                    if (tokens.break_on(evt.data.$.which)) {
                        tokens.stop_observing();
                    }

                });

                editable.attachListener(editable, 'keypress', function (evt) {
                    // btw: keyIdentifier is webkit only.
                    var element_id = editor.element.getId();

                    var triggerCharacter = $('#' + element_id).data('token-activator');

                    var typed_char = String.fromCharCode(evt.data.$.which);

                    if (typed_char === triggerCharacter || tokens.observe === 1) {
                        tokens.start_observing();
                        /* Things which should trigger "stop observing":
                         * if at this point no result and still a unicode, return false
                         * OR detect another @ while we are already observing
                         * OR the length is longer than 11
                         */
                        if ((tokens.char_input.length > 0 && typed_char === triggerCharacter) || tokens.char_input.length > 11) {
                            tokens.stop_observing();
                        } else {
                            tokens.char_input.push(typed_char);
                            var selection = this.editor.getSelection();
                            tokens.get_tokens(selection);
                        }
                    }
                });
            }); // end editor.on
        } // end init function
    });
})(mQuery, mauticAjaxUrl);

