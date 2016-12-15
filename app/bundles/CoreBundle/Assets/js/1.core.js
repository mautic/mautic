var MauticVars  = {};
var mQuery      = jQuery.noConflict(true);
window.jQuery   = mQuery;

// Polyfil for ES6 startsWith method
if (!String.prototype.startsWith) {
    String.prototype.startsWith = function(searchString, position){
      position = position || 0;
      return this.substr(position, searchString.length) === searchString;
  };
}

//set default ajax options
MauticVars.activeRequests = 0;

mQuery.ajaxSetup({
    beforeSend: function (request, settings) {
        if (settings.showLoadingBar) {
            mQuery('.loading-bar').addClass('active');
            MauticVars.activeRequests++;
        }

        if (typeof IdleTimer != 'undefined') {
            //append last active time
            var userLastActive = IdleTimer.getLastActive();
            var queryGlue = (settings.url.indexOf("?") == -1) ? '?' : '&';

            settings.url = settings.url + queryGlue + 'mauticUserLastActive=' + userLastActive;
        }

        if (mQuery('#mauticLastNotificationId').length) {
            //append last notifications
            var queryGlue = (settings.url.indexOf("?") == -1) ? '?' : '&';

            settings.url = settings.url + queryGlue + 'mauticLastNotificationId=' + mQuery('#mauticLastNotificationId').val();
        }

        return true;
    },

    cache: false
});

// Force stop the page loading bar when no more requests are being in progress
mQuery( document ).ajaxStop(function(event) {
    // Seems to be stuck
    MauticVars.activeRequests = 0;
    Mautic.stopPageLoadingBar();
});

mQuery( document ).ready(function() {
    if (typeof mauticContent !== 'undefined') {
        mQuery("html").Core({
            console: false
        });
    }

    if (typeof IdleTimer != 'undefined') {
        IdleTimer.init({
            idleTimeout: 60000, //1 min
            awayTimeout: 900000, //15 min
            statusChangeUrl: mauticAjaxUrl + '?action=updateUserStatus'
        });
    }

    // Prevent backspace from activating browser back
    mQuery(document).on('keydown', function (e) {
        if (e.which === 8 && !mQuery(e.target).is("input:not([readonly]):not([type=radio]):not([type=checkbox]), textarea, [contentEditable], [contentEditable=true]")) {
            e.preventDefault();
        }
    });
});

//Fix for back/forward buttons not loading ajax content with History.pushState()
MauticVars.manualStateChange = true;

if (typeof History != 'undefined') {
    History.Adapter.bind(window, 'statechange', function () {
        if (MauticVars.manualStateChange == true) {
            //back/forward button pressed
            window.location.reload();
        }
        MauticVars.manualStateChange = true;
    });
}

//set global Chart defaults
if (typeof Chart != 'undefined') {
    // configure global Chart options
    Chart.defaults.global.elements.line.borderWidth = 1;
    Chart.defaults.global.elements.point.radius = 2;
    Chart.defaults.global.legend.labels.boxWidth = 12;
    Chart.defaults.global.maintainAspectRatio = false;
}

//live search vars
MauticVars.liveCache            = new Array();
MauticVars.lastSearchStr        = "";
MauticVars.globalLivecache      = new Array();
MauticVars.lastGlobalSearchStr  = "";

//used for spinning icons to show something is in progress)
MauticVars.iconClasses          = {};

//prevent multiple ajax calls from multiple clicks
MauticVars.routeInProgress       = '';

//prevent interval ajax requests from overlapping
MauticVars.moderatedIntervals    = {};
MauticVars.intervalsInProgress   = {};

