Mautic.builderTokens = {};
Mautic.dynamicContentTokens = {};
Mautic.builderTokensRequestInProgress = false;

    /**
 * Activate Froala options
 */
Mautic.activateGlobalFroalaOptions = function() {
    Mautic.basicFroalaOptions = {
        enter: mQuery.FroalaEditor.ENTER_BR,
        imageUploadURL: mauticBaseUrl + 's/file/upload',
        imageManagerLoadURL: mauticBaseUrl + 's/file/list',
        imageManagerDeleteURL: mauticBaseUrl + 's/file/delete',
        imageDefaultWidth: 0,
        htmlAllowedTags: ['a', 'abbr', 'address', 'area', 'article', 'aside', 'audio', 'b', 'base', 'bdi', 'bdo', 'blockquote', 'br', 'button', 'canvas', 'caption', 'cite', 'code', 'col', 'colgroup', 'datalist', 'dd', 'del', 'details', 'dfn', 'dialog', 'div', 'dl', 'dt', 'em', 'embed', 'fieldset', 'figcaption', 'figure', 'footer', 'form', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'header', 'hgroup', 'hr', 'i', 'iframe', 'img', 'input', 'ins', 'kbd', 'keygen', 'label', 'legend', 'li', 'link', 'main', 'map', 'mark', 'menu', 'menuitem', 'meter', 'nav', 'noscript', 'object', 'ol', 'optgroup', 'option', 'output', 'p', 'param', 'pre', 'progress', 'queue', 'rp', 'rt', 'ruby', 's', 'samp', 'script', 'style', 'section', 'select', 'small', 'source', 'span', 'strike', 'strong', 'sub', 'summary', 'sup', 'table', 'tbody', 'td', 'textarea', 'tfoot', 'th', 'thead', 'time', 'title', 'tr', 'track', 'u', 'ul', 'var', 'video', 'wbr', 'center'],
        htmlAllowedAttrs: ['data-atwho-at-query', 'data-section', 'data-section-wrapper', 'accept', 'accept-charset', 'accesskey', 'action', 'align', 'allowfullscreen', 'alt', 'async', 'autocomplete', 'autofocus', 'autoplay', 'autosave', 'background', 'bgcolor', 'border', 'charset', 'cellpadding', 'cellspacing', 'checked', 'cite', 'class', 'color', 'cols', 'colspan', 'content', 'contenteditable', 'contextmenu', 'controls', 'coords', 'data', 'data-.*', 'datetime', 'default', 'defer', 'dir', 'dirname', 'disabled', 'download', 'draggable', 'dropzone', 'enctype', 'for', 'form', 'formaction', 'frameborder', 'headers', 'height', 'hidden', 'high', 'href', 'hreflang', 'http-equiv', 'icon', 'id', 'ismap', 'itemprop', 'keytype', 'kind', 'label', 'lang', 'language', 'list', 'loop', 'low', 'max', 'maxlength', 'media', 'method', 'min', 'mozallowfullscreen', 'multiple', 'name', 'novalidate', 'open', 'optimum', 'pattern', 'ping', 'placeholder', 'poster', 'preload', 'pubdate', 'radiogroup', 'readonly', 'rel', 'required', 'reversed', 'rows', 'rowspan', 'sandbox', 'scope', 'scoped', 'scrolling', 'seamless', 'selected', 'shape', 'size', 'sizes', 'span', 'src', 'srcdoc', 'srclang', 'srcset', 'start', 'step', 'summary', 'spellcheck', 'style', 'tabindex', 'target', 'title', 'type', 'translate', 'usemap', 'value', 'valign', 'webkitallowfullscreen', 'width', 'wrap']
    };

    // Gated video style
    Mautic.basicFroalaOptions.iframeStyle = mQuery.FroalaEditor.DEFAULTS.iframeStyle + 'body .fr-gatedvideo{user-select:none;-o-user-select:none;-moz-user-select:none;-khtml-user-select:none;-webkit-user-select:none;-ms-user-select:none;position:relative;display:table;min-height:140px}body .fr-gatedvideo::after{content:"";position:absolute;background-repeat:no-repeat;background-position:50% 40%;height:100%;width:100%;top:0;left:0;display:block;clear:both;background-image:url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAHIAAAByCAMAAAC4A3VPAAAA/1BMVEUAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAD64ociAAAAVHRSTlMAAQIDBAUGCAkKCw0PEBEUFxsfICUmKistLjE1Njo8QExNVl9iY2RmZ2hpa2xtb3Bxc3R8gIWGkZedoquwt8XP0dXX2drc4OLm6Ont7/Hz9ff5+/3esbxfAAACIklEQVRo3u3aW1fTQBSG4a9BKIUKVCi0IqCIp3pAjYpQaEGQYlWk5fv/v8WLrkKbJjNNsmeu9nuXrFnruclhJXsATdM0TdNSVihVqrV6itZXl2ZyeLM7Rz1mqN1YyQaWwltmrrUxDfHgUSUYOdztM1fNBav4rE/+fjI8mjtm3q5rFnFvsK46OFo4p0BPpxHZBADMd0jX5ovhor8AEJxSqLpdJAHgQErkddI19JJjZI1yNePFVxwjC5eCJDesIoEtSZGtGPE1I2RLlOTks+9NZAUWZUU2bCKxLUy2I2JjYgVCYZIzFpE4kSaXLCLRkSZXR8S3cQtwI02uW0RCWhx5zr6jb/I9fZNJojvyA32T+/RNGkRHpEl0QxpFJ+RHeiYff6Jv8oLeSSqppJJKKqmkkkoqqWTe9rveyfrDrncSFtPJl5fZdPN9aTQdfUWbTFf/Cgymsz8i5a53Mtl0+HcryZQn7+dQCSb+SJNVWEycSZMVWEwcSpMlWMy7gZtUvQIsJtaEyaPIBGHSRNCTJXdgM4GvouLtLGwmsCxKhjEzr4gJ4Lug2C/FTfbKvyJk8Z8cuRs/vxwzAWBTTDxOmguPmqaBRurO5zCFOTjxRUTsmHYz3JkX5sFNqk7njfsKhubn4YnN3NfQQWDZPVG+Iskf9zduMd+9cmnbrgGgGP5sPx8bby5/y/zsa20VMm74Cdb2Ds/SvbNvOifh9iI0TdM0TZPtP32lY4xP2bT1AAAAAElFTkSuQmCC)}body .fr-gatedvideo video{background-color:rgba(67,83,147,.5)}body .fr-gatedvideo.fr-active > *{z-index:2;position:relative}body .fr-gatedvideo > *{-webkit-box-sizing:content-box;-moz-box-sizing:content-box;box-sizing:content-box;max-width:100%;border:none}body .fr-box .fr-gatedvideo-resizer{position:absolute;border:solid 1px #1e88e5;display:none;user-select:none;-o-user-select:none;-moz-user-select:none;-khtml-user-select:none;-webkit-user-select:none;-ms-user-select:none}body .fr-box .fr-gatedvideo-resizer.fr-active{display:block}body .fr-box .fr-gatedvideo-resizer .fr-handler{display:block;position:absolute;background:#1e88e5;border:solid 1px #fff;z-index:4;-webkit-box-sizing:border-box;-moz-box-sizing:border-box;box-sizing:border-box}body .fr-box .fr-gatedvideo-resizer .fr-handler.fr-hnw{cursor:nw-resize}body .fr-box .fr-gatedvideo-resizer .fr-handler.fr-hne{cursor:ne-resize}body .fr-box .fr-gatedvideo-resizer .fr-handler.fr-hsw{cursor:sw-resize}body .fr-box .fr-gatedvideo-resizer .fr-handler.fr-hse{cursor:se-resize}body .fr-box .fr-gatedvideo-resizer .fr-handler{width:12px;height:12px}body .fr-box .fr-gatedvideo-resizer .fr-handler.fr-hnw{left:-6px;top:-6px}body .fr-box .fr-gatedvideo-resizer .fr-handler.fr-hne{right:-6px;top:-6px}body .fr-box .fr-gatedvideo-resizer .fr-handler.fr-hsw{left:-6px;bottom:-6px}body .fr-box .fr-gatedvideo-resizer .fr-handler.fr-hse{right:-6px;bottom:-6px}@media (min-width: 1200px){body .fr-box .fr-gatedvideo-resizer .fr-handler{width:10px;height:10px}body .fr-box .fr-gatedvideo-resizer .fr-handler.fr-hnw{left:-5px;top:-5px}body .fr-box .fr-gatedvideo-resizer .fr-handler.fr-hne{right:-5px;top:-5px}body .fr-box .fr-gatedvideo-resizer .fr-handler.fr-hsw{left:-5px;bottom:-5px}body .fr-box .fr-gatedvideo-resizer .fr-handler.fr-hse{right:-5px;bottom:-5px}}body .fr-gatedvideo-size-layer .fr-gatedvideo-group .fr-input-line{display:inline-block}body .fr-gatedvideo-size-layer .fr-gatedvideo-group .fr-input-line + .fr-input-line{margin-left:10px}body .fr-gatedvideo-overlay{position:fixed;top:0;left:0;bottom:0;right:0;z-index:9999;display:none}';

    // Set the Froala license key
    mQuery.FroalaEditor.DEFAULTS.key = 'MCHCPd1XQVZFSHSd1C==';
};