var Mautic = {
    loadedContent: {},

    keyboardShortcutHtml: {},

    builderTokens: {},

    dynamicContentTokens: {},

    builderTokensRequestInProgress: false,

    addKeyboardShortcut: function (sequence, description, func, section) {
        Mousetrap.bind(sequence, func);
        var sectionName = section || 'global';

        if (! Mautic.keyboardShortcutHtml.hasOwnProperty(sectionName)) {
            Mautic.keyboardShortcutHtml[sectionName] = {};
        }

        Mautic.keyboardShortcutHtml[sectionName][sequence] = '<div class="col-xs-6"><mark>' + sequence + '</mark>: ' + description + '</div>';
    },

    /**
     * Binds global keyboard shortcuts
     */
    bindGlobalKeyboardShortcuts: function () {
        Mautic.addKeyboardShortcut('shift+d', 'Load the Dashboard', function (e) {
            mQuery('#mautic_dashboard_index').click();
        });

        Mautic.addKeyboardShortcut('shift+c', 'Load Contacts',  function(e) {
            mQuery('#mautic_contact_index').click();
        });

        Mautic.addKeyboardShortcut('shift+right', 'Activate Right Menu', function (e) {
            mQuery(".navbar-right a[data-toggle='sidebar']").click();
        });

        Mautic.addKeyboardShortcut('shift+n', 'Show Notifications', function (e) {
            mQuery('.dropdown-notification').click();
        });

        Mautic.addKeyboardShortcut('shift+s', 'Global Search', function (e) {
            mQuery('#globalSearchContainer .search-button').click();
        });

        Mousetrap.bind('?', function (e) {
            var modalWindow = mQuery('#MauticSharedModal');

            modalWindow.find('.modal-title').html('Keyboard Shortcuts');
            modalWindow.find('.modal-body').html(function() {
                var modalHtml = '';
                var sections = Object.keys(Mautic.keyboardShortcutHtml);
                sections.forEach(function(section) {
                    var sectionTitle = (section + '').replace(/^([a-z\u00E0-\u00FC])|\s+([a-z\u00E0-\u00FC])/g, function ($1) {
                        return $1.toUpperCase();
                    });
                    modalHtml += '<h4>' + sectionTitle + '</h4><br />';
                    modalHtml += '<div class="row">';
                    var sequences = Object.keys(Mautic.keyboardShortcutHtml[section]);
                    sequences.forEach(function(sequence) {
                        modalHtml += Mautic.keyboardShortcutHtml[section][sequence];
                    });
                    modalHtml += '</div><hr />';
                });

                return modalHtml;
            });
            modalWindow.find('.modal-footer').html('<p>Press <mark>shift+?</mark> at any time to view this help modal.');
            modalWindow.modal();
        });
    },

    /**
     * Translations
     *
     * @param id     string
     * @param params object
     */
    translate: function (id, params) {
        if (!mauticLang.hasOwnProperty(id)) {
            return id;
        }

        var translated = mauticLang[id];

        if (params) {
            for (var key in params) {
                if (!params.hasOwnProperty(key)) continue;

                var regEx = new RegExp('%' + key + '%', 'g');
                translated = translated.replace(regEx, params[key])
            }
        }

        return translated;
    },

    /**
     * Setups browser notifications
     */
    setupBrowserNotifier: function () {
        //request notification support
        notify.requestPermission();
        notify.config({
            autoClose: 10000
        });

        Mautic.browserNotifier = {
            isSupported:     notify.isSupported,
            permissionLevel: notify.permissionLevel()
        };

        Mautic.browserNotifier.isSupported        = notify.isSupported;
        Mautic.browserNotifier.permissionLevel    = notify.permissionLevel();
        Mautic.browserNotifier.createNotification = function (title, options) {
            return notify.createNotification(title, options);
        }
    },

    /**
     * Stops the ajax page loading indicator
     */
    stopPageLoadingBar: function () {
        if (MauticVars.activeRequests < 1) {
            MauticVars.activeRequests = 0;
        } else {
            MauticVars.activeRequests--;
        }

        if (MauticVars.loadingBarTimeout) {
            clearTimeout(MauticVars.loadingBarTimeout);
        }

        if (MauticVars.activeRequests == 0) {
            mQuery('.loading-bar').removeClass('active');
        }
    },

    /**
     * Activate page loading bar
     */
    startPageLoadingBar: function() {
        mQuery('.loading-bar').addClass('active');
        MauticVars.activeRequests++;
    },

    /**
     * Starts the ajax loading indicator for the right canvas
     */
    startCanvasLoadingBar: function () {
        mQuery('.canvas-loading-bar').addClass('active');
    },

    /**
     * Starts the ajax loading indicator for modals
     *
     * @param modalTarget
     */
    startModalLoadingBar: function (modalTarget) {
        mQuery(modalTarget + ' .modal-loading-bar').addClass('active');
    },

    /**
     * Stops the ajax loading indicator for the right canvas
     */
    stopCanvasLoadingBar: function () {
        mQuery('.canvas-loading-bar').removeClass('active');
    },

    /**
     * Stops the ajax loading indicator for modals
     */
    stopModalLoadingBar: function (modalTarget) {
        mQuery(modalTarget + ' .modal-loading-bar').removeClass('active');
    },

    /**
     * Activate label loading spinner
     *
     * @param el
     */
    activateLabelLoadingIndicator: function(el) {
        var labelSpinner    = mQuery("label[for='"+el+"']");
        Mautic.labelSpinner = mQuery('<i class="fa fa-fw fa-spinner fa-spin"></i>');
        labelSpinner.append(Mautic.labelSpinner);
    },

    /**
     * Remove the spinner from label
     */
    removeLabelLoadingIndicator: function() {
        mQuery(Mautic.labelSpinner).remove();
    },

    /**
     * Activate Froala options
     */
    activateGlobalFroalaOptions: function() {
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
    },

    /**
     * Initiate various functions on page load, manual or ajax
     */
    onPageLoad: function (container, response, inModal) {
        Mautic.initDateRangePicker(container + ' #daterange_date_from', container + ' #daterange_date_to');

        //initiate links
        mQuery(container + " a[data-toggle='ajax']").off('click.ajax');
        mQuery(container + " a[data-toggle='ajax']").on('click.ajax', function (event) {
            event.preventDefault();

            return Mautic.ajaxifyLink(this, event);
        });

        //initialize forms
        mQuery(container + " form[data-toggle='ajax']").each(function (index) {
            Mautic.ajaxifyForm(mQuery(this).attr('name'));
        });

        //initialize ajax'd modals
        mQuery(container + " *[data-toggle='ajaxmodal']").off('click.ajaxmodal');
        mQuery(container + " *[data-toggle='ajaxmodal']").on('click.ajaxmodal', function (event) {
            event.preventDefault();

            Mautic.ajaxifyModal(this, event);
        });

        //initalize live search boxes
        mQuery(container + " *[data-toggle='livesearch']").each(function (index) {
            Mautic.activateLiveSearch(mQuery(this), "lastSearchStr", "liveCache");
        });

        //initialize list filters
        mQuery(container + " *[data-toggle='listfilter']").each(function (index) {
            Mautic.activateListFilterSelect(mQuery(this));
        });

        //initialize tooltips
        mQuery(container + " *[data-toggle='tooltip']").tooltip({html: true, container: 'body'});

        //initialize sortable lists
        mQuery(container + " *[data-toggle='sortablelist']").each(function (index) {
            Mautic.activateSortable(this);
        });

        //downloads
        mQuery(container + " a[data-toggle='download']").off('click.download');
        mQuery(container + " a[data-toggle='download']").on('click.download', function (event) {
            event.preventDefault();

            var link = mQuery(this).attr('href');

            //initialize download links
            var iframe = mQuery("<iframe/>").attr({
                src: link,
                style: "visibility:hidden;display:none"
            }).appendTo(mQuery('body'));
        });

        mQuery(container + " a[data-toggle='confirmation']").off('click.confirmation');
        mQuery(container + " a[data-toggle='confirmation']").on('click.confirmation', function (event) {
            event.preventDefault();
            MauticVars.ignoreIconSpin = true;
            return Mautic.showConfirmation(this);
        });

        //initialize date/time
        mQuery(container + " *[data-toggle='datetime']").each(function() {
            Mautic.activateDateTimeInputs(this, 'datetime');
        });

        mQuery(container + " *[data-toggle='date']").each(function() {
            Mautic.activateDateTimeInputs(this, 'date');
        });

        mQuery(container + " *[data-toggle='time']").each(function() {
            Mautic.activateDateTimeInputs(this, 'time');
        });

        mQuery(container + " input[data-toggle='color']").each(function() {
            Mautic.activateColorPicker(this);
        });

        mQuery(container + " select").not('.multiselect, .not-chosen').each(function() {
            Mautic.activateChosenSelect(this);
        });

        mQuery(container + " select.multiselect").each(function() {
            Mautic.activateMultiSelect(this);
        });

        mQuery(container + " *[data-toggle='field-lookup']").each(function (index) {
            var target = mQuery(this).attr('data-target');
            var options = mQuery(this).attr('data-options');
            var field = mQuery(this).attr('id');
            var action = mQuery(this).attr('data-action');

            Mautic.activateFieldTypeahead(field, target, options, action);
        });

        // Fix dropdowns in responsive tables - https://github.com/twbs/bootstrap/issues/11037#issuecomment-163746965
        mQuery(container + " .table-responsive").on('shown.bs.dropdown', function (e) {
            var table = mQuery(this),
                menu = mQuery(e.target).find(".dropdown-menu"),
                tableOffsetHeight = table.offset().top + table.height(),
                menuOffsetHeight = menu.offset().top + menu.outerHeight(true);

            if (menuOffsetHeight > tableOffsetHeight)
                table.css("padding-bottom", menuOffsetHeight - tableOffsetHeight + 16)
        });
        mQuery(container + " .table-responsive").on("hide.bs.dropdown", function () {
            mQuery(this).css("padding-bottom", 0);
        })

        //initialize tab/hash activation
        mQuery(container + " .nav-tabs[data-toggle='tab-hash']").each(function() {
            // Show tab based on hash
            var hash  = document.location.hash;
            var prefix = 'tab-';

            if (hash) {
                var hashPieces = hash.split('?');
                hash           = hashPieces[0].replace("#", "#" + prefix);
                var activeTab  = mQuery(this).find('a[href=' + hash + ']').first();

                if (mQuery(activeTab).length) {
                    mQuery('.nav-tabs li').removeClass('active');
                    mQuery('.tab-pane').removeClass('in active');
                    mQuery(activeTab).parent().addClass('active');
                    mQuery(hash).addClass('in active');
                }
            }

            mQuery(this).find('a').on('shown.bs.tab', function (e) {
                window.location.hash = e.target.hash.replace("#" + prefix, "#");
            });
        });

        //spin icons on button click
        mQuery(container + ' .btn:not(.btn-nospin)').on('click.spinningicons', function (event) {
            Mautic.startIconSpinOnEvent(event);
        });

        mQuery(container + ' input[class=list-checkbox]').on('change', function () {
            var disabled = Mautic.batchActionPrecheck(container) ? false : true;
            var color    = (disabled) ? 'btn-default' : 'btn-info';
            var button   = container + ' th.col-actions .input-group-btn button';
            mQuery(button).prop('disabled', disabled);
            mQuery(button).removeClass('btn-default btn-info').addClass(color);
        });

        //Copy form buttons to the toolbar
        if (mQuery(container + " .bottom-form-buttons").length) {
            if (inModal) {
                if (mQuery(container + ' .modal-form-buttons').length) {
                    //hide the bottom buttons
                    mQuery(container + ' .bottom-form-buttons').addClass('hide');
                    var buttons = mQuery(container + " .bottom-form-buttons").html();

                    //make sure working with a clean slate
                    mQuery(container + ' .modal-form-buttons').html('');

                    mQuery(buttons).filter("button").each(function (i, v) {
                        //get the ID
                        var id = mQuery(this).attr('id');
                        var button = mQuery("<button type='button' />")
                            .addClass(mQuery(this).attr('class'))
                            .addClass('btn-copy')
                            .html(mQuery(this).html())
                            .appendTo(container + ' .modal-form-buttons')
                            .on('click.ajaxform', function (event) {
                                if (mQuery(this).hasClass('disabled')) {
                                    return false;
                                }

                                // Disable the form buttons until this action is complete
                                if (!mQuery(this).hasClass('btn-dnd')) {
                                    mQuery(this).parent().find('button').prop('disabled', true);
                                }

                                event.preventDefault();
                                Mautic.startIconSpinOnEvent(event);
                                mQuery('#' + id).click();
                            });
                    });
                }
            } else {
                //hide the toolbar actions if applicable
                mQuery('.toolbar-action-buttons').addClass('hide');

                if (mQuery('.toolbar-form-buttons').hasClass('hide')) {
                    //hide the bottom buttons
                    mQuery(container + ' .bottom-form-buttons').addClass('hide');
                    var buttons = mQuery(container + " .bottom-form-buttons").html();

                    //make sure working with a clean slate
                    mQuery(container + ' .toolbar-form-buttons .toolbar-standard').html('');
                    mQuery(container + ' .toolbar-form-buttons .toolbar-dropdown .drop-menu').html('');

                    var lastIndex = mQuery(buttons).filter("button").length - 1;
                    mQuery(buttons).filter("button").each(function (i, v) {
                        //get the ID
                        var id = mQuery(this).attr('id');

                        var buttonClick = function (event) {
                            event.preventDefault();

                            // Disable the form buttons until this action is complete
                            if (!mQuery(this).hasClass('btn-dnd')) {
                                mQuery(this).parent().find('button').prop('disabled', true);
                            }

                            Mautic.startIconSpinOnEvent(event);
                            mQuery('#' + id).click();
                        };

                        mQuery("<button type='button' />")
                            .addClass(mQuery(this).attr('class'))
                            .addClass('btn-copy')
                            .attr('id', mQuery(this).attr('id') + '_toolbar')
                            .html(mQuery(this).html())
                            .on('click.ajaxform', buttonClick)
                            .appendTo('.toolbar-form-buttons .toolbar-standard');

                        if (i === lastIndex) {
                            mQuery(".toolbar-form-buttons .toolbar-dropdown .btn-main")
                                .off('.ajaxform')
                                .attr('id', mQuery(this).attr('id') + '_toolbar_mobile')
                                .html(mQuery(this).html())
                                .on('click.ajaxform', buttonClick);
                        } else {
                            mQuery("<a />")
                                .attr('id', mQuery(this).attr('id') + '_toolbar_mobile')
                                .html(mQuery(this).html())
                                .on('click.ajaxform', buttonClick)
                                .appendTo(mQuery('<li />').prependTo('.toolbar-form-buttons .toolbar-dropdown .dropdown-menu'))
                        }

                    });
                    mQuery('.toolbar-form-buttons').removeClass('hide');
                }
            }
        }

        Mautic.activateGlobalFroalaOptions();
        if (mQuery(container + ' textarea.editor').length) {
            mQuery(container + ' textarea.editor').each(function () {
                var textarea = mQuery(this);

                // init AtWho in a froala editor
                if (textarea.hasClass('editor-builder-tokens')) {
                    textarea.on('froalaEditor.initialized', function (e, editor) {
                        Mautic.initAtWho(editor.$el, textarea.attr('data-token-callback'), editor);
                    });

                    textarea.on('froalaEditor.focus', function (e, editor) {
                        Mautic.initAtWho(editor.$el, textarea.attr('data-token-callback'), editor);
                    });
                }

                textarea.on('froalaEditor.blur', function (e, editor) {
                    editor.popups.hideAll();
                });

                var maxButtons = ['undo', 'redo', '|', 'bold', 'italic', 'underline', 'paragraphFormat', 'fontFamily', 'fontSize', 'color', 'align', 'orderedList', 'unorderedList', 'quote', 'clearFormatting', 'insertLink', 'insertImage', 'insertGatedVideo', 'insertTable', 'html', 'fullscreen'];
                var minButtons = ['undo', 'redo', '|', 'bold', 'italic', 'underline'];

                if (textarea.hasClass('editor-email')) {
                    maxButtons = mQuery.grep(maxButtons, function(value) {
                        return value != 'insertGatedVideo';
                    });

                    maxButtons.push('dynamicContent');
                }

                if (textarea.hasClass('editor-dynamic-content')) {
                    minButtons = ['undo', 'redo', '|', 'bold', 'italic', 'underline', 'fontFamily', 'fontSize', 'color', 'align', 'orderedList', 'unorderedList', 'quote', 'clearFormatting', 'insertLink', 'insertImage'];
                }

                if (textarea.hasClass('editor-advanced') || textarea.hasClass('editor-basic-fullpage')) {
                    var options = {
                        // Set custom buttons with separator between them.
                        toolbarButtons: maxButtons,
                        toolbarButtonsMD: maxButtons,
                        heightMin: 300
                    };

                    if (textarea.hasClass('editor-basic-fullpage')) {
                        options.fullPage = true;
                        options.htmlAllowedTags = ['.*'];
                        options.htmlAllowedAttrs = ['.*'];
                        options.htmlRemoveTags = [];
                        options.lineBreakerTags = [];
                    }

                    textarea.on('froalaEditor.focus', function (e, editor) {
                        Mautic.showChangeThemeWarning = true;
                    });

                    textarea.froalaEditor(mQuery.extend({}, Mautic.basicFroalaOptions, options));
                } else {
                    textarea.froalaEditor(mQuery.extend({}, Mautic.basicFroalaOptions, {
                        // Set custom buttons with separator between them.
                        toolbarButtons: minButtons,
                        heightMin: 100
                    }));
                }
            });
        }

        //activate shuffles
        if (mQuery(container + ' .shuffle-grid').length) {
            var grid = mQuery(container + " .shuffle-grid");

            //give a slight delay in order for images to load so that shuffle starts out with correct dimensions
            setTimeout(function () {
                grid.shuffle({
                    itemSelector: ".shuffle",
                    sizer: false
                });

                // Update shuffle on sidebar minimize/maximize
                mQuery("html")
                    .on("fa.sidebar.minimize", function () {
                        grid.shuffle("update");
                    })
                    .on("fa.sidebar.maximize", function () {
                        grid.shuffle("update");
                    });

                // Update shuffle if in a tab
                if (grid.parents('.tab-pane').length) {
                    var tabId = grid.parents('.tab-pane').first().attr('id');
                    var tab   = mQuery('a[href="#' + tabId + '"]').on('shown.bs.tab', function() {
                        grid.shuffle("update");
                    });
                }
            }, 1000);
        }

        //prevent auto closing dropdowns for dropdown forms
        if (mQuery(container + ' .dropdown-menu-form').length) {
            mQuery(container + ' .dropdown-menu-form').on('click', function (e) {
                e.stopPropagation();
            });
        }

        if (response && response.updateSelect) {
            Mautic.updateEntitySelect(response);
        }

        //run specific on loads
        var contentSpecific = false;
        if (response && response.mauticContent) {
            contentSpecific = response.mauticContent;
        } else if (container == 'body') {
            contentSpecific = mauticContent;
        }

        if (container == '#app-content' || container == 'body') {
            //register global keyboard shortcuts
            Mautic.bindGlobalKeyboardShortcuts();

            mQuery(".sidebar-left a[data-toggle='ajax']").on('click.ajax', function (event) {
                mQuery("html").removeClass('sidebar-open-ltr');
            });
            mQuery('.sidebar-right a[data-toggle="ajax"]').on('click.ajax', function (event) {
                mQuery("html").removeClass('sidebar-open-rtl');
            });
        }

        if (contentSpecific && typeof Mautic[contentSpecific + "OnLoad"] == 'function') {
            if (typeof Mautic[contentSpecific + "OnLoad"] == 'function') {
                if (typeof Mautic.loadedContent[contentSpecific] == 'undefined') {
                    Mautic.loadedContent[contentSpecific] = true;
                    Mautic[contentSpecific + "OnLoad"](container, response);
                }
            }
        }

        if (!inModal && container == 'body') {
            //prevent notification dropdown from closing if clicking an action
            mQuery('#notificationsDropdown').on('click', function (e) {
                if (mQuery(e.target).hasClass('do-not-close')) {
                    e.stopPropagation();
                }
            });

            if (mQuery('#globalSearchContainer').length) {
                mQuery('#globalSearchContainer .search-button').click(function () {
                    mQuery('#globalSearchContainer').addClass('active');
                    if (mQuery('#globalSearchInput').val()) {
                        mQuery('#globalSearchDropdown').addClass('open');
                    }
                    setTimeout(function () {
                        mQuery('#globalSearchInput').focus();
                    }, 100);
                    mQuery('body').on('click.globalsearch', function (event) {
                        var target = event.target;
                        if (!mQuery(target).parents('#globalSearchContainer').length && !mQuery(target).parents('#globalSearchDropdown').length) {
                            Mautic.closeGlobalSearchResults();
                        }
                    });
                });

                mQuery("#globalSearchInput").on('change keyup paste', function () {
                    if (mQuery(this).val()) {
                        mQuery('#globalSearchDropdown').addClass('open');
                    } else {
                        mQuery('#globalSearchDropdown').removeClass('open');
                    }
                });
                Mautic.activateLiveSearch("#globalSearchInput", "lastGlobalSearchStr", "globalLivecache");
            }
        }

        Mautic.renderCharts(container);
        Mautic.renderMaps(container);
        Mautic.stopIconSpinPostEvent();

        //stop loading bar
        if ((response && typeof response.stopPageLoading != 'undefined' && response.stopPageLoading) || container == '#app-content' || container == '.page-list') {
            Mautic.stopPageLoadingBar();
        }
    },

    /**
     * Initialize AtWho dropdown in a Froala editor.
     *
     * @param element jQuery element
     * @param method  method to get the tokens from
     * @param froala  Froala Editor
     */
    initAtWho: function(element, method, froala) {
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
    },

    /**
     * Initialize AtWho dropdown in a Froala editor.
     *
     * @param element jQuery element
     * @param method  method to get the tokens from
     * @param froala  Froala Editor
     */
    configureAtWho: function(element, method, froala) {
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
    },

    getBuilderTokensMethod: function() {
        var method = 'page:getBuilderTokens';
        if (parent.mQuery('.builder').hasClass('email-builder')) {
            method = 'email:getBuilderTokens';
        }
        return method;
    },

    /**
     * Download the tokens
     *
     * @param method to fetch the tokens from
     * @param callback(tokens) to call when finished
     */
    getTokens: function(method, callback) {
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
    },

    configureDynamicContentAtWhoTokens: function() {
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
    },

    /**
     * Inserts a new row into a chosen select box
     *
     * @param response
     */
    updateEntitySelect: function (response) {
        // New entity added through a popup so update the chosen
        var newOption = mQuery('<option />').val(response.id);
        newOption.html(response.name);
        var el = '#' + response.updateSelect;

        var mQueryParent = (window.opener) ? window.opener.mQuery : mQuery;

        var sortOptions = function (options) {
            return options.sort(function (a, b) {
                var alc = a.text.toLowerCase(), blc = b.text.toLowerCase();
                return alc > blc ? 1 : alc < blc ? -1 : 0;
            });
        }

        if (mQueryParent(el).prop('disabled')) {
            mQueryParent(el).prop('disabled', false);
            var defaultOption = mQuery('<option value="">' + mauticLang.chosenChooseOne + '</option>');
        } else {
            var defaultOption = mQueryParent(el + ' option:first');
            if (defaultOption.val() !== '' && defaultOption.val() !== 'new') {
                var defaultOption = false;
            } else {
                // Remove the first option and add it back after sorting
                mQueryParent(el + ' option:first').remove();
            }
        }

        if (response.group) {
            var optgroup = el + " optgroup";
            if (mQueryParent(optgroup).length) {
                // update option when new option equal with option item in group.
                var firstOptionGroups = mQueryParent(el + ' optgroup');
                var isUpdateOption = false;
                firstOptionGroups.each(function () {
                    var firstOptions = mQuery(this).children();
                    for (var i = 0; i < firstOptions.length; i++) {
                        if (firstOptions[i].value === response.id.toString()) {
                            firstOptions[i].text = response.name;
                            isUpdateOption = true;
                            break;
                        }
                    }
                });

                if (!isUpdateOption) {
                    //the optgroup exist so append to it
                    mQueryParent(optgroup + " option:last").prev().before(newOption);
                }
            } else {
                //create the optgroup
                var newOptgroup = mQuery('<optgroup label= />');
                newOption.appendTo(newOptgroup);
                mQueryParent(newOptgroup).appendTo(mQueryParent(el));
            }

            var optionGroups = sortOptions(mQueryParent(el + ' optgroup'));

            optionGroups.each(function () {
                var options = sortOptions(mQuery(this).children());
                mQuery(this).html(options);
            });

            var appendOptions = optionGroups;
        } else {
            newOption.appendTo(mQueryParent(el));

            var appendOptions = sortOptions(mQueryParent(el).children());
        }

        mQueryParent(el).html(appendOptions);
        if (defaultOption) {
            mQueryParent(el).prepend(defaultOption);
        }

        newOption.prop('selected', true);
        mQueryParent(el).trigger("chosen:updated");

        if (window.opener) {
            window.close();
        } else {
            mQueryParent('#MauticSharedModal').modal('hide');
        }
    },

    /**
     * Open modal route when a specific value is selected from a select list
     *
     * @param el
     * @param url
     * @param header
     */
    loadAjaxModalBySelectValue: function (el, value, route, header) {
        var selectVal = mQuery(el).val();
        var hasValue = (selectVal == value);
        if (!hasValue && mQuery.isArray(selectVal)) {
            hasValue = (mQuery.inArray(value, selectVal) !== -1);
        }
        if (hasValue) {
            // Remove it from the select
            route = route + (route.indexOf('?') > -1 ? '&' : '?') + 'modal=1&updateSelect=' + mQuery(el).attr('id');
            mQuery(el).find('option[value="' + value + '"]').prop('selected', false);
            mQuery(el).trigger("chosen:updated");
            Mautic.loadAjaxModal('#MauticSharedModal', route, 'get', header);
        }
    },

    /**
     * Open a popup
     * @param options
     */
    loadNewWindow: function (options) {
        if (options.windowUrl) {
            Mautic.startModalLoadingBar();

            setTimeout(function () {
                var opener = window.open(options.windowUrl, 'mauticpopup', 'height=600,width=1100');

                if (!opener || opener.closed || typeof opener.closed == 'undefined') {
                    alert(mauticLang.popupBlockerMessage);
                } else {
                    opener.onload = function () {
                        Mautic.stopModalLoadingBar();
                        Mautic.stopIconSpinPostEvent();
                    };
                }
            }, 100);
        }
    },

    /**
     * Convert to chosen select
     *
     * @param el
     */
    activateChosenSelect: function(el) {
        var noResultsText = mQuery(el).data('no-results-text');
        if (!noResultsText) {
            noResultsText = mauticLang['chosenNoResults'];
        }

        var isLookup = mQuery(el).attr('data-chosen-lookup');

        if (isLookup) {
            if (mQuery(el).attr('data-new-route')) {
                // Register method to initiate new
                mQuery(el).on('change', function () {
                    var url = mQuery(el).attr('data-new-route');
                    // If the element is already in a modal then use a popup
                    if (mQuery(el).closest('.modal').length > 0) {
                        var queryGlue = url.indexOf('?') >= 0 ? '&' : '?';
                        Mautic.loadNewWindow({
                            "windowUrl": url + queryGlue + "contentOnly=1&updateSelect=" + mQuery(el).attr('id')
                        });
                        // De-select the new select option
                        mQuery(el).find('option[value="new"]').prop('selected', false);
                        mQuery(el).trigger('chosen:updated');
                    } else {
                        Mautic.loadAjaxModalBySelectValue(this, 'new', url, mQuery(el).attr('data-header'));
                    }
                });
            }

            var multiPlaceholder = mauticLang['mautic.core.lookup.search_options'],
                singlePlaceholder = mauticLang['mautic.core.lookup.search_options'];
        } else {
            var multiPlaceholder = mauticLang['chosenChooseMore'],
                singlePlaceholder = mauticLang['chosenChooseOne'];
        }

        mQuery(el).chosen({
            placeholder_text_multiple: multiPlaceholder,
            placeholder_text_single: singlePlaceholder,
            no_results_text: noResultsText,
            width: "100%",
            allow_single_deselect: true,
            include_group_label_in_selected: true,
            search_contains: true
        });

        if (isLookup) {
            var searchTerm = mQuery(el).attr('data-model');

            if (searchTerm) {
                mQuery(el).ajaxChosen({
                    type: 'GET',
                    url: mauticAjaxUrl + '?action=' + mQuery(el).attr('data-chosen-lookup'),
                    dataType: 'json',
                    afterTypeDelay: 2,
                    jsonTermKey: searchTerm,
                    keepTypingMsg: "Keep typing...",
                    lookingForMsg: "Looking for"
                }, function (data) {
                    var results = [];

                    mQuery.each(data, function (i, val) {
                        results.push({value: val.value, text: val.text});
                    });

                    return results;
                });
            }
        }
    },

    /**
     * Activate a typeahead lookup
     *
     * @param field
     * @param target
     * @param options
     */
    activateFieldTypeahead: function (field, target, options, action) {
        if (options) {
            var keys = values = [];
            //check to see if there is a key/value split
            options = options.split('||');
            if (options.length == 2) {
                keys = options[1].split('|');
                values = options[0].split('|');
            } else {
                values = options[0].split('|');
            }

            var fieldTypeahead = Mautic.activateTypeahead('#' + field, {
                dataOptions: values,
                dataOptionKeys: keys,
                minLength: 0
            });
        } else {
            var fieldTypeahead = Mautic.activateTypeahead('#' + field, {
                prefetch: true,
                remote: true,
                action: action + "&field=" + target
            });
        }

        mQuery(fieldTypeahead).on('typeahead:selected', function (event, datum) {
            if (mQuery("#" + field).length && datum["value"]) {
                mQuery("#" + field).val(datum["value"]);
            }
        }).on('typeahead:autocompleted', function (event, datum) {
            if (mQuery("#" + field).length && datum["value"]) {
                mQuery("#" + field).val(datum["value"]);
            }
        });
    },

    /**
     * Convert to multiselect
     *
     * @param el
     */
    activateMultiSelect: function(el) {
        var moveOption = function(v, prev) {
            var theOption = mQuery(el).find('option[value="' + v + '"]').first();
            var lastSelected = mQuery(el).find('option:not(:disabled)').filter(function () {
                return mQuery(this).prop('selected');
            }).last();

            if (typeof prev !== 'undefined') {
                if (prev) {
                    var prevOption = mQuery(el).find('option[value="' + prev + '"]').first();
                    theOption.insertAfter(prevOption);
                    return;
                }
            } else if (lastSelected.length) {
                theOption.insertAfter(lastSelected);
                return;
            }
            theOption.prependTo(el);
        };

        mQuery(el).multiSelect({
            afterInit: function(container) {
                var funcName = mQuery(el).data('afterInit');
                if (funcName) {
                    Mautic[funcName]('init', container);
                }

                var selectThat = this,
                    $selectableSearch      = this.$selectableUl.prev(),
                    $selectionSearch       = this.$selectionUl.prev(),
                    selectableSearchString = '#' + this.$container.attr('id') + ' .ms-elem-selectable:not(.ms-selected)',
                    selectionSearchString  = '#' + this.$container.attr('id') + ' .ms-elem-selection.ms-selected';

                this.qs1 = $selectableSearch.quicksearch(selectableSearchString)
                    .on('keydown', function (e) {
                        if (e.which === 40) {
                            selectThat.$selectableUl.focus();
                            return false;
                        }
                    });

                this.qs2 = $selectionSearch.quicksearch(selectionSearchString)
                    .on('keydown', function (e) {
                        if (e.which == 40) {
                            selectThat.$selectionUl.focus();
                            return false;
                        }
                    });

                var selectOrder = mQuery(el).data('order');
                if (selectOrder && selectOrder.length > 1) {
                    this.deselect_all();
                    mQuery.each(selectOrder, function(k, v) {
                        selectThat.select(v);
                    });
                }

                var isSortable = mQuery(el).data('sortable');
                if (isSortable) {
                    mQuery(el).parent('.choice-wrapper').find('.ms-selection').first().sortable({
                        items: '.ms-elem-selection',
                        helper: function (e, ui) {
                            ui.width(mQuery(el).width());
                            return ui;
                        },
                        axis: 'y',
                        scroll: false,
                        update: function(event, ui) {
                            var prev      = ui.item.prev();
                            var prevValue = (prev.length) ? prev.data('ms-value') : '';
                            moveOption(ui.item.data('ms-value'), prevValue);
                        }
                    });
                }
            },
            afterSelect: function(value) {
                var funcName = mQuery(el).data('afterSelect');
                if (funcName) {
                    Mautic[funcName]('select', value);
                }
                this.qs1.cache();
                this.qs2.cache();

                moveOption(value);
            },
            afterDeselect: function(value) {
                var funcName = mQuery(el).data('afterDeselect');
                if (funcName) {
                    Mautic[funcName]('deselect', value);
                }

                this.qs1.cache();
                this.qs2.cache();
            },
            selectableHeader: "<input type='text' class='ms-search form-control' autocomplete='off'>",
            selectionHeader:  "<input type='text' class='ms-search form-control' autocomplete='off'>",
            keepOrder: true
        });
    },

    /**
     * Activate sortable
     *
     * @param el
     */
    activateSortable: function(el) {
        var prefix = mQuery(el).attr('data-prefix');
        if (mQuery('#' + prefix + '_additem').length) {
            mQuery('#' + prefix + '_additem').click(function () {
                var count = mQuery('#' + prefix + '_itemcount').val();
                var prototype = mQuery('#' + prefix + '_additem').attr('data-prototype');
                prototype = prototype.replace(/__name__/g, count);
                mQuery(prototype).appendTo(mQuery('#' + prefix + '_list div.list-sortable'));
                mQuery('#' + prefix + '_list_' + count).focus();
                count++;
                mQuery('#' + prefix + '_itemcount').val(count);
                return false;
            });
        }

        mQuery('#' + prefix + '_list div.list-sortable').sortable({
            items: 'div.sortable',
            handle: 'span.postaddon',
            axis: 'y',
            containment: '#' + prefix + '_list',
            stop: function (i) {
                var order = 0;
                mQuery('#' + prefix + '_list div.list-sortable div.input-group input').each(function () {
                    var name = mQuery(this).attr('name');
                    if (mQuery(this).hasClass('sortable-label')) {
                        name = name.replace(/(\[list\]\[[0-9]+\]\[label\])$/g, '') + '[list][' + order + '][label]';
                    } else if (mQuery(this).hasClass('sortable-value')) {
                        name = name.replace(/(\[list\]\[[0-9]+\]\[value\])$/g, '') + '[list][' + order + '][value]';
                        order++;
                    } else {
                        name = name.replace(/(\[list\]\[[0-9]+\])$/g, '') + '[list][' + order + ']';
                        order++;
                    }
                    mQuery(this).attr('name', name);
                });
            }
        });
    },

    /**
     * Activate containers datetime inputs
     * @param container
     */
    activateDateTimeInputs: function(el, type) {
        if (typeof type == 'undefined') {
            type = 'datetime';
        }

        var format = mQuery(el).data('format');
        if (type == 'datetime') {
            mQuery(el).datetimepicker({
                format: (format) ? format : 'Y-m-d H:i',
                lazyInit: true,
                validateOnBlur: false,
                allowBlank: true,
                scrollInput: false
            });
        } else if(type == 'date') {
            mQuery(el).datetimepicker({
                timepicker: false,
                format: (format) ? format : 'Y-m-d',
                lazyInit: true,
                validateOnBlur: false,
                allowBlank: true,
                scrollInput: false,
                closeOnDateSelect: true
            });
        } else if (type == 'time') {
            mQuery(el).datetimepicker({
                datepicker: false,
                format: (format) ? format : 'H:i',
                lazyInit: true,
                validateOnBlur: false,
                allowBlank: true,
                scrollInput: false
            });
        }

        mQuery(el).addClass('calendar-activated');
    },

    /**
     * Close global search results
     */
    closeGlobalSearchResults: function () {
        mQuery('#globalSearchContainer').removeClass('active');
        mQuery('#globalSearchDropdown').removeClass('open');
        mQuery('body').off('click.globalsearch');
    },

    /**
     * Functions to be ran on ajax page unload
     */
    onPageUnload: function (container, response) {
        //unload tooltips so they don't double show
        if (typeof container != 'undefined') {
            mQuery(container + " *[data-toggle='tooltip']").tooltip('destroy');

            //unload lingering modals from body so that there will not be multiple modals generated from new ajaxed content
            if (typeof MauticVars.modalsReset == 'undefined') {
                MauticVars.modalsReset = {};
            }

            mQuery(container + ' textarea.editor').each(function () {
                mQuery('textarea.editor').froalaEditor('destroy');
            });

            //turn off shuffle events
            mQuery('html')
                .off('fa.sidebar.minimize')
                .off('fa.sidebar.maximize');

            mQuery(container + " input[data-toggle='color']").each(function() {
                mQuery(this).minicolors('destroy');
            });
        }

        //run specific unloads
        var contentSpecific = false;
        if (container == '#app-content') {
            //full page gets precedence
            Mousetrap.reset();

            contentSpecific = mauticContent;

            // trash created chart objects to save some memory
            if (typeof Mautic.chartObjects !== 'undefined') {
                mQuery.each(Mautic.chartObjects, function (i, chart) {
                    chart.destroy();
                });
                Mautic.chartObjects = [];
            }

            // trash created map objects to save some memory
            if (typeof Mautic.mapObjects !== 'undefined') {
                mQuery.each(Mautic.mapObjects, function (i, map) {
                    Mautic.destroyMap(map);
                });
                Mautic.mapObjects = [];
            }

    	    // trash tokens to save some memory
            if (typeof Mautic.builderTokens !== 'undefined') {
                Mautic.builderTokens = {};
            }
        } else if (response && response.mauticContent) {
            contentSpecific = response.mauticContent;
        }

        if (contentSpecific) {
            if (typeof Mautic[contentSpecific + "OnUnload"] == 'function') {
                Mautic[contentSpecific + "OnUnload"](container, response);
            }

            if (typeof (Mautic.loadedContent[contentSpecific])) {
                delete Mautic.loadedContent[contentSpecific];
            }
        }

    },

    /**
     * Takes a given route, retrieves the HTML, and then updates the content
     * @param route
     * @param link
     * @param method
     * @param target
     * @param showPageLoading
     * @param callback
     * @param data
     */
    loadContent: function (route, link, method, target, showPageLoading, callback, data) {
        if (typeof Mautic.loadContentXhr == 'undefined') {
            Mautic.loadContentXhr = {};
        } else if (typeof Mautic.loadContentXhr[target] != 'undefined') {
            Mautic.loadContentXhr[target].abort();
        }

        showPageLoading = (typeof showPageLoading == 'undefined' || showPageLoading) ? true : false;

        Mautic.loadContentXhr[target] = mQuery.ajax({
            showLoadingBar: showPageLoading,
            url: route,
            type: method,
            dataType: "json",
            data: data,
            success: function (response) {
                if (response) {
                    response.stopPageLoading = showPageLoading;

                    if (response.callback) {
                        window["Mautic"][response.callback].apply('window', [response]);
                        return;
                    }
                    if (response.redirect) {
                        Mautic.redirectWithBackdrop(response.redirect);
                    } else if (target || response.target) {
                        if (target) response.target = target;
                        Mautic.processPageContent(response);
                    } else {
                        //clear the live cache
                        MauticVars.liveCache = new Array();
                        MauticVars.lastSearchStr = '';

                        //set route and activeLink if the response didn't override
                        if (typeof response.route === 'undefined') {
                            response.route = route;
                        }

                        if (typeof response.activeLink === 'undefined' && link) {
                            response.activeLink = link;
                        }

                        Mautic.processPageContent(response);
                    }

                    //restore button class if applicable
                    Mautic.stopIconSpinPostEvent();
                }
                MauticVars.routeInProgress = '';
            },
            error: function (request, textStatus, errorThrown) {
                Mautic.processAjaxError(request, textStatus, errorThrown, true);

                //clear routeInProgress
                MauticVars.routeInProgress = '';

                //restore button class if applicable
                Mautic.stopIconSpinPostEvent();

                //stop loading bar
                Mautic.stopPageLoadingBar();
            },
            complete: function () {
                if (typeof callback !== 'undefined') {
                    if (typeof callback == 'function') {
                        callback();
                    } else {
                        window["Mautic"][callback].apply('window', []);
                    }
                }
                Mautic.generatePageTitle( route );
                delete Mautic.loadContentXhr[target];
            }
        });

        //prevent firing of href link
        //mQuery(link).attr("href", "javascript: void(0)");
        return false;
    },

    /**
    * Generates the title of the current page
    *
    * @param route
    */
    generatePageTitle: function(route){

        if( -1 !== route.indexOf('view') ){
            //loading view of module title
            var currentModule = route.split('/')[3];

            //check if we find spans
            var titleWithHTML = mQuery('.page-header h3').find('span.span-block');
            var currentModuleItem = '';

            if( 1 < titleWithHTML.length ){
                currentModuleItem = titleWithHTML.eq(0).text() + ' - ' + titleWithHTML.eq(1).text();
            } else {
                currentModuleItem = mQuery('.page-header h3').text();
            }

            mQuery('title').html( currentModule[0].toUpperCase() + currentModule.slice(1) + ' | ' + currentModuleItem + ' | Mautic' );
        } else {
            //loading basic title
            mQuery('title').html( mQuery('.page-header h3').html() + ' | Mautic' );
        }
    },

    /**
     * Inserts a new javascript file request into the document head
     *
     * @param url
     * @param onLoadCallback
     * @param alreadyLoadedCallback
     */
    loadScript: function (url, onLoadCallback, alreadyLoadedCallback) {
        // check if the asset has been loaded
        if (typeof Mautic.headLoadedAssets == 'undefined') {
            Mautic.headLoadedAssets = {};
        } else if (typeof Mautic.headLoadedAssets[url] != 'undefined') {
            // URL has already been appended to head

            if (alreadyLoadedCallback && typeof Mautic[alreadyLoadedCallback] == 'function') {
                Mautic[alreadyLoadedCallback]();
            }

            return;
        }

        // Note that asset has been appended
        Mautic.headLoadedAssets[url] = 1;

        mQuery.getScript(url, function( data, textStatus, jqxhr ) {
            if (textStatus == 'success') {
                if (onLoadCallback && typeof Mautic[onLoadCallback] == 'function') {
                    Mautic[onLoadCallback]();
                } else if (typeof Mautic[mauticContent + "OnLoad"] == 'function') {
                    // Likely a page refresh; execute onLoad content
                    if (typeof Mautic.loadedContent[mauticContent] == 'undefined') {
                        Mautic.loadedContent[mauticContent] = true;
                        Mautic[mauticContent + "OnLoad"]('#app-content', {});
                    }
                }
            }
        });
    },

    /**
     * Inserts a new stylesheet into the document head
     *
     * @param url
     */
    loadStylesheet: function (url) {
        // check if the asset has been loaded
        if (typeof Mautic.headLoadedAssets == 'undefined') {
            Mautic.headLoadedAssets = {};
        } else if (typeof Mautic.headLoadedAssets[url] != 'undefined') {
            // URL has already been appended to head
            return;
        }

        // Note that asset has been appended
        Mautic.headLoadedAssets[url] = 1;

        var link = document.createElement("link");
        link.type = "text/css";
        link.rel = "stylesheet";
        link.href = url;
        mQuery('head').append(link);
    },

    /**
     * Just a little visual that an action is taking place
     *
     * @param event|string
     */
    startIconSpinOnEvent: function (target) {
        if (MauticVars.ignoreIconSpin) {
            MauticVars.ignoreIconSpin = false;
            return;
        }

        if (typeof target == 'object' && typeof(target.target) !== 'undefined') {
            target = target.target;
        }

        if (mQuery(target).length) {
            var hasBtn = mQuery(target).hasClass('btn');
            var hasIcon = mQuery(target).hasClass('fa');

            var i = (hasBtn && mQuery(target).find('i.fa').length) ? mQuery(target).find('i.fa') : target;

            if ((hasBtn && mQuery(target).find('i.fa').length) || hasIcon) {
                var el = (hasIcon) ? target : mQuery(target).find('i.fa').first();
                var identifierClass = (new Date).getTime();
                MauticVars.iconClasses[identifierClass] = mQuery(el).attr('class');

                var specialClasses = ['fa-fw', 'fa-lg', 'fa-2x', 'fa-3x', 'fa-4x', 'fa-5x', 'fa-li', 'text-white', 'text-muted'];
                var appendClasses = "";

                //check for special classes to add to spinner
                for (var i = 0; i < specialClasses.length; i++) {
                    if (mQuery(el).hasClass(specialClasses[i])) {
                        appendClasses += " " + specialClasses[i];
                    }
                }
                mQuery(el).removeClass();
                mQuery(el).addClass('fa fa-spinner fa-spin ' + identifierClass + appendClasses);
            }
        }
    },

    /**
     * Stops the icon spinning after an event is complete
     */
    stopIconSpinPostEvent: function (specificId) {
        if (typeof specificId != 'undefined' && specificId in MauticVars.iconClasses) {
            mQuery('.' + specificId).removeClass('fa fa-spinner fa-spin ' + specificId).addClass(MauticVars.iconClasses[specificId]);
            delete MauticVars.iconClasses[specificId];
        } else {
            mQuery.each(MauticVars.iconClasses, function (index, value) {
                mQuery('.' + index).removeClass('fa fa-spinner fa-spin ' + index).addClass(value);
            });

            MauticVars.iconClasses = {};
        }
    },

    /**
     * Displays backdrop with wait message then redirects
     *
     * @param url
     */
    redirectWithBackdrop: function(url) {
        Mautic.activateBackdrop();
        setTimeout(function() {
            window.location = url;
        }, 50);
    },

    /**
     * Acivates a backdrop
     */
    activateBackdrop: function(hideWait) {
        if (!mQuery('#mautic-backdrop').length) {
            var container = mQuery('<div />', {
                id: 'mautic-backdrop'
            });

            mQuery('<div />', {
                'class': 'modal-backdrop fade in'
            }).appendTo(container);

            if (typeof hideWait == 'undefined') {
                mQuery('<div />', {
                    "class": 'mautic-pleasewait'
                }).html(mauticLang.pleaseWait)
                    .appendTo(container);
            }

            container.appendTo('body');
        }
    },

    /**
     * Deactivates backdrop
     */
    deactivateBackgroup: function() {
        if (mQuery('#mautic-backdrop').length) {
            mQuery('#mautic-backdrop').remove();
        }
    },

    /**
     * Posts a form and returns the output.
     * Uses jQuery form plugin so it handles files as well.
     *
     * @param form
     * @param callback
     */
    postForm: function (form, callback) {
        var form = mQuery(form);

        var modalParent = form.closest('.modal');
        var inMain = mQuery(modalParent).length > 0 ? false : true;

        var action = form.attr('action');

        if (!inMain) {
            var modalTarget = '#' + mQuery(modalParent).attr('id');
            Mautic.startModalLoadingBar(modalTarget);
        }
        var showLoading = (!inMain || form.attr('data-hide-loadingbar')) ? false : true;

        form.ajaxSubmit({
            showLoadingBar: showLoading,
            success: function (data) {
                if (!inMain) {
                    Mautic.stopModalLoadingBar(modalTarget);
                }

                if (data.redirect) {
                    Mautic.redirectWithBackdrop(data.redirect);
                } else {
                    MauticVars.formSubmitInProgress = false;
                    if (!inMain) {
                        var modalId = mQuery(modalParent).attr('id');
                    }

                    if (data.sessionExpired) {
                        if (!inMain) {
                            mQuery('#' + modalId).modal('hide');
                            mQuery('.modal-backdrop').remove();
                        }
                        Mautic.processPageContent(data);
                    } else if (callback) {
                        data.inMain = inMain;

                        if (!inMain) {
                            data.modalId = modalId;
                        }

                        if (typeof callback == 'function') {
                            callback(data);
                        } else if (typeof Mautic[callback] == 'function') {
                            Mautic[callback](data);
                        }
                    }
                }
            },
            error: function (request, textStatus, errorThrown) {
                MauticVars.formSubmitInProgress = false;

                Mautic.processAjaxError(request, textStatus, errorThrown, inMain);
            }
        });
    },

    /**
     * Updates new content
     * @param response
     */
    processPageContent: function (response) {
        if (response) {
            Mautic.deactivateBackgroup();

            if (!response.target) {
                response.target = '#app-content';
            }

            //inactive tooltips, etc
            Mautic.onPageUnload(response.target, response);

            //set content
            if (response.newContent) {
                if (response.replaceContent && response.replaceContent == 'true') {
                    mQuery(response.target).replaceWith(response.newContent);
                } else {
                    mQuery(response.target).html(response.newContent);
                }
            }

            if (response.flashes) {
                Mautic.setFlashes(response.flashes);
            }

            if (response.notifications) {
                Mautic.setNotifications(response.notifications);
            }

            if (response.browserNotifications) {
                Mautic.setBrowserNotifications(response.browserNotifications);
            }

            if (response.route) {
                //update URL in address bar
                MauticVars.manualStateChange = false;
                History.pushState(null, "Mautic", response.route);

                //update Title
                Mautic.generatePageTitle( response.route );
            }

            if (response.target == '#app-content') {
                //update type of content displayed
                if (response.mauticContent) {
                    mauticContent = response.mauticContent;
                }

                if (response.activeLink) {
                    var link = response.activeLink;
                    if (link !== undefined && link.charAt(0) != '#') {
                        link = "#" + link;
                    }

                    var parent = mQuery(link).parent();

                    //remove current classes from menu items
                    mQuery(".nav-sidebar").find(".active").removeClass("active");

                    //add current to parent <li>
                    parent.addClass("active");

                    //get parent
                    var openParent = parent.closest('li.open');

                    //remove ancestor classes
                    mQuery(".nav-sidebar").find(".open").each(function () {
                        if (!openParent.hasClass('open') || (openParent.hasClass('open') && openParent[0] !== mQuery(this)[0])) {
                            mQuery(this).removeClass('open');
                        }
                    });

                    //add current_ancestor classes
                    //mQuery(parent).parentsUntil(".nav-sidebar", "li").addClass("current_ancestor");
                }

                mQuery('body').animate({
                    scrollTop: 0
                }, 0);

            } else {
                var overflow = mQuery(response.target).css('overflow');
                var overflowY = mQuery(response.target).css('overflowY');
                if (overflow == 'auto' || overflow == 'scroll' || overflowY == 'auto' || overflowY == 'scroll') {
                    mQuery(response.target).animate({
                        scrollTop: 0
                    }, 0);
                }
            }

            if (response.overlayEnabled) {
                mQuery(response.overlayTarget + ' .content-overlay').remove();
            }

            //activate content specific stuff
            Mautic.onPageLoad(response.target, response);
        }
    },

    /**
     * Prepares form for ajax submission
     * @param form
     */
    ajaxifyForm: function (formName) {
        Mautic.initializeFormFieldVisibilitySwitcher(formName);

        //prevent enter submitting form and instead jump to next line
        var form = 'form[name="' + formName + '"]';
        mQuery(form + ' input, ' + form + ' select').off('keydown.ajaxform');
        mQuery(form + ' input, ' + form + ' select').on('keydown.ajaxform', function (e) {
            if(e.keyCode == 13 && (e.metaKey || e.ctrlKey)) {
                if (MauticVars.formSubmitInProgress) {
                    return false;
                }

                // Find save button first then apply
                var saveButton = mQuery(form).find('button.btn-save');
                var applyButton = mQuery(form).find('button.btn-apply');

                var modalParent = mQuery(form).closest('.modal');
                var inMain      = mQuery(modalParent).length > 0 ? false : true;

                if (mQuery(saveButton).length) {
                    if (inMain) {
                        if (mQuery(form).find('button.btn-save.btn-copy').length) {
                            mQuery(mQuery(form).find('button.btn-save.btn-copy')).trigger('click');

                            return;
                        }
                    } else {
                        if (mQuery(modalParent).find('button.btn-save.btn-copy').length) {
                            mQuery(mQuery(modalParent).find('button.btn-save.btn-copy')).trigger('click');

                            return;
                        }
                    }

                    mQuery(saveButton).trigger('click');
                } else if (mQuery(applyButton).length) {
                    if (inMain) {
                        if (mQuery(form).find('button.btn-apply.btn-copy').length) {
                            mQuery(mQuery(form).find('button.btn-apply.btn-copy')).trigger('click');

                            return;
                        }
                    } else {
                        if (mQuery(modalParent).find('button.btn-apply.btn-copy').length) {
                            mQuery(mQuery(modalParent).find('button.btn-apply.btn-copy')).trigger('click');

                            return;
                        }
                    }

                    mQuery(applyButton).trigger('click');
                }
            } else if (e.keyCode == 13 && mQuery(e.target).is(':input')) {
                var inputs = mQuery(this).parents('form').eq(0).find(':input');
                if (inputs[inputs.index(this) + 1] != null) {
                    inputs[inputs.index(this) + 1].focus();
                }
                e.preventDefault();
                return false;
            }
        });

        //activate the submit buttons so symfony knows which were clicked
        mQuery(form + ' :submit').each(function () {
            mQuery(this).off('click.ajaxform');
            mQuery(this).on('click.ajaxform', function () {
                if (mQuery(this).attr('name') && !mQuery("input[name='" + mQuery(this).attr('name') + "']").length) {
                    mQuery('form[name="' + formName + '"]').append(
                        mQuery("<input type='hidden'>").attr({
                            name: mQuery(this).attr('name'),
                            value: mQuery(this).attr('value')
                        })
                    );
                }
            });
        });

        //activate the forms
        mQuery(form).off('submit.ajaxform');
        mQuery(form).on('submit.ajaxform', (function (e) {
            e.preventDefault();
            var form = mQuery(this);

            if (MauticVars.formSubmitInProgress) {
                return false;
            } else {
                var callbackAsync = form.data('submit-callback-async');
                if (callbackAsync && typeof Mautic[callbackAsync] == 'function') {
                    Mautic[callbackAsync].apply(this, [form, function() {
                        Mautic.postMauticForm(form);
                    }]);
                } else {
                    var callback = form.data('submit-callback');

                    // Allow a callback to do stuff before submit and abort if needed
                    if (callback && typeof Mautic[callback] == 'function') {
                        if (!Mautic[callback]()) {
                            return false;
                        }
                    }

                    Mautic.postMauticForm(form);
                }
            }

            return false;
        }));
    },

    postMauticForm: function(form) {
        MauticVars.formSubmitInProgress = true;
        Mautic.postForm(form, function (response) {
            if (response.inMain) {
                Mautic.processPageContent(response);
            } else {
                Mautic.processModalContent(response, '#' + response.modalId);
            }
        });
    },

    /**
     * Retrieves content of href via ajax
     * @param el
     * @param event
     * @returns {boolean}
     */
    ajaxifyLink: function (el, event) {
        if (mQuery(el).hasClass('disabled')) {
            return false;
        }

        var route = mQuery(el).attr('href');
        if (route.indexOf('javascript') >= 0 || MauticVars.routeInProgress === route) {
            return false;
        }

        if (event.ctrlKey || event.metaKey) {
            //open the link in a new window
            route = route.split("?")[0];
            window.open(route, '_blank');
            return;
        }

        //prevent leaving if currently in a form
        if (mQuery(".form-exit-unlock-id").length) {
            if (mQuery(el).attr('data-ignore-formexit') != 'true') {
                var unlockParameter = (mQuery('.form-exit-unlock-parameter').length) ? mQuery('.form-exit-unlock-parameter').val() : '';
                Mautic.unlockEntity(mQuery('.form-exit-unlock-model').val(), mQuery('.form-exit-unlock-id').val(), unlockParameter);
            }
        }

        var link = mQuery(el).attr('data-menu-link');
        if (link !== undefined && link.charAt(0) != '#') {
            link = "#" + link;
        }

        var method = mQuery(el).attr('data-method');
        if (!method) {
            method = 'GET'
        }

        MauticVars.routeInProgress = route;

        var target = mQuery(el).attr('data-target');
        if (!target) {
            target = null;
        }

        //give an ajaxified link the option of not displaying the global loading bar
        var showLoadingBar = (mQuery(el).attr('data-hide-loadingbar')) ? false : true;

        //close the global search results if opened
        if (mQuery('#globalSearchContainer').length && mQuery('#globalSearchContainer').hasClass('active')) {
            Mautic.closeGlobalSearchResults();
        }

        Mautic.loadContent(route, link, method, target, showLoadingBar);
    },

    /**
     * Load a modal with ajax content
     *
     * @param el
     * @param event
     *
     * @returns {boolean}
     */
    ajaxifyModal: function (el, event) {
        if (mQuery(el).hasClass('disabled')) {
            return false;
        }

        var target = mQuery(el).attr('data-target');

        var route = (mQuery(el).attr('data-href')) ? mQuery(el).attr('data-href') : mQuery(el).attr('href');
        if (route.indexOf('javascript') >= 0) {
            return false;
        }

        mQuery('body').addClass('noscroll');

        var method = mQuery(el).attr('data-method');
        if (!method) {
            method = 'GET'
        }

        var header = mQuery(el).attr('data-header');
        var footer = mQuery(el).attr('data-footer');

        var preventDismissal = mQuery(el).attr('data-prevent-dismiss');
        if (preventDismissal) {
            // Reset
            mQuery(el).removeAttr('data-prevent-dismiss');
        }


        Mautic.loadAjaxModal(target, route, method, header, footer, preventDismissal);
    },

    /**
     * Retrieve ajax content for modal
     * @param target
     * @param route
     * @param method
     * @param header
     * @param footer
     */
    loadAjaxModal: function (target, route, method, header, footer, preventDismissal) {

        //show the modal
        if (mQuery(target + ' .loading-placeholder').length) {
            mQuery(target + ' .loading-placeholder').removeClass('hide');
            mQuery(target + ' .modal-body-content').addClass('hide');

            if (mQuery(target + ' .modal-loading-bar').length) {
                mQuery(target + ' .modal-loading-bar').addClass('active');
            }
        }

        if (footer == 'false') {
            mQuery(target + " .modal-footer").addClass('hide');
        }

        //move the modal to the body tag to get around positioned div issues
        mQuery(target).on('show.bs.modal', function () {
            if (header) {
                mQuery(target + " .modal-title").html(header);
            }

            if (footer && footer != 'false') {
                mQuery(target + " .modal-footer").html(header);
            }
        });

        //clean slate upon close
        mQuery(target).on('hidden.bs.modal', function () {
            mQuery('body').removeClass('noscroll');

            //unload
            Mautic.onPageUnload(target);

            Mautic.resetModal(target);

            if (typeof Mautic.modalContentXhr[target] != 'undefined') {
                Mautic.modalContentXhr[target].abort();
                delete Mautic.modalContentXhr[target];
            }
        });

        // Check if dismissal is allowed
        if (typeof mQuery(target).data('bs.modal') !== 'undefined' && typeof mQuery(target).data('bs.modal').options !== 'undefined') {
            if (preventDismissal) {
                mQuery(target).data('bs.modal').options.keyboard = false;
                mQuery(target).data('bs.modal').options.backdrop = 'static';
            } else {
                mQuery(target).data('bs.modal').options.keyboard = true;
                mQuery(target).data('bs.modal').options.backdrop = true;
            }
        } else {
            if (preventDismissal) {
                mQuery(target).modal({
                    backdrop: 'static',
                    keyboard: false
                });
            } else {
                mQuery(target).modal({
                    backdrop: true,
                    keyboard: true
                });
            }
        }

        mQuery(target).modal('show');

        if (typeof Mautic.modalContentXhr == 'undefined') {
            Mautic.modalContentXhr = {};
        } else if (typeof Mautic.modalContentXhr[target] != 'undefined') {
            Mautic.modalContentXhr[target].abort();
        }

        Mautic.modalContentXhr[target] = mQuery.ajax({
            url: route,
            type: method,
            dataType: "json",
            success: function (response) {
                if (response) {
                    Mautic.processModalContent(response, target);
                }
                Mautic.stopIconSpinPostEvent();
            },
            error: function (request, textStatus, errorThrown) {
                Mautic.processAjaxError(request, textStatus, errorThrown);
                Mautic.stopIconSpinPostEvent();
            },
            complete: function () {
                Mautic.stopModalLoadingBar(target);
                delete Mautic.modalContentXhr[target];
            }
        });
    },

    /**
     * Clears content from a shared modal
     * @param target
     */
    resetModal: function (target) {
        if (mQuery(target).hasClass('in')) {
            return;
        }

        mQuery(target + " .modal-title").html('');
        mQuery(target + " .modal-body-content").html('');

        if (mQuery(target + " loading-placeholder").length) {
            mQuery(target + " loading-placeholder").removeClass('hide');
        }
        if (mQuery(target + " .modal-footer").length) {
            var hasFooterButtons = mQuery(target + " .modal-footer .modal-form-buttons").length;
            mQuery(target + " .modal-footer").html('');
            if (hasFooterButtons) {
                //add footer buttons
                mQuery('<div class="modal-form-buttons" />').appendTo(target + " .modal-footer");
            }
            mQuery(target + " .modal-footer").removeClass('hide');
        }
    },

    /**
     * Handles modal content post ajax request
     * @param response
     * @param target
     */
    processModalContent: function (response, target) {
        if (response.error) {
            Mautic.stopIconSpinPostEvent();

            alert(response.error);
            return;
        }

        if (response.sessionExpired || (response.closeModal && response.newContent)) {
            mQuery(target).modal('hide');
            mQuery('body').removeClass('modal-open');
            mQuery('.modal-backdrop').remove();
            //assume the content is to refresh main app
            Mautic.processPageContent(response);
        } else {
            if (response.flashes) {
                Mautic.setFlashes(response.flashes);
            }

            if (response.notifications) {
                Mautic.setNotifications(response.notifications);
            }

            if (response.browserNotifications) {
                Mautic.setBrowserNotifications(response.browserNotifications);
            }

            if (response.callback) {
                window["Mautic"][response.callback].apply('window', [response]);
                return;
            }

            if (response.closeModal) {
                mQuery('body').removeClass('noscroll');
                mQuery(target).modal('hide');
                Mautic.onPageUnload(target, response);

                if (response.mauticContent) {
                    if (typeof Mautic[response.mauticContent + "OnLoad"] == 'function') {
                        if (typeof Mautic.loadedContent[response.mauticContent] == 'undefined') {
                            Mautic.loadedContent[response.mauticContent] = true;
                            Mautic[response.mauticContent + "OnLoad"](target, response);
                        }
                    }
                }
            } else if (response.target) {
                mQuery(response.target).html(response.newContent);

                //activate content specific stuff
                Mautic.onPageLoad(response.target, response, true);
            } else {
                //load the content
                if (mQuery(target + ' .loading-placeholder').length) {
                    mQuery(target + ' .loading-placeholder').addClass('hide');
                    mQuery(target + ' .modal-body-content').html(response.newContent);
                    mQuery(target + ' .modal-body-content').removeClass('hide');
                } else {
                    mQuery(target + ' .modal-body').html(response.newContent);
                }

                //activate content specific stuff
                Mautic.onPageLoad(target, response, true);
            }
        }
    },

    /**
     * Display confirmation modal
     */
    showConfirmation: function (el) {
        var precheck = mQuery(el).data('precheck');

        if (precheck) {
            if (typeof precheck == 'function') {
                if (!precheck()) {
                    return;
                }
            } else if (typeof Mautic[precheck] == 'function') {
                if (!Mautic[precheck]()) {
                    return;
                }
            }
        }

        var message = mQuery(el).data('message');
        var confirmText = mQuery(el).data('confirm-text');
        var confirmAction = mQuery(el).attr('href');
        var confirmCallback = mQuery(el).data('confirm-callback');
        var cancelText = mQuery(el).data('cancel-text');
        var cancelCallback = mQuery(el).data('cancel-callback');

        var confirmContainer = mQuery("<div />").attr({"class": "modal fade confirmation-modal"});
        var confirmDialogDiv = mQuery("<div />").attr({"class": "modal-dialog"});
        var confirmContentDiv = mQuery("<div />").attr({"class": "modal-content"});
        var confirmFooterDiv = mQuery("<div />").attr({"class": "modal-body text-center"});
        var confirmHeaderDiv = mQuery("<div />").attr({"class": "modal-header"});
        confirmHeaderDiv.append(mQuery('<h4 />').attr({"class": "modal-title"}).text(message));
        var confirmButton = mQuery('<button type="button" />')
            .addClass("btn btn-danger")
            .css("marginRight", "5px")
            .css("marginLeft", "5px")
            .click(function () {
                if (typeof Mautic[confirmCallback] === "function") {
                    window["Mautic"][confirmCallback].apply('window', [confirmAction, el]);
                }
            })
            .html(confirmText);
        if (cancelText) {
            var cancelButton = mQuery('<button type="button" />')
                .addClass("btn btn-primary")
                .click(function () {
                    if (cancelCallback && typeof Mautic[cancelCallback] === "function") {
                        window["Mautic"][cancelCallback].apply('window', []);
                    } else {
                        Mautic.dismissConfirmation();
                    }
                })
                .html(cancelText);
        }

        if (typeof cancelButton != 'undefined') {
            confirmFooterDiv.append(cancelButton);
        }

        confirmFooterDiv.append(confirmButton);

        confirmContentDiv.append(confirmHeaderDiv);
        confirmContentDiv.append(confirmFooterDiv);

        confirmContainer.append(confirmDialogDiv.append(confirmContentDiv));
        mQuery('body').append(confirmContainer);

        mQuery('.confirmation-modal').on('hidden.bs.modal', function () {
            mQuery(this).remove();
        });

        mQuery('.confirmation-modal').modal('show');

    },

    /**
     * Dismiss confirmation modal
     */
    dismissConfirmation: function () {
        if (mQuery('.confirmation-modal').length) {
            mQuery('.confirmation-modal').modal('hide');
        }
    },

    /**
     * Reorder table data
     * @param name
     * @param orderby
     * @param tmpl
     * @param target
     */
    reorderTableData: function (name, orderby, tmpl, target, baseUrl) {
        if (typeof baseUrl == 'undefined') {
            baseUrl = window.location.pathname;
        }

        if (baseUrl.indexOf('tmpl') == -1) {
            baseUrl = baseUrl + "?tmpl=" + tmpl
        }

        var route = baseUrl + "&name=" + name + "&orderby=" + encodeURIComponent(orderby);
        Mautic.loadContent(route, '', 'POST', target);
    },

    /**
     *
     * @param name
     * @param filterby
     * @param filterValue
     * @param tmpl
     * @param target
     */
    filterTableData: function (name, filterby, filterValue, tmpl, target, baseUrl) {
        if (typeof baseUrl == 'undefined') {
            baseUrl = window.location.pathname;
        }

        if (baseUrl.indexOf('tmpl') == -1) {
            baseUrl = baseUrl + "?tmpl=" + tmpl
        }

        var route = baseUrl + "&name=" + name + "&filterby=" + encodeURIComponent(filterby) + "&value=" + encodeURIComponent(filterValue)
        Mautic.loadContent(route, '', 'POST', target);
    },

    /**
     *
     * @param name
     * @param limit
     * @param tmpl
     * @param target
     */
    limitTableData: function (name, limit, tmpl, target, baseUrl) {
        if (typeof baseUrl == 'undefined') {
            baseUrl = window.location.pathname;
        }

        if (baseUrl.indexOf('tmpl') == -1) {
            baseUrl = baseUrl + "?tmpl=" + tmpl
        }

        var route = baseUrl + "&name=" + name + "&limit=" + limit;
        Mautic.loadContent(route, '', 'POST', target);
    },

    /**
     * Executes an object action
     *
     * @param action
     */
    executeAction: function (action) {
        if (typeof Mautic.activeActions == 'undefined') {
            Mautic.activeActions = {};
        } else if (typeof Mautic.activeActions[action] != 'undefined') {
            // Action is currently being executed
            return;
        }

        Mautic.activeActions[action] = true;

        //dismiss modal if activated
        Mautic.dismissConfirmation();
        mQuery.ajax({
            showLoadingBar: true,
            url: action,
            type: "POST",
            dataType: "json",
            success: function (response) {
                Mautic.processPageContent(response);
            },
            error: function (request, textStatus, errorThrown) {
                Mautic.processAjaxError(request, textStatus, errorThrown);
            },
            complete: function() {
                delete Mautic.activeActions[action]
            }
        });
    },

    /**
     * Executes a batch action
     *
     * @param action
     */
    executeBatchAction: function (action, el) {
        if (typeof Mautic.activeActions == 'undefined') {
            Mautic.activeActions = {};
        } else if (typeof Mautic.activeActions[action] != 'undefined') {
            // Action is currently being executed
            return;
        }

        var items = Mautic.getCheckedListIds(el, true);

        var queryGlue = action.indexOf('?') >= 0 ? '&' : '?';

        // Append the items to the action to send with the POST
        var action = action + queryGlue + 'ids=' + items;

        // Hand over processing to the executeAction method
        Mautic.executeAction(action);
    },

    /**
     * Retrieves the IDs of the items checked in a list
     *
     * @param el
     * @param stringify
     * @returns {*}
     */
    getCheckedListIds: function(el, stringify) {
        var checkboxes = 'input[class=list-checkbox]:checked';

        // Check for a target
        if (typeof el != 'undefined' && el) {
            var target = mQuery(el).data('target');
            if (target) {
                checkboxes = target + ' ' + checkboxes;
            }
        }

        // Retrieve all of the selected items
        var items = mQuery(checkboxes).map(function () {
            return mQuery(this).val();
        }).get();

        if (stringify) {
            items = JSON.stringify(items);
        }

        return items;
    },

    /**
     * Checks that items are checked before showing confirmation
     *
     * @returns int
     */
    batchActionPrecheck: function(container) {
        if (typeof container == 'undefined') {
            container = '';
        }
        return mQuery(container + ' input[class=list-checkbox]:checked').length;
    },

    /**
     * Activates Typeahead.js command lists for search boxes
     * @param elId
     * @param modelName
     */
    activateSearchAutocomplete: function (elId, modelName) {
        if (mQuery('#' + elId).length) {
            var livesearch = (mQuery('#' + elId).attr("data-toggle=['livesearch']")) ? true : false;

            var typeaheadObject = Mautic.activateTypeahead('#' + elId, {
                prefetch: true,
                remote: false,
                limit: 0,
                action: 'commandList&model=' + modelName,
                multiple: true
            });
            mQuery(typeaheadObject).on('typeahead:selected', function (event, datum) {
                if (livesearch) {
                    //force live search update,
                    MauticVars.lastSearchStr = '';
                    mQuery('#' + elId).keyup();
                }
            }).on('typeahead:autocompleted', function (event, datum) {
                if (livesearch) {
                    //force live search update
                    MauticVars.lastSearchStr = '';
                    mQuery('#' + elId).keyup();
                }
            });
        }
    },

    /**
     * Activate live search feature
     *
     * @param el
     * @param searchStrVar
     * @param liveCacheVar
     */
    activateLiveSearch: function (el, searchStrVar, liveCacheVar) {
        if (!mQuery(el).length) {
            return;
        }

        //find associated button
        var btn = "button[data-livesearch-parent='" + mQuery(el).attr('id') + "']";

        mQuery(el).on('focus', function () {
            Mautic.currentSearchString = mQuery(this).val().trim();
        });
        mQuery(el).on('change keyup paste', {}, function (event) {
            var searchStr = mQuery(el).val().trim();

            var spaceKeyPressed = (event.which == 32 || event.keyCode == 32);
            var enterKeyPressed = (event.which == 13 || event.keyCode == 13);
            var deleteKeyPressed = (event.which == 8 || event.keyCode == 8);

            if (!enterKeyPressed && Mautic.currentSearchString && Mautic.currentSearchString == searchStr) {
                return;
            }

            var target = mQuery(el).attr('data-target');
            var diff = searchStr.length - MauticVars[searchStrVar].length;

            if (diff < 0) {
                diff = parseInt(diff) * -1;
            }

            var overlayEnabled = mQuery(el).attr('data-overlay');
            if (!overlayEnabled || overlayEnabled == 'false') {
                overlayEnabled = false;
            } else {
                overlayEnabled = true;
            }

            var overlayTarget = mQuery(el).attr('data-overlay-target');
            if (!overlayTarget) overlayTarget = target;

            if (overlayEnabled) {
                mQuery(el).off('blur.livesearchOverlay');
                mQuery(el).on('blur.livesearchOverlay', function() {
                   mQuery(overlayTarget + ' .content-overlay').remove();
                });
            }

            if (!deleteKeyPressed && overlayEnabled) {
                var overlay = mQuery('<div />', {"class": "content-overlay"}).html(mQuery(el).attr('data-overlay-text'));
                if (mQuery(el).attr('data-overlay-background')) {
                    overlay.css('background', mQuery(el).attr('data-overlay-background'));
                }
                if (mQuery(el).attr('data-overlay-color')) {
                    overlay.css('color', mQuery(el).attr('data-overlay-color'));
                }
            }

            //searchStr in MauticVars[liveCacheVar] ||
            if ((!searchStr && MauticVars[searchStrVar].length) || diff >= 3 || spaceKeyPressed || enterKeyPressed) {
                MauticVars[searchStrVar] = searchStr;
                event.data.livesearch = true;

                Mautic.filterList(event,
                    mQuery(el).attr('id'),
                    mQuery(el).attr('data-action'),
                    target,
                    liveCacheVar,
                    overlayEnabled,
                    overlayTarget
                );
            } else if (overlayEnabled) {
                if (!mQuery(overlayTarget + ' .content-overlay').length) {
                    mQuery(overlayTarget).prepend(overlay);
                }
            }
        });

        if (mQuery(btn).length) {
            mQuery(btn).on('click', {'parent': mQuery(el).attr('id')}, function (event) {
                var searchStr = mQuery(el).val().trim();
                MauticVars[searchStrVar] = searchStr;

                Mautic.filterButtonClicked = true;
                Mautic.filterList(event,
                    event.data.parent,
                    mQuery('#' + event.data.parent).attr('data-action'),
                    mQuery('#' + event.data.parent).attr('data-target'),
                    'liveCache',
                    mQuery(this).attr('data-livesearch-action')
                );
            });

            if (mQuery(el).val()) {
                mQuery(btn).attr('data-livesearch-action', 'clear');
                mQuery(btn + ' i').removeClass('fa-search').addClass('fa-eraser');
            } else {
                mQuery(btn).attr('data-livesearch-action', 'search');
                mQuery(btn + ' i').removeClass('fa-eraser').addClass('fa-search');
            }
        }
    },

    /**
     * Filters list based on search contents
     */
    filterList: function (e, elId, route, target, liveCacheVar, action, overlayEnabled, overlayTarget) {
        if (typeof liveCacheVar == 'undefined') {
            liveCacheVar = "liveCache";
        }

        var el = mQuery('#' + elId);
        //only submit if the element exists, its a livesearch, or on button click

        if (el.length && (e.data.livesearch || mQuery(e.target).prop('tagName') == 'BUTTON' || mQuery(e.target).parent().prop('tagName') == 'BUTTON')) {
            var value = el.val().trim();
            //should the content be cleared?
            if (!value) {
                //force action since we have no content
                action = 'clear';
            } else if (action == 'clear') {
                el.val('');
                el.typeahead('val', '');
                value = '';
            }

            //make the request
            //@TODO reevaluate search caching as it seems to cause issues
            if (false && value && value in MauticVars[liveCacheVar]) {
                var response = {"newContent": MauticVars[liveCacheVar][value]};
                response.target = target;
                response.overlayEnabled = overlayEnabled;
                response.overlayTarget = overlayTarget;

                Mautic.processPageContent(response);
            } else {
                var searchName = el.attr('name');
                if (searchName == 'undefined') {
                    searchName = 'search';
                }

                if (typeof Mautic.liveSearchXhr !== 'undefined') {
                    //ensure current search request is aborted
                    Mautic['liveSearchXhr'].abort();
                }

                var btn = "button[data-livesearch-parent='" + elId + "']";
                if (mQuery(btn).length && !mQuery(btn).hasClass('btn-nospin') && !Mautic.filterButtonClicked) {
                    Mautic.startIconSpinOnEvent(btn);
                }

                var tmpl = mQuery('#' + elId).data('tmpl');
                if (!tmpl) {
                    tmpl = 'list';
                }

                var tmplParam = (route.indexOf('tmpl') == -1) ? '&tmpl=' + tmpl : '';

                // In a modal?
                var checkInModalTarget = (overlayTarget) ? overlayTarget : target;
                var modalParent        = mQuery(checkInModalTarget).closest('.modal');
                var inModal            = mQuery(modalParent).length > 0;

                if (inModal) {
                    var modalTarget = '#' + mQuery(modalParent).attr('id');
                    Mautic.startModalLoadingBar(modalTarget);
                }
                var showLoading = (inModal) ? false : true;

                Mautic.liveSearchXhr = mQuery.ajax({
                    showLoadingBar: showLoading,
                    url: route,
                    type: "GET",
                    data: searchName + "=" + encodeURIComponent(value) + tmplParam,
                    dataType: "json",
                    success: function (response) {
                        //cache the response
                        if (response.newContent) {
                            MauticVars[liveCacheVar][value] = response.newContent;
                        }
                        //note the target to be updated
                        response.target = target;
                        response.overlayEnabled = overlayEnabled;
                        response.overlayTarget = overlayTarget;

                        //update the buttons class and action
                        if (mQuery(btn).length) {
                            if (action == 'clear') {
                                mQuery(btn).attr('data-livesearch-action', 'search');
                                mQuery(btn).children('i').first().removeClass('fa-eraser').addClass('fa-search');
                            } else {
                                mQuery(btn).attr('data-livesearch-action', 'clear');
                                mQuery(btn).children('i').first().removeClass('fa-search').addClass('fa-eraser');
                            }
                        }

                        if (inModal) {
                            Mautic.processModalContent(response);
                            Mautic.stopModalLoadingBar(modalTarget);
                        } else {
                            Mautic.processPageContent(response);
                            Mautic.stopPageLoadingBar();
                        }
                    },
                    error: function (request, textStatus, errorThrown) {
                        Mautic.processAjaxError(request, textStatus, errorThrown);

                        //update the buttons class and action
                        if (mQuery(btn).length) {
                            if (action == 'clear') {
                                mQuery(btn).attr('data-livesearch-action', 'search');
                                mQuery(btn).children('i').first().removeClass('fa-eraser').addClass('fa-search');
                            } else {
                                mQuery(btn).attr('data-livesearch-action', 'clear');
                                mQuery(btn).children('i').first().removeClass('fa-search').addClass('fa-eraser');
                            }
                        }
                    },
                    complete: function() {
                        delete Mautic.liveSearchXhr;
                        delete Mautic.filterButtonClicked;
                    }
                });
            }
        }
    },

    /**
     * Filters a list based on select value
     *
     * @param el
     */
    activateListFilterSelect: function(el) {
        var filterName       = mQuery(el).attr('name');
        var isMultiple       = mQuery(el).attr('multiple') ? true : false;
        var prefixExceptions = mQuery(el).data('prefix-exceptions');

        if (isMultiple && prefixExceptions) {
            if (typeof Mautic.listFilterValues == 'undefined') {
                Mautic.listFilterValues = {};
            }

            // Store values for comparison on change
            Mautic.listFilterValues[filterName] = mQuery(el).val();
        }

        mQuery(el).on('change', function() {
            var filterVal = mQuery(this).val();
            if (filterVal == null) {
                filterVal = [];
            }

            if (prefixExceptions) {
                var limited = prefixExceptions.split(',');

                if (filterVal.length > 1) {
                    for (var i=0; i<filterVal.length; i++) {
                        if (mQuery.inArray(filterVal[i], Mautic.listFilterValues[filterName]) == -1) {
                            var newOption = mQuery(this).find('option[value="' + filterVal[i] + '"]');
                            var prefix    = mQuery(newOption).parent().data('prefix');

                            if (mQuery.inArray(prefix, limited) != -1) {
                                mQuery(newOption).siblings().prop('selected', false);

                                filterVal = mQuery(this).val();
                                mQuery(this).trigger('chosen:updated');
                            }
                        }
                    }
                }

                Mautic.listFilterValues[filterName] = filterVal;
            }

            var tmpl = mQuery(this).data('tmpl');
            if (!tmpl) {
                tmpl = 'list';
            }

            var filters   = (isMultiple) ? JSON.stringify(filterVal) : filterVal;
            var request   = window.location.pathname + '?tmpl=' + tmpl + '&' + filterName + '=' + filters;

            Mautic.loadContent(request, '', 'POST', mQuery(this).data('target'));
        });
    },

    /**
     * Removes a list option from a list generated by ListType
     * @param el
     */
    removeFormListOption: function (el) {
        var sortableDiv = mQuery(el).parents('div.sortable');
        var inputCount = mQuery(sortableDiv).parents('div.form-group').find('input.sortable-itemcount');
        var count = mQuery(inputCount).val();
        count--;
        mQuery(inputCount).val(count);
        mQuery(sortableDiv).remove();
    },

    /**
     * Toggles published status of an entity
     *
     * @param el
     * @param model
     * @param id
     */
    togglePublishStatus: function (event, el, model, id, extra, backdrop) {
        event.preventDefault();

        var wasPublished = mQuery(el).hasClass('fa-toggle-on');

        mQuery(el).removeClass('fa-toggle-on fa-toggle-off').addClass('fa-spin fa-spinner');

        //destroy tooltips so it can be regenerated
        mQuery(el).tooltip('destroy');
        //clear the lookup cache
        MauticVars.liveCache = new Array();

        if (backdrop) {
            Mautic.activateBackdrop();
        }

        if (extra) {
            extra = '&' + extra;
        }
        mQuery(el).tooltip('destroy');
        mQuery.ajax({
            url: mauticAjaxUrl,
            type: "POST",
            data: "action=togglePublishStatus&model=" + model + '&id=' + id + extra,
            dataType: "json",
            success: function (response) {
                if (response.reload) {
                    Mautic.redirectWithBackdrop(window.location);
                } else if (response.statusHtml) {
                    mQuery(el).replaceWith(response.statusHtml);
                    mQuery(el).tooltip({html: true, container: 'body'});
                }
            },
            error: function (request, textStatus, errorThrown) {
                var addClass = (wasPublished) ? 'fa-toggle-on' : 'fa-toggle-off';
                mQuery(el).removeClass('fa-spin fa-spinner').addClass(addClass);

                Mautic.processAjaxError(request, textStatus, errorThrown);
            }
        });
    },

    /**
     * Toggles the class for yes/no button groups
     * @param changedId
     */
    toggleYesNoButtonClass: function (changedId) {
        changedId = '#' + changedId;

        var isYesButton   = mQuery(changedId).parent().hasClass('btn-yes');
        var isExtraButton = mQuery(changedId).parent().hasClass('btn-extra');

        if (isExtraButton) {
            mQuery(changedId).parents('.btn-group').find('.btn').removeClass('btn-success btn-danger').addClass('btn-default');
        } else {
            //change the other
            var otherButton = isYesButton ? '.btn-no' : '.btn-yes';
            var otherLabel = mQuery(changedId).parent().parent().find(otherButton);

            if (mQuery(changedId).prop('checked')) {
                var thisRemove = 'btn-default',
                    otherAdd = 'btn-default';
                if (isYesButton) {
                    var thisAdd = 'btn-success',
                        otherRemove = 'btn-danger';
                } else {
                    var thisAdd = 'btn-danger',
                        otherRemove = 'btn-success';
                }
            } else {
                var thisAdd = 'btn-default';
                if (isYesButton) {
                    var thisAdd = 'btn-success',
                        otherRemove = 'btn-danger';
                } else {
                    var thisAdd = 'btn-danger',
                        otherRemove = 'btn-success';
                }
            }

            mQuery(changedId).parent().removeClass(thisRemove).addClass(thisAdd);
            mQuery(otherLabel).removeClass(otherRemove).addClass(otherAdd);
        }

        return true;
    },

    /**
     * Apply filter
     * @param list
     */
    setSearchFilter: function (el, searchId, string) {
        if (typeof searchId == 'undefined')
            searchId = '#list-search';
        else
            searchId = '#' + searchId;

        if (string || string === '') {
            var current = string;
        } else {
            var filter  = mQuery(el).val();
            var current = mQuery('#list-search').typeahead('val') + " " + filter;
        }

        //append the filter
        mQuery(searchId).typeahead('val', current);

        //submit search
        var e = mQuery.Event("keypress", {which: 13});
        e.data = {};
        e.data.livesearch = true;
        Mautic.filterList(
            e,
            'list-search',
            mQuery(searchId).attr('data-action'),
            mQuery(searchId).attr('data-target'),
            'liveCache'
        );
    },

    /**
     * Unlock an entity
     *
     * @param model
     * @param id
     */
    unlockEntity: function (model, id, parameter) {
        mQuery.ajax({
            url: mauticAjaxUrl,
            type: "POST",
            data: "action=unlockEntity&model=" + model + "&id=" + id + "&parameter=" + parameter,
            dataType: "json"
        });
    },

    /**
     * Processes ajax errors
     *
     *
     * @param request
     * @param textStatus
     * @param errorThrown
     */
    processAjaxError: function (request, textStatus, errorThrown, mainContent) {
        if (textStatus == 'abort') {
            Mautic.stopPageLoadingBar();
            Mautic.stopCanvasLoadingBar();
            Mautic.stopIconSpinPostEvent();
            return;
        }

        var inDevMode = typeof mauticEnv !== 'undefined' && mauticEnv == 'dev';

        if (inDevMode) {
            console.log(request);
        }

        if (typeof request.responseJSON !== 'undefined') {
            response = request.responseJSON;
        } else {
            //Symfony may have added some excess buffer if an exception was hit during a sub rendering and because
            //it uses ob_start, PHP dumps the buffer upon hitting the exception.  So let's filter that out.
            var errorStart = request.responseText.indexOf('{"newContent');
            var jsonString = request.responseText.slice(errorStart);

            if (jsonString) {
                try {
                    var response = mQuery.parseJSON(jsonString);
                    if (inDevMode) {
                        console.log(response);
                    }
                } catch (err) {
                    if (inDevMode) {
                        console.log(err);
                    }
                }
            } else {
                response = {};
            }
        }

        if (response) {
            if (response.newContent && mainContent) {
                //an error page was returned
                mQuery('#app-content .content-body').html(response.newContent);
                if (response.route && response.route.indexOf("ajax") == -1) {
                    //update URL in address bar
                    MauticVars.manualStateChange = false;
                    History.pushState(null, "Mautic", response.route);
                }
            } else if (response.newContent && mQuery('.modal.in').length) {
                //assume a modal was the recipient of the information
                mQuery('.modal.in .modal-body-content').html(response.newContent);
                mQuery('.modal.in .modal-body-content').removeClass('hide');
                if (mQuery('.modal.in  .loading-placeholder').length) {
                    mQuery('.modal.in  .loading-placeholder').addClass('hide');
                }
            } else if (inDevMode) {
                if (response.error) {
                    var error = response.error.code + ': ' + errorThrown + '; ' + response.error.exception;
                    alert(error);
                }
            }
        }

        Mautic.stopPageLoadingBar();
        Mautic.stopCanvasLoadingBar();
        Mautic.stopIconSpinPostEvent();
    },

    /**
     * Emulates empty data object if doughnut/pie chart data are empty.
     *
     *
     * @param data
     */
    emulateNoDataForPieChart: function (data) {
        var dataEmpty = true;
        mQuery.each(data, function (i, part) {
            if (part.value) {
                dataEmpty = false;
            }
        });
        if (dataEmpty) {
            data = [{
                value: 1,
                color: "#efeeec",
                highlight: "#EBEBEB",
                label: "No data"
            }];
        }
        return data;
    },

    /**
     * Moderates intervals to prevent ajax overlaps
     *
     * @param key
     * @param callback
     * @param timeout
     */
    setModeratedInterval: function (key, callback, timeout, params) {
        if (typeof MauticVars.intervalsInProgress[key] != 'undefined') {
            //action is still pending so clear and reschedule
            clearTimeout(MauticVars.moderatedIntervals[key]);
        } else {
            MauticVars.intervalsInProgress[key] = true;

            //perform callback
            if (typeof params == 'undefined') {
                params = [];
            }

            if (typeof callback == 'function') {
                callback(params);
            } else {
                window["Mautic"][callback].apply('window', params);
            }
        }

        //schedule new timeout
        MauticVars.moderatedIntervals[key] = setTimeout(function () {
            Mautic.setModeratedInterval(key, callback, timeout, params)
        }, timeout);
    },

    /**
     * Call at the end of the moderated interval callback function to let setModeratedInterval know
     * the action is done and it's safe to execute again
     *
     * @param key
     */
    moderatedIntervalCallbackIsComplete: function (key) {
        delete MauticVars.intervalsInProgress[key];
    },

    /**
     * Clears a moderated interval
     *
     * @param key
     */
    clearModeratedInterval: function (key) {
        Mautic.moderatedIntervalCallbackIsComplete(key);
        clearTimeout(MauticVars.moderatedIntervals[key]);
        delete MauticVars.moderatedIntervals[key];
    },

    /**
     * Sets flashes
     * @param flashes
     */
    setFlashes: function (flashes) {
        mQuery('#flashes').append(flashes);

        mQuery('#flashes .alert-new').each(function () {
            var me = this;
            window.setTimeout(function () {
                mQuery(me).fadeTo(500, 0).slideUp(500, function () {
                    mQuery(this).remove();
                });
            }, 4000);

            mQuery(this).removeClass('alert-new');
        });
    },

    /**
     * Set browser notifications
     *
     * @param notifications
     */
    setBrowserNotifications: function (notifications) {
        mQuery.each(notifications, function (key, notification) {
            Mautic.browserNotifier.createNotification(
                notification.title,
                {
                    body: notification.message,
                    icon: notification.icon
                }
            );
        });
    },

    /**
     *
     * @param notifications
     */
    setNotifications: function (notifications) {
        if (notifications.lastId) {
            mQuery('#mauticLastNotificationId').val(notifications.lastId);
        }

        if (mQuery('#notifications .mautic-update')) {
            mQuery('#notifications .mautic-update').remove();
        }

        if (notifications.hasNewNotifications) {
            if (mQuery('#newNotificationIndicator').hasClass('hide')) {
                mQuery('#newNotificationIndicator').removeClass('hide');
            }
        }

        if (notifications.content) {
            mQuery('#notifications').prepend(notifications.content);

            if (!mQuery('#notificationMautibot').hasClass('hide')) {
                mQuery('#notificationMautibot').addClass('hide');
            }
        }

        if (notifications.sound) {
            mQuery('.playSound').remove();

            mQuery.playSound(notifications.sound);
        }
    },

    /**
     * Marks notifications as read and clears unread indicators
     */
    showNotifications: function () {
        mQuery("#notificationsDropdown").unbind('hide.bs.dropdown');
        mQuery('#notificationsDropdown').on('hidden.bs.dropdown', function () {
            if (!mQuery('#newNotificationIndicator').hasClass('hide')) {
                mQuery('#notifications .is-unread').remove();
                mQuery('#newNotificationIndicator').addClass('hide');
            }
        });
    },

    /**
     * Clear notification(s)
     * @param id
     */
    clearNotification: function (id) {
        if (id) {
            mQuery("#notification" + id).fadeTo("fast", 0.01).slideUp("fast", function () {
                mQuery(this).find("*[data-toggle='tooltip']").tooltip('destroy');
                mQuery(this).remove();

                if (!mQuery('#notifications .notification').length) {
                    if (mQuery('#notificationMautibot').hasClass('hide')) {
                        mQuery('#notificationMautibot').removeClass('hide');
                    }
                }
            });
        } else {
            mQuery("#notifications .notification").fadeOut(300, function () {
                mQuery(this).remove();

                if (mQuery('#notificationMautibot').hasClass('hide')) {
                    mQuery('#notificationMautibot').removeClass('hide');
                }
            });
        }

        mQuery.ajax({
            url: mauticAjaxUrl,
            type: "GET",
            data: "action=clearNotification&id=" + id
        });
    },

    /**
     * Converts an input to a color picker
     * @param el
     */
    activateColorPicker: function(el) {
        var pickerOptions = mQuery(el).data('color-options');
        if (!pickerOptions) {
            pickerOptions = {
                theme: 'bootstrap',
                change: function (hex, opacity) {
                    mQuery(el).trigger('change.minicolors', hex);
                }
            };
        }

        mQuery(el).minicolors(pickerOptions);
    },

    /**
     * Activates typeahead
     * @param el
     * @param options
     * @returns {*}
     */
    activateTypeahead: function (el, options) {
        if (typeof options == 'undefined' || !mQuery(el).length) {
            return;
        }

        if (typeof options.remote == 'undefined') {
            options.remote = (options.action) ? true : false;
        }

        if (typeof options.prefetch == 'undefined') {
            options.prefetch = false;
        }

        if (typeof options.limit == 'undefined') {
            options.limit = 5;
        }

        if (!options.displayKey) {
            options.displayKey = 'value';
        }

        if (typeof options.multiple == 'undefined') {
            options.multiple = false;
        }

        if (typeof options.minLength == 'undefined') {
            options.minLength = 2;
        }

        if (options.prefetch || options.remote) {
            if (typeof options.action == 'undefined') {
                return;
            }

            var sourceOptions = {
                datumTokenizer: Bloodhound.tokenizers.obj.whitespace(options.displayKey),
                queryTokenizer: Bloodhound.tokenizers.whitespace,
                dupDetector: function (remoteMatch, localMatch) {
                    return (remoteMatch[options.displayKey] == localMatch[options.displayKey]);
                },
                ttl: 15000,
                limit: options.limit
            };

            var filterClosure = function (list) {
                if (typeof list.ignore_wdt != 'undefined') {
                    delete list.ignore_wdt;
                }

                if (typeof list.success != 'undefined') {
                    delete list.success;
                }

                if (typeof list == 'object') {
                    if (typeof list[0] != 'undefined') {
                        //meant to be an array and not an object
                        list = mQuery.map(list, function (el) {
                            return el;
                        });
                    } else {
                        //empty object so return empty array
                        list = [];
                    }
                }

                return list;
            };

            if (options.remote) {
                sourceOptions.remote = {
                    url: mauticAjaxUrl + "?action=" + options.action + "&filter=%QUERY",
                    filter: filterClosure
                };
            }

            if (options.prefetch) {
                sourceOptions.prefetch = {
                    url: mauticAjaxUrl + "?action=" + options.action,
                    filter: filterClosure
                };
            }

            var theBloodhound = new Bloodhound(sourceOptions);
            theBloodhound.initialize();
        } else {
            var substringMatcher = function (strs, strKeys) {
                return function findMatches(q, cb) {
                    var matches, substrRegex;

                    // an array that will be populated with substring matches
                    matches = [];

                    // regex used to determine if a string contains the substring `q`
                    substrRegex = new RegExp(q, 'i');

                    // iterate through the pool of strings and for any string that
                    // contains the substring `q`, add it to the `matches` array
                    mQuery.each(strs, function (i, str) {
                        if (typeof str == 'object') {
                            str = str[options.displayKey];
                        }

                        if (substrRegex.test(str)) {
                            // the typeahead jQuery plugin expects suggestions to a
                            // JavaScript object, refer to typeahead docs for more info
                            var match = {};

                            match[options.displayKey] = str;

                            if (strKeys.length && typeof strKeys[i] != 'undefined') {
                                match['id'] = strKeys[i];
                            }
                            matches.push(match);
                        }
                    });

                    cb(matches);
                };
            };

            var lookupOptions = (options.dataOptions) ? options.dataOptions : mQuery(el).data('options');
            var lookupKeys = (options.dataOptionKeys) ? options.dataOptionKeys : [];
            if (!lookupOptions) {
                return;
            }
        }

        var theName = el.replace(/[^a-z0-9\s]/gi, '').replace(/[-\s]/g, '_');

        var theTypeahead = mQuery(el).typeahead(
            {
                hint: true,
                highlight: true,
                minLength: options.minLength,
                multiple: options.multiple
            },
            {
                name: theName,
                displayKey: options.displayKey,
                source: (typeof theBloodhound != 'undefined') ? theBloodhound.ttAdapter() : substringMatcher(lookupOptions, lookupKeys)
            }
        ).on('keypress', function (event) {
                if ((event.keyCode || event.which) == 13) {
                    mQuery(el).typeahead('close');
                }
            }).on('focus', function() {
                if(mQuery(el).typeahead('val') === '' && !options.minLength) {
                    mQuery(el).data('ttTypeahead').input.trigger('queryChanged', '');
                }
            });

        return theTypeahead;
    },

    /**
     * Execute an action to AjaxController
     *
     * @param action
     * @param data
     * @param successClosure
     * @param showLoadingBar
     */
    ajaxActionRequest: function(action, data, successClosure, showLoadingBar) {
        if (typeof Mautic.ajaxActionXhr == 'undefined') {
            Mautic.ajaxActionXhr = {};
        } else if (typeof Mautic.ajaxActionXhr[action] != 'undefined') {
            Mautic.removeLabelLoadingIndicator();
            Mautic.ajaxActionXhr[action].abort();
        }

        if (typeof showLoadingBar == 'undefined') {
            showLoadingBar = false;
        }

        Mautic.ajaxActionXhr[action] = mQuery.ajax({
            url: mauticAjaxUrl + '?action=' + action,
            type: 'POST',
            data: data,
            showLoadingBar: showLoadingBar,
            success: function (response) {
                if (typeof successClosure == 'function') {
                    successClosure(response);
                }
            },
            error: function (request, textStatus, errorThrown) {
                Mautic.processAjaxError(request, textStatus, errorThrown, true);
            },
            complete: function () {
                delete Mautic.ajaxActionXhr[action];
            }
        });
    },

    /**
     * Helper function to timeframe based graphs
     *
     * @param element
     * @param action
     * @param query
     * @param callback
     */
    getChartData: function(element, action, query, callback) {
        var element = mQuery(element);
        var wrapper = element.closest('ul');
        var button  = mQuery('#time-scopes .button-label');
        wrapper.find('a').removeClass('bg-primary');
        element.addClass('bg-primary');
        button.text(element.text());

        // Append action
        query = query + '&action=' + action;

        mQuery.ajax({
            showLoadingBar: true,
            url: mauticAjaxUrl,
            type: 'POST',
            data: query,
            dataType: "json",
            success: function (response) {
                if (response.success) {
                    Mautic.stopPageLoadingBar();
                    if (typeof callback == 'function') {
                        callback(response);
                    } else if(typeof window["Mautic"][callback] !== 'undefined') {
                        window["Mautic"][callback].apply('window', [response]);
                    }
                }
            },
            error: function (request, textStatus, errorThrown) {
                Mautic.processAjaxError(request, textStatus, errorThrown);
            }
        });
    },

    /**
     * Get entity ID of pages that have an input with id of entityId
     *
     * @returns {*}
     */
    getEntityId: function() {
        return (mQuery('input#entityId').length) ? mQuery('input#entityId').val() : 0;
    },

    /**
     * Close the given modal and redirect to a URL
     *
     * @param el
     * @param url
     */
    closeModalAndRedirect: function(el, url) {
        Mautic.startModalLoadingBar(el);

        Mautic.loadContent(url);

        mQuery('body').removeClass('noscroll');
    },

    /**
     * Render the chart.js charts
     *
     * @param mQuery|string scope
     */
    renderCharts: function(scope) {
        var charts = [];
        if (!Mautic.chartObjects) Mautic.chartObjects = [];

        if (mQuery.type(scope) === 'string') {
            charts = mQuery(scope).find('canvas.chart');
        } else if (scope) {
            charts = scope.find('canvas.chart');
        } else {
            charts = mQuery('canvas.chart');
        }

        if (charts.length) {
            charts.each(function(index, canvas) {
                canvas = mQuery(canvas);
                if (!canvas.hasClass('chart-rendered')) {
                    if (canvas.hasClass('line-chart')) {
                        Mautic.renderLineChart(canvas)
                    } else if (canvas.hasClass('pie-chart')) {
                        Mautic.renderPieChart(canvas)
                    } else if (canvas.hasClass('bar-chart')) {
                        Mautic.renderBarChart(canvas)
                    } else if (canvas.hasClass('liefechart-bar-chart')) {
                        Mautic.renderLifechartBarChart(canvas)
                    } else if (canvas.hasClass('simple-bar-chart')) {
                        Mautic.renderSimpleBarChart(canvas)
                    } else if (canvas.hasClass('horizontal-bar-chart')) {
                        Mautic.renderHorizontalBarChart(canvas)
                    }
                }
                canvas.addClass('chart-rendered');
            });
        }
    },

    /**
     * Render the chart.js line chart
     *
     * @param mQuery element canvas
     */
    renderLineChart: function(canvas) {
        var data = mQuery.parseJSON(canvas.text());
        if (!data.labels.length || !data.datasets.length) return;
        var chart = new Chart(canvas, {
            type: 'line',
            data: data,
            options: {
                lineTension : 0.2,
                borderWidth: 1,
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero: true
                        }
                    }]
                }
            }
        });
        Mautic.chartObjects.push(chart);
    },

    /**
     * Render the chart.js pie chart
     *
     * @param mQuery element canvas
     */
    renderPieChart: function(canvas) {
        var data = mQuery.parseJSON(canvas.text());
        var options = {borderWidth: 1};
        var disableLegend = canvas.attr('data-disable-legend');
        if (typeof disableLegend !== 'undefined' && disableLegend !== false) {
            options.legend = {
                display: false
            }
        }
        // data = Mautic.emulateNoDataForPieChart(data);
        var chart = new Chart(canvas, {
            type: 'pie',
            data: data,
            options: options
        });
        Mautic.chartObjects.push(chart);
    },

    /**
     * Render the chart.js bar chart
     *
     * @param mQuery element canvas
     */
    renderBarChart: function(canvas) {
        var data = mQuery.parseJSON(canvas.text());
        var chart = new Chart(canvas, {
            type: 'bar',
            data: data,
            options: {
                scales: {
                    xAxes: [{
                        barPercentage: 0.9,
                    }]
                }
            }
        });
        Mautic.chartObjects.push(chart);
    },
    /**
     * Render the chart.js bar chart
     *
     * @param mQuery element canvas
     */
    renderLifechartBarChart: function(canvas) {
        var canvasWidth = mQuery(canvas).parent().width();
        var barWidth    = (canvasWidth < 300) ? 5 : 25;
        var data = mQuery.parseJSON(canvas.text());
        var chart = new Chart(canvas, {
            type: 'bar',
            data: data,
            options: {
                scales: {
                    xAxes: [
                        {
                            barThickness: barWidth,
                        }
                    ]
                }
            }
        });
        Mautic.chartObjects.push(chart);
    },

    /**
     * Render the chart.js simple bar chart
     *
     * @param mQuery element canvas
     */
    renderSimpleBarChart: function(canvas) {
        var data = mQuery.parseJSON(canvas.text());
        var chart = new Chart(canvas, {
            type: 'bar',
            data: data,
            options: {
                scales: {
                    xAxes: [{
                        stacked: false,
                        ticks: {fontSize: 9},
                        gridLines: {display:false},
                    }],
                    yAxes: [{
                        display: false,
                        stacked: false,
                        ticks: {beginAtZero: true, display: false},
                        gridLines: {display:false}
                    }],
                    display: false
                },
                legend: {
                    display: false
                }
            }
        });
        Mautic.chartObjects.push(chart);
    },

    /**
     * Render the chart.js simple bar chart
     *
     * @param mQuery element canvas
     */
    renderHorizontalBarChart: function(canvas) {
        var data = mQuery.parseJSON(canvas.text());
        var chart = new Chart(canvas, {
            type: 'horizontalBar',
            data: data,
            options: {
                scales: {
                    xAxes: [{
                        display: true,
                        stacked: false,
                        gridLines: {display:false},
                        ticks: {beginAtZero: true,display: true, fontSize: 8, stepSize: 5}
                    }],
                    yAxes: [{
                        stacked: false,
                        ticks: {beginAtZero: true, display: true, fontSize: 9},
                        gridLines: {display:false},
                        barPercentage: 0.5,
                        categorySpacing: 1
                    }],
                    display: false
                },
                legend: {
                    display: false
                },
                tooltips: {
                    mode: 'single',
                    bodyFontSize: 9,
                    bodySpacing: 0,
                    callbacks: {
                        title: function(tooltipItems, data) {
                            // Title doesn't make sense for scatter since we format the data as a point
                            return '';
                        },
                        label: function(tooltipItem, data) {
                            return  tooltipItem.xLabel + ': ' + tooltipItem.yLabel;
                        }
                    }

                }
            }
        });
        Mautic.chartObjects.push(chart);
    },

    /**
     * Render vector maps
     *
     * @param mQuery element scope
     */
    renderMaps: function(scope) {
        var maps = [];

        if (mQuery.type(scope) === 'string') {
            maps = mQuery(scope).find('.vector-map');
        } else if (scope) {
            maps = scope.find('.vector-map');
        } else {
            maps = mQuery('.vector-map');
        }

        if (maps.length) {
            maps.each(function(index, element) {
                Mautic.renderMap(mQuery(element));
            });
        }
    },

    /**
     *
     * @param wrapper
     * @returns {*}
     */
    renderMap: function(wrapper) {
        // Map render causes a JS error on FF when the element is hidden
        if (wrapper.is(':visible')) {
            if (!Mautic.mapObjects) Mautic.mapObjects = [];
            var data = wrapper.data('map-data');
            if (typeof data === 'undefined' || !data.length) {
                try {
                    data = mQuery.parseJSON(wrapper.text());
                    wrapper.data('map-data', data);
                } catch (error) {

                    return;
                }
            }

            // Markers have numerical indexes
            var firstKey = Object.keys(data)[0];

            // Check type of data
            if (firstKey == "0") {
                // Markers
                var markersData = data,
                    regionsData = {};
            } else {
                // Regions
                var markersData = {},
                    regionsData = data;
            }

            wrapper.text('');
            wrapper.vectorMap({
                backgroundColor: 'transparent',
                zoomOnScroll: false,
                markers: markersData,
                markerStyle: {
                    initial: {
                        fill: '#40C7B5'
                    },
                    selected: {
                        fill: '#40C7B5'
                    }
                },
                regionStyle: {
                    initial: {
                        "fill": '#dce0e5',
                        "fill-opacity": 1,
                        "stroke": 'none',
                        "stroke-width": 0,
                        "stroke-opacity": 1
                    },
                    hover: {
                        "fill-opacity": 0.7,
                        "cursor": 'pointer'
                    }
                },
                map: 'world_mill_en',
                series: {
                    regions: [{
                        values: regionsData,
                        scale: ['#dce0e5', '#40C7B5'],
                        normalizeFunction: 'polynomial'
                    }]
                },
                onRegionTipShow: function (event, label, index) {
                    if (data[index] > 0) {
                        label.html(
                            '<b>'+label.html()+'</b></br>'+
                            data[index]+' Leads'
                        );
                    }
                }
            });
            wrapper.addClass('map-rendered');
            Mautic.mapObjects.push(wrapper);
            return wrapper;
        }
    },

    /**
     * Destroy a jVector map
     */
    destroyMap: function(wrapper) {
        if (wrapper.hasClass('map-rendered')) {
            var map = wrapper.vectorMap('get', 'mapObject');
            map.removeAllMarkers();
            map.remove();
            wrapper.empty();
            wrapper.removeClass('map-rendered');
        }
    },

    /**
     * Initialize graph date range selectors
     */
    initDateRangePicker: function (fromId, toId) {
        var dateFrom = mQuery(fromId);
        var dateTo = mQuery(toId);

        if (dateFrom.length && dateTo.length) {
            dateFrom.datetimepicker({
                format: 'M j, Y',
                onShow: function (ct) {
                    this.setOptions({
                        maxDate: dateTo.val() ? new Date(dateTo.val()) : false
                    });
                },
                timepicker: false
            });

            dateTo.datetimepicker({
                format: 'M j, Y',
                onShow: function (ct) {
                    this.setOptions({
                        maxDate: new Date(),
                        minDate: dateFrom.val() ? new Date(dateFrom.val()) : false
                    });
                },
                timepicker: false
            });
        }
    },

    /**
     * Initialize theme selection
     *
     * @param themeField
     */
    intiSelectTheme: function(themeField) {
        var customHtml = mQuery('textarea.builder-html');
        var isNew = Mautic.isNewEntity('#page_sessionId, #emailform_sessionId');
        Mautic.showChangeThemeWarning = true;
        Mautic.builderTheme = themeField.val();

        if (isNew) {
            Mautic.showChangeThemeWarning = false;

            // Populate default content
            if (!customHtml.length || !customHtml.val().length) {
                Mautic.setThemeHtml(Mautic.builderTheme);
            }
        }

        if (customHtml.length) {
            mQuery('[data-theme]').click(function(e) {
                e.preventDefault();
                var currentLink = mQuery(this);
                var theme = currentLink.attr('data-theme');
                var isCodeMode = (theme === 'mautic_code_mode');
                Mautic.builderTheme = theme;

                if (Mautic.showChangeThemeWarning && customHtml.val().length) {
                    if (!isCodeMode) {
                        if (confirm(Mautic.translate('mautic.core.builder.theme_change_warning'))) {
                            customHtml.val('');
                            Mautic.showChangeThemeWarning = false;
                        } else {
                            return;
                        }
                    } else {
                        if (confirm(Mautic.translate('mautic.core.builder.code_mode_warning'))) {
                        } else {
                            return;
                        }
                    }
                }

                // Set the theme field value
                themeField.val(theme);

                // Code Mode
                if (isCodeMode) {
                    mQuery('.builder').addClass('code-mode');
                    mQuery('.builder .code-editor').removeClass('hide');
                    mQuery('.builder .code-mode-toolbar').removeClass('hide');
                    mQuery('.builder .builder-toolbar').addClass('hide');
                } else {
                    mQuery('.builder').removeClass('code-mode');
                    mQuery('.builder .code-editor').addClass('hide');
                    mQuery('.builder .code-mode-toolbar').addClass('hide');
                    mQuery('.builder .builder-toolbar').removeClass('hide');

                    // Load the theme HTML to the source textarea
                    Mautic.setThemeHtml(theme);
                }

                // Manipulate classes to achieve the theme selection illusion
                mQuery('.theme-list .panel').removeClass('theme-selected');
                currentLink.closest('.panel').addClass('theme-selected');
                mQuery('.theme-list .select-theme-selected').addClass('hide');
                mQuery('.theme-list .select-theme-link').removeClass('hide');
                currentLink.closest('.panel').find('.select-theme-selected').removeClass('hide');
                currentLink.addClass('hide');
            });
        }
    },

    /**
     * Set theme's HTML
     *
     * @param theme
     */
    setThemeHtml: function(theme) {
        mQuery.get(mQuery('#builder_url').val()+'?template=' + theme, function(themeHtml) {
            var textarea = mQuery('textarea.builder-html');
            textarea.val(themeHtml);
        });
    },

    /**
     * Updates content of an iframe
     *
     * @param iframe ID
     * @param HTML content
     */
    updateIframeContent: function(iframeId, content) {
        var iframe = document.getElementById(iframeId);
        var doc = iframe.contentDocument || iframe.contentWindow.document;
        doc.open();
        doc.write(content);
        doc.close();
    },

    /**
     * Initialize form field visibility switcher
     *
     * @param formName
     */
    initializeFormFieldVisibilitySwitcher: function (formName)
    {
        Mautic.switchFormFieldVisibilty(formName);

        mQuery('form[name="'+formName+'"]').change(function() {
            Mautic.switchFormFieldVisibilty(formName);
        });
    },

    /**
     * Switch form field visibility based on selected values
     */
    switchFormFieldVisibilty: function (formName) {
        var form   = mQuery('form[name="'+formName+'"]');
        var fields = {};
        var fieldsPriority = {};

        var getFieldParts = function(fieldName) {
            var returnObject = {"name": fieldName, "attribute": ''};
            if (fieldName.search(':') !== -1) {
                var returnArray = fieldName.split(':');
                returnObject.name = returnArray[0];
                returnObject.attribute = returnArray[1];
            }

            return returnObject;
        };

        var checkValueCondition = function (sourceFieldVal, condition) {
            var visible = true;
            if (typeof condition == 'object') {
                visible = mQuery.inArray(sourceFieldVal, condition) !== -1;
            } else if (condition == 'empty' || (condition == 'notEmpty')) {
                var isEmpty = (sourceFieldVal == '' || sourceFieldVal == null || sourceFieldVal == 'undefined');
                visible = (condition == 'empty') ? isEmpty : !isEmpty;
            } else if (condition !== sourceFieldVal) {
                visible = false;
            }

            return visible;
        };

        var checkFieldCondition = function (fieldId, attribute, condition) {
            var visible = true;

            if (attribute) {
                // Compare the attribute value
                if (typeof mQuery('#' + fieldId).attr(attribute) !== 'undefined') {
                    var field = '#' + fieldId;
                } else if (mQuery('#' + fieldId).is('select')) {
                    // Check the value option
                    var field = mQuery('#' + fieldId +' option[value="' + mQuery('#' + fieldId).val() + '"]');
                } else {
                    return visible;
                }

                var attributeValue = (typeof mQuery(field).attr(attribute) !== 'undefined') ? mQuery(field).attr(attribute) : null;

                return checkValueCondition(attributeValue, condition);
            } else if (mQuery('#' + fieldId).is(':checkbox') || mQuery('#' + fieldId).is(':radio')) {
                return (condition == 'checked' && mQuery('#' + fieldId).is(':checked')) || (condition == '' && !mQuery('#' + fieldId).is(':checked'));
            }

            return checkValueCondition(mQuery('#' + fieldId).val(), condition);
        }

        // find all fields to show
        form.find('[data-show-on]').each(function(index, el) {
            var field = mQuery(el);
            var showOn = jQuery.parseJSON(field.attr('data-show-on'));

            mQuery.each(showOn, function(fieldId, condition) {
                var fieldParts = getFieldParts(fieldId);

                // Treat multiple fields as OR statements
                if (typeof fields[field.attr('id')] === 'undefined' || !fields[field.attr('id')]) {
                    fields[field.attr('id')] = checkFieldCondition(fieldParts.name, fieldParts.attribute, condition);
                }
            });
        });

        // find all fields to hide
        form.find('[data-hide-on]').each(function(index, el) {
            var field  = mQuery(el);
            var hideOn = jQuery.parseJSON(field.attr('data-hide-on'));

            if (typeof hideOn.display_priority !== 'undefined') {
                fieldsPriority[field.attr('id')] = 'hide';
                delete hideOn.display_priority;
            }

            mQuery.each(hideOn, function(fieldId, condition) {
                var fieldParts = getFieldParts(fieldId);

                // Treat multiple fields as OR statements
                if (typeof fields[field.attr('id')] === 'undefined' || fields[field.attr('id')]) {
                    fields[field.attr('id')] = !checkFieldCondition(fieldParts.name, fieldParts.attribute, condition);
                }
            });
        });

        // show/hide according to conditions
        mQuery.each(fields, function(fieldId, show) {
            var fieldContainer = mQuery('#' + fieldId).closest('[class*="col-"]');;
            if (show) {
                fieldContainer.fadeIn();
            } else {
                fieldContainer.fadeOut();
            }
        });
    },


    /**
     * Check if the the entity ID is temporary (for new entities)
     *
     * @param string idInputSelector
     */
    isNewEntity: function(idInputSelector) {
        id = mQuery(idInputSelector);
        if (id.length) {
            return id.val().match("^new_");
        }
        return null;
    }
};

// Show overflow in the App Wrapper when a Chosen dropdown is shown

mQuery(document).on({
    // The order in which the handlers are registered matters
    "chosen:hiding_dropdown": function() {
        mQuery('#app-wrapper').css('overflow', 'hidden');
    },
    "chosen:showing_dropdown": function() {
        mQuery('#app-wrapper').css('overflow', 'visible');
    }
});