/**
 * Initialize AtWho dropdown in a Froala editor.
 *
 * @param element jQuery element
 * @param method  method to get the tokens from
 * @param froala  Froala Editor
 */
Mautic.initAtWho = function(element, method, froala) {
    // Avoid to request the tokens if not necessary
    if (Mautic.builderTokensRequestInProgress) {
        // Wait till previous request finish
        var intervalID = setInterval(function(){
            if (!Mautic.builderTokensRequestInProgress) {
                clearInterval(intervalID);
                Mautic.configureAtWho(element, method, froala);
            }
        }, 500);
    } else {
        Mautic.configureAtWho(element, method, froala);
    }
};

/**
 * Initialize AtWho dropdown in a Froala editor.
 *
 * @param element jQuery element
 * @param method  method to get the tokens from
 * @param froala  Froala Editor
 */
Mautic.configureAtWho = function(element, method, froala) {
    Mautic.getTokens(method, function(tokens) {
        element.atwho('destroy');

        Mautic.configureDynamicContentAtWhoTokens();

        // Add the dynamic content tokens
        mQuery.extend(tokens, Mautic.dynamicContentTokens);

        element.atwho({
            at: '{',
            displayTpl: '<li>${name} <small>${id}</small></li>',
            insertTpl: "${id}",
            editableAtwhoQueryAttrs: {"data-fr-verified": true},
            data: mQuery.map(tokens, function(value, i) {
                return {'id':i, 'name':value};
            }),
            acceptSpaceBar: true
        });

        if (froala) {
            froala.events.on('keydown', function (e) {
                if ((e.which == mQuery.FroalaEditor.KEYCODE.TAB ||
                    e.which == mQuery.FroalaEditor.KEYCODE.ENTER ||
                    e.which == mQuery.FroalaEditor.KEYCODE.SPACE) &&
                    froala.$el.atwho('isSelecting')) {
                    return false;
                }
            }, true);
        }
    });
};

/**
 * Download the tokens
 *
 * @param method to fetch the tokens from
 * @param callback(tokens) to call when finished
 */
Mautic.getTokens = function(method, callback) {
    // Check if the builderTokens var holding the tokens was already loaded
    if (!mQuery.isEmptyObject(Mautic.builderTokens)) {
        return callback(Mautic.builderTokens);
    }

    Mautic.builderTokensRequestInProgress = true;

    // OK, let's fetch the tokens.
    mQuery.ajax({
        url: mauticAjaxUrl,
        data: 'action=' + method,
        success: function (response) {
            if (typeof response.tokens === 'object') {

                // store the tokens to the session storage
                Mautic.builderTokens = response.tokens;

                // return the callback with tokens
                callback(response.tokens);
            }
        },
        error: function (request, textStatus, errorThrown) {
            Mautic.processAjaxError(request, textStatus, errorThrown);
        },
        complete: function() {
            Mautic.builderTokensRequestInProgress = false;
        }
    });
};

/**
 * Append dynamic content tokens to at who
 */
Mautic.configureDynamicContentAtWhoTokens = function() {
    Mautic.dynamicContentTokens = {};

    var dynamicContentTabs = mQuery('#dynamicContentTabs');

    if (dynamicContentTabs.length === 0 && window.parent) {
        dynamicContentTabs = mQuery(window.parent.document.getElementById('dynamicContentTabs'));
    }

    if (dynamicContentTabs.length) {
        dynamicContentTabs.find('a[data-toggle="tab"]').each(function () {
            var tokenText = mQuery(this).text();
            var prototype = '{dynamiccontent="__tokenName__"}';
            var newOption = prototype.replace(/__tokenName__/g, tokenText);

            Mautic.dynamicContentTokens[newOption] = tokenText;
        });
    }
};
