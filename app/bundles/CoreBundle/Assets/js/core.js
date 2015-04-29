var MauticVars  = {};
var mQuery      = jQuery.noConflict(true);
window.jQuery   = mQuery;

//set default ajax options
MauticVars.activeRequests = 0;

mQuery.ajaxSetup({
    beforeSend: function (request, settings) {
        if (settings.showLoadingBar) {
            mQuery('.loading-bar').addClass('active');
            MauticVars.activeRequests++;

            var currentRequests = MauticVars.activeRequests;
            MauticVars.loadingBarTimeout = setTimeout(function() {
                if (MauticVars.activeRequests == currentRequests) {
                    // Seems to be stuck
                    MauticVars.activeRequests = 0;
                    Mautic.stopPageLoadingBar();
                }
            }, 20000);
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

mQuery( document ).ready(function() {
    if (typeof mauticContent !== 'undefined') {
        mQuery("html").Core({
            console: false
        });
    }

    if (typeof IdleTimer != 'undefined') {
        IdleTimer.init({
            idleTimeout: 900000, //15 minutes = 900000
            awayTimeout: 18000000, //30 minutes = 18000000
            statusChangeUrl: mauticAjaxUrl + '?action=updateUserStatus'
        });
    }
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
    Chart.defaults.global.responsive = true;
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
    /**
     * Binds global keyboard shortcuts
     */
    bindGlobalKeyboardShortcuts: function () {
        Mousetrap.bind('shift+d', function (e) {
            mQuery('#mautic_dashboard_index').click();
        });

        Mousetrap.bind('shift+l', function(e) {
            mQuery('#menu_lead_parent_child > li:first > a').click();
        });

        Mousetrap.bind('shift+right', function (e) {
            mQuery('.navbar-right > button.navbar-toggle').click();
        });

        Mousetrap.bind('shift+n', function (e) {
            mQuery('.dropdown-notification').click();
        });

        Mousetrap.bind('shift+s', function (e) {
            mQuery('#globalSearchContainer .search-button').click();
        });
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
        mQuery(Mautic.labelSpinner).remove();;
    },

    /**
     * Initiate various functions on page load, manual or ajax
     */
    onPageLoad: function (container, response, inModal) {
        //initiate links
        mQuery(container + " a[data-toggle='ajax']").off('click.ajax');
        mQuery(container + " a[data-toggle='ajax']").on('click.ajax', function (event) {
            event.preventDefault();

            return Mautic.ajaxifyLink(this, event);
        });

        mQuery(".sidebar-left a[data-toggle='ajax']").on('click.ajax', function (event) {
            mQuery("html").removeClass('sidebar-open-ltr');
        });
        mQuery('.sidebar-right a[data-toggle="ajax"]').on('click.ajax', function (event) {
            mQuery("html").removeClass('sidebar-open-rtl');
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

        mQuery(container + " *[data-toggle='livesearch']").each(function (index) {
            Mautic.activateLiveSearch(mQuery(this), "lastSearchStr", "liveCache");
        });

        //initialize tooltips
        mQuery(container + " *[data-toggle='tooltip']").tooltip({html: true, container: 'body'});

        //initialize sortable lists
        mQuery(container + " *[data-toggle='sortablelist']").each(function (index) {
            var prefix = mQuery(this).attr('data-prefix');

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
                stop: function (i) {
                    var order = 0;
                    mQuery('#' + prefix + '_list div.list-sortable div.input-group input').each(function () {
                        var name = mQuery(this).attr('name');
                        name = name.replace(/\[list\]\[(.+)\]$/g, '') + '[list][' + order + ']';
                        mQuery(this).attr('name', name);
                        order++;
                    });
                }
            });
        });

        //downloads
        mQuery(container + " a[data-toggle='download']").off('click.download');
        mQuery(container + " a[data-toggle='download']").on('click.download', function (event) {
            event.preventDefault();

            var link = mQuery(event.target).attr('href');

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

        mQuery(container + " input[data-toggle='color']").pickAColor({
            fadeMenuToggle: false,
            inlineDropdown: true
        });

        mQuery(container + " select").not('.multiselect, .not-chosen').each(function() {
            Mautic.activateChosenSelect(this);
        });

        mQuery(container + " select.multiselect").each(function() {
            Mautic.activateMultiSelect(this);
        });

        //spin icons on button click
        mQuery(container + ' .btn:not(.btn-nospin)').on('click.spinningicons', function (event) {
            Mautic.startIconSpinOnEvent(event);
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
                    mQuery(container + ' .toolbar-form-buttons .hidden-xs').html('');
                    mQuery(container + ' .toolbar-form-buttons .hidden-md .drop-menu').html('');

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
                            .attr('id', mQuery(this).attr('id') + '_toolbar')
                            .html(mQuery(this).html())
                            .on('click.ajaxform', buttonClick)
                            .appendTo('.toolbar-form-buttons .hidden-sm');

                        if (i === lastIndex) {
                            mQuery(".toolbar-form-buttons .hidden-md .btn-main")
                                .off('.ajaxform')
                                .attr('id', mQuery(this).attr('id') + '_toolbar_mobile')
                                .html(mQuery(this).html())
                                .on('click.ajaxform', buttonClick);
                        } else {
                            mQuery("<a />")
                                .attr('id', mQuery(this).attr('id') + '_toolbar_mobile')
                                .html(mQuery(this).html())
                                .on('click.ajaxform', buttonClick)
                                .appendTo(mQuery('<li />').prependTo('.toolbar-form-buttons .hidden-md .dropdown-menu'))
                        }

                    });
                    mQuery('.toolbar-form-buttons').removeClass('hide');
                }
            }
        }

        //activate editors
        mQuery.each(['editor', 'editor-basic', 'editor-advanced', 'editor-advanced-2rows', 'editor-fullpage'], function (index, editorClass) {
            if (mQuery(container + ' textarea.' + editorClass).length) {
                mQuery(container + ' textarea.' + editorClass).each(function () {
                    var settings = {};

                    if (editorClass != 'editor') {
                        // Set the custom editor toolbar
                        var toolbar = editorClass.replace('editor-', '').replace('-', '_');
                        settings.toolbar = toolbar;
                    }

                    if (editorClass != 'editor' && editorClass != 'editor-basic') {
                        // Do not strip classes and the like
                        settings.allowedContent = true;
                    }

                    if (editorClass == 'editor-fullpage') {
                        // Allow full page editing and add tools to update html document
                        settings.fullPage     = true;
                        settings.extraPlugins = "sourcedialog,docprops";
                    }

                    mQuery(this).ckeditor(settings);
                });
            }
        });

        //activate shuffles
        if (mQuery('.shuffle-grid').length) {
            var grid = mQuery(".shuffle-grid");

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
        if (mQuery('.dropdown-menu-form').length) {
            mQuery('.dropdown-menu-form').on('click', function (e) {
                e.stopPropagation();
            });
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

            Mautic.setupBrowserNotifier();
        }

        if (contentSpecific && typeof Mautic[contentSpecific + "OnLoad"] == 'function') {
            Mautic[contentSpecific + "OnLoad"](container, response);
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

        //instantiate sparkline plugin
        mQuery('.plugin-sparkline').sparkline('html', {enableTagOptions: true});

        Mautic.stopIconSpinPostEvent();

        //stop loading bar
        if (container == '#app-content' || container == '.page-list') {
            Mautic.stopPageLoadingBar();
        }
    },

    /**
     * Convert to chosen select
     *
     * @param el
     */
    activateChosenSelect: function(el) {
        mQuery(el).chosen({
            placeholder_text_multiple: mauticLang['chosenChooseMore'],
            placeholder_text_single: mauticLang['chosenChooseOne'],
            no_results_text: mauticLang['chosenNoResults'],
            width: "100%",
            allow_single_deselect: true
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
     * Activate containers datetime inputs
     * @param container
     */
    activateDateTimeInputs: function(el, type) {
        if (typeof type == 'undefined') {
            type = 'datetime';
        }

        if (type == 'datetime') {
            mQuery(el).datetimepicker({
                format: 'Y-m-d H:i',
                lazyInit: true,
                validateOnBlur: false,
                allowBlank: true,
                scrollInput: false
            });
        } else if(type == 'date') {
            mQuery(el).datetimepicker({
                timepicker: false,
                format: 'Y-m-d',
                lazyInit: true,
                validateOnBlur: false,
                allowBlank: true,
                scrollInput: false,
                closeOnDateSelect: true
            });
        } else if (type == 'time') {
            mQuery(el).datetimepicker({
                datepicker: false,
                format: 'H:i',
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

            mQuery.each(['editor', 'editor-basic', 'editor-advanced', 'editor-advanced-2rows', 'editor-fullpage'], function (index, editorClass) {
                mQuery(container + ' textarea.' + editorClass).each(function () {
                    for (var name in CKEDITOR.instances) {
                        var instance = CKEDITOR.instances[name];
                        if (this && this == instance.element.$) {
                            instance.destroy(true);
                        }
                    }
                });
            });

            //turn off shuffle events
            mQuery('html')
                .off('fa.sidebar.minimize')
                .off('fa.sidebar.maximize');
        }

        //run specific unloads
        var contentSpecific = false;
        if (container == '#app-content') {
            //full page gets precedence
            Mousetrap.reset();

            contentSpecific = mauticContent;
        } else if (response && response.mauticContent) {
            contentSpecific = response.mauticContent;
        }

        if (contentSpecific && typeof Mautic[contentSpecific + "OnUnload"] == 'function') {
            Mautic[contentSpecific + "OnUnload"](container, response);
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
     */
    loadContent: function (route, link, method, target, showPageLoading, callback) {
        var xhrVar = (target) ? target : 'none';
        if (typeof Mautic.loadContentXhr == 'undefined') {
            Mautic.loadContentXhr = {};
        } else if (typeof Mautic.loadContentXhr[target] != 'undefined') {
            Mautic.loadContentXhr[target].abort();
        }

        Mautic.loadContentXhr[target] = mQuery.ajax({
            showLoadingBar: (typeof showPageLoading == 'undefined' || showPageLoading) ? true : false,
            url: route,
            type: method,
            dataType: "json",
            success: function (response) {
                if (response) {
                    if (response.callback) {
                        window["Mautic"][response.callback].apply('window', [response]);
                        return;
                    }
                    if (response.redirect) {
                        mQuery('<div />', {
                            'class': "modal-backdrop fade in"
                        }).appendTo('body');
                        setTimeout(function() {
                            window.location = response.redirect;
                        }, 50);
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
                delete Mautic.loadContentXhr[target];
            }
        });

        //prevent firing of href link
        //mQuery(link).attr("href", "javascript: void(0)");
        return false;
    },

    /**
     * Inserts a new javascript file request into the document head
     *
     * @param url
     */
    loadScript: function (url) {
        // check if the asset has been loaded
        if (typeof Mautic.headLoadedAssets == 'undefined') {
            Mautic.headLoadedAssets = {};
        } else if (typeof Mautic.headLoadedAssets[url] != 'undefined') {
            // URL has already been appended to head
            return;
        }

        // Note that asset has been appended
        Mautic.headLoadedAssets[url] = 1;

        var s = document.createElement('script');
        s.type = 'text/javascript';
        s.async = true;
        s.src = url;
        mQuery('head').append(s);
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
     * @param event
     */
    startIconSpinOnEvent: function (event) {
        if (MauticVars.ignoreIconSpin) {
            MauticVars.ignoreIconSpin = false;
            return;
        }

        if (event && typeof(event.target) !== 'undefined' && mQuery(event.target).length) {
            var hasBtn = mQuery(event.target).hasClass('btn');
            var hasIcon = mQuery(event.target).hasClass('fa');

            var i = (hasBtn && mQuery(event.target).find('i.fa').length) ? mQuery(event.target).find('i.fa') : event.target;

            if ((hasBtn && mQuery(event.target).find('i.fa').length) || hasIcon) {
                var el = (hasIcon) ? event.target : mQuery(event.target).find('i.fa').first();
                var identifierClass = (new Date).getTime();
                MauticVars.iconClasses[identifierClass] = mQuery(el).attr('class');

                var specialClasses = ['fa-fw', 'fa-lg', 'fa-2x', 'fa-3x', 'fa-4x', 'fa-5x', 'fa-li'];
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
     * Posts a form and returns the output.
     * Uses jQuery form plugin so it handles files as well.
     *
     * @param form
     * @param callback
     */
    postForm: function (form, callback) {
        var form = mQuery(form);

        var modalParent = form.closest('.modal');
        var inMain = modalParent.length > 0 ? false : true;

        var action = form.attr('action');

        if (!inMain) {
            Mautic.startModalLoadingBar();
        }
        var showLoading = (!inMain || form.attr('data-hide-loadingbar')) ? false : true;

        form.ajaxSubmit({
            showLoadingBar: showLoading,
            success: function (data) {
                if (data.redirect) {
                    mQuery('<div />', {
                        'class': "modal-backdrop fade in"
                    }).appendTo('body');
                    setTimeout(function() {
                        window.location = data.redirect;
                    }, 50);
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
        //prevent enter submitting form and instead jump to next line
        mQuery('form[name="' + formName + '"] input').off('keydown.ajaxform');
        mQuery('form[name="' + formName + '"] input').on('keydown.ajaxform', function (e) {
            if (e.keyCode == 13) {
                var inputs = mQuery(this).parents("form").eq(0).find(":input");
                if (inputs[inputs.index(this) + 1] != null) {
                    inputs[inputs.index(this) + 1].focus();
                }
                e.preventDefault();
                return false;
            }
        });

        //activate the submit buttons so symfony knows which were clicked
        mQuery('form[name="' + formName + '"] :submit').each(function () {
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
        mQuery('form[name="' + formName + '"]').off('submit.ajaxform');
        mQuery('form[name="' + formName + '"]').on('submit.ajaxform', (function (e) {
            e.preventDefault();

            if (MauticVars.formSubmitInProgress) {
                return false;
            } else {
                MauticVars.formSubmitInProgress = true;
            }

            Mautic.postForm(mQuery(this), function (response) {
                if (response.inMain) {
                    Mautic.processPageContent(response);
                } else {
                    Mautic.processModalContent(response, '#' + response.modalId);
                }
            });

            return false;
        }));
    },

    /**
     * Retrieves content of href via ajax
     * @param el
     * @param event
     * @returns {boolean}
     */
    ajaxifyLink: function (el, event) {
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
     * @returns {boolean}
     */
    ajaxifyModal: function (el, event) {
        var target = mQuery(el).attr('data-target');

        var route = mQuery(el).attr('href');
        if (route.indexOf('javascript') >= 0) {
            return false;
        }

        var method = mQuery(el).attr('data-method');
        if (!method) {
            method = 'GET'
        }

        var header = mQuery(el).attr('data-header');

        Mautic.loadAjaxModal(target, route, method, header);
    },

    /**
     * Retrieve ajax content for modal
     * @param target
     * @param route
     * @param method
     * @param header
     */
    loadAjaxModal: function (target, route, method, header) {

        //show the modal
        if (mQuery(target + ' .loading-placeholder').length) {
            mQuery(target + ' .loading-placeholder').removeClass('hide');
            mQuery(target + ' .modal-body-content').addClass('hide');

            if (mQuery(target + ' .modal-loading-bar').length) {
                mQuery(target + ' .modal-loading-bar').addClass('active');
            }
        }

        //move the modal to the body tag to get around positioned div issues
        mQuery(target).on('show.bs.modal', function () {
            if (header) {
                mQuery(target + " .modal-title").html(header);
            }
        });

        //clean slate upon close
        mQuery(target).on('hidden.bs.modal', function () {
            //unload
            Mautic.onPageUnload(target);

            Mautic.resetModal(target);

            if (typeof Mautic.modalContentXhr[target] != 'undefined') {
                Mautic.modalContentXhr[target].abort();
                delete Mautic.modalContentXhr[target];
            }
        });

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
                mQuery(target).modal('hide');
                Mautic.onPageUnload(target, response);

                if (response.mauticContent) {
                    if (typeof Mautic[response.mauticContent + "OnLoad"] == 'function') {
                        Mautic[response.mauticContent + "OnLoad"](target, response);
                    }
                }
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
                    window["Mautic"][confirmCallback].apply('window', [confirmAction]);
                }
            })
            .html(confirmText);
        if (cancelText) {
            var cancelButton = mQuery('<button type="button" />')
                .addClass("btn btn-primary")
                .click(function () {
                    if (typeof Mautic[cancelCallback] === "function") {
                        window["Mautic"][cancelCallback].apply('window', []);
                    }
                })
                .html(cancelText);
        }

        confirmFooterDiv.append(confirmButton);

        if (typeof cancelButton != 'undefined') {
            confirmFooterDiv.append(cancelButton);
        }

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

        var route = baseUrl + "?tmpl=" + tmpl + "&name=" + name + "&orderby=" + encodeURIComponent(orderby);
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

        var route = baseUrl + "?tmpl=" + tmpl + "&name=" + name + "&filterby=" + encodeURIComponent(filterby) + "&value=" + encodeURIComponent(filterValue)
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

        var route = baseUrl + "?tmpl=" + tmpl + "&name=" + name + "&limit=" + limit;
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
    executeBatchAction: function (action) {
        if (typeof Mautic.activeActions == 'undefined') {
            Mautic.activeActions = {};
        } else if (typeof Mautic.activeActions[action] != 'undefined') {
            // Action is currently being executed
            return;
        }

        // Retrieve all of the selected items
        var items = JSON.stringify(mQuery('input[class=list-checkbox]:checked').map(function () {
            return mQuery(this).val();
        }).get());

        // Append the items to the action to send with the POST
        var action = action + '?ids=' + items;

        // Hand over processing to the executeAction method
        Mautic.executeAction(action);
    },

    /**
     * Checks that items are checked before showing confirmation
     *
     * @returns int
     */
    batchActionPrecheck: function() {
        return mQuery('input[class=list-checkbox]:checked').length;
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
        //find associated button
        var btn = "button[data-livesearch-parent='" + mQuery(el).attr('id') + "']";
        if (mQuery(btn).length) {
            mQuery(btn).on('click', {'parent': mQuery(el).attr('id')}, function (event) {
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

            //update the buttons class and action
            var btn = "button[data-livesearch-parent='" + elId + "']";
            if (mQuery(btn).length) {
                if (action == 'clear') {
                    mQuery(btn).attr('data-livesearch-action', 'search');
                    mQuery(btn).children('i').first().removeClass('fa-eraser').addClass('fa-search');
                } else {
                    mQuery(btn).attr('data-livesearch-action', 'clear');
                    mQuery(btn).children('i').first().removeClass('fa-search').addClass('fa-eraser');
                }
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
                Mautic.liveSearchXhr = mQuery.ajax({
                    showLoadingBar: true,
                    url: route,
                    type: "GET",
                    data: searchName + "=" + encodeURIComponent(value) + '&tmpl=list',
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

                        Mautic.processPageContent(response);
                    },
                    error: function (request, textStatus, errorThrown) {
                        Mautic.processAjaxError(request, textStatus, errorThrown);
                    },
                    complete: function() {
                        delete Mautic.liveSearchXhr;
                    }
                });
            }
        }
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
    togglePublishStatus: function (event, el, model, id, extra) {
        event.preventDefault();

        var wasPublished = mQuery(el).hasClass('fa-toggle-on');

        mQuery(el).removeClass('fa-toggle-on fa-toggle-off').addClass('fa-spin fa-spinner');

        //destroy tooltips so it can be regenerated
        mQuery(el).tooltip('destroy');
        //clear the lookup cache
        MauticVars.liveCache = new Array();

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
                    mQuery('<div />', {
                        'class': "modal-backdrop fade in"
                    }).appendTo('body');
                    setTimeout(function() {
                        location.reload();
                    }, 50);
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

        var isYesButton = mQuery(changedId).parent().hasClass('btn-yes');

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
    },

    /**
     * Apply filter
     * @param list
     */
    setSearchFilter: function (el, searchId) {
        if (typeof searchId == 'undefined')
            searchId = '#list-search';
        else
            searchId = '#' + searchId;
        var filter = mQuery(el).val();
        var current = mQuery('#list-search').typeahead('val');
        current += " " + filter;

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

        //clear filter
        mQuery(el).val('');
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
                var response = mQuery.parseJSON(jsonString);
                console.log(response);
            } else {
                response = {};
            }
        }

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
     * Executes the first step in the update cycle
     *
     * @param container
     * @param step
     * @param state
     */
    processUpdate: function (container, step, state) {
        // Edge case but do it anyway, remove the /index_dev.php from mauticBaseUrl to make sure we can always correctly call the standalone upgrader
        var baseUrl = mauticBasePath + '/';

        switch (step) {
            // Set the update page layout
            case 1:
                mQuery.ajax({
                    showLoadingBar: true,
                    url: mauticAjaxUrl + '?action=core:updateSetUpdateLayout',
                    dataType: 'json',
                    success: function (response) {
                        if (response.success) {
                            mQuery('div[id=' + container + ']').html(response.content);
                            Mautic.processUpdate(container, step + 1, state);
                        } else if (response.redirect) {
                            window.location = response.redirect;
                        }
                    },
                    error: function (request, textStatus, errorThrown) {
                        Mautic.processAjaxError(request, textStatus, errorThrown);
                    }
                });
                break;

            // Download the update package
            case 2:
                mQuery.ajax({
                    showLoadingBar: true,
                    url: mauticAjaxUrl + '?action=core:updateDownloadPackage',
                    dataType: 'json',
                    success: function (response) {
                        if (response.redirect) {
                            window.location = response.redirect;
                        } else {
                            mQuery('td[id=update-step-downloading-status]').html(response.stepStatus);

                            if (response.success) {
                                mQuery('td[id=update-step-downloading-status]').append(mQuery('<i></i>').addClass('pull-right fa fa-check text-success'));
                                mQuery('#updateTable tbody').append('<tr><td>' + response.nextStep + '</td><td id="update-step-extracting-status">' + response.nextStepStatus + ' <i class="pull-right fa fa-spinner fa-spin"></i></td></tr>');
                                Mautic.processUpdate(container, step + 1, state);
                            } else {
                                mQuery('td[id=update-step-downloading-status]').append(mQuery('<i />').addClass('pull-right fa fa-warning text-danger'));
                                mQuery('div[id=main-update-panel]').removeClass('panel-default').addClass('panel-danger');
                                mQuery('div#main-update-panel div.panel-body').prepend('<div class="alert alert-danger">' + response.message + '</div>');
                            }
                        }
                    },
                    error: function (request, textStatus, errorThrown) {
                        Mautic.processAjaxError(request, textStatus, errorThrown);
                    }
                });
                break;

            // Extract the update package
            case 3:
                mQuery.ajax({
                    showLoadingBar: true,
                    url: mauticAjaxUrl + '?action=core:updateExtractPackage',
                    dataType: 'json',
                    success: function (response) {
                        if (response.redirect) {
                            window.location = response.redirect;
                        } else {
                            mQuery('td[id=update-step-extracting-status]').html(response.stepStatus);

                            if (response.success) {
                                mQuery('td[id=update-step-extracting-status]').append(mQuery('<i></i>').addClass('pull-right fa fa-check text-success'));
                                mQuery('#updateTable tbody').append('<tr><td>' + response.nextStep + '</td><td id="update-step-moving-status">' + response.nextStepStatus + ' <i class="pull-right fa fa-spinner fa-spin"></i></td></tr>');
                                Mautic.processUpdate(container, step + 1, state);
                            } else {
                                mQuery('td[id=update-step-extracting-status]').append(mQuery('<i></i>').addClass('pull-right fa fa-warning text-danger'));
                                mQuery('div[id=main-update-panel]').removeClass('panel-default').addClass('panel-danger');
                                mQuery('div#main-update-panel div.panel-body').prepend('<div class="alert alert-danger">' + response.message + '</div>');
                            }
                        }
                    },
                    error: function (request, textStatus, errorThrown) {
                        Mautic.processAjaxError(request, textStatus, errorThrown);
                    }
                });
                break;

            // Move the updated bundles into production
            case 4:
                mQuery.ajax({
                    showLoadingBar: true,
                    url: baseUrl + 'upgrade/upgrade.php?task=moveBundles&updateState=' + state,
                    dataType: 'json',
                    success: function (response) {
                        if (response.redirect) {
                            window.location = response.redirect;
                        } else {
                            mQuery('td[id=update-step-moving-status]').html(response.stepStatus);

                            if (response.error) {
                                mQuery('td[id=update-step-moving-status]').append(mQuery('<i></i>').addClass('pull-right fa fa-warning text-danger'));
                                // If an error state, we cannot move on
                                mQuery('div[id=main-update-panel]').removeClass('panel-default').addClass('panel-danger');
                                mQuery('div#main-update-panel div.panel-body').prepend('<div class="alert alert-danger">' + response.message + '</div>');
                            } else if (response.complete) {
                                mQuery('td[id=update-step-moving-status]').append(mQuery('<i></i>').addClass('pull-right fa fa-spinner fa-spin'));

                                // If complete then we go into the next step
                                Mautic.processUpdate(container, step + 1, response.updateState);
                            } else {
                                mQuery('td[id=update-step-moving-status]').append(mQuery('<i></i>').addClass('pull-right fa fa-spinner fa-spin'));

                                // In this section, the step hasn't completed yet so we repeat it
                                Mautic.processUpdate(container, step, response.updateState);
                            }
                        }
                    },
                    error: function (request, textStatus, errorThrown) {
                        Mautic.processAjaxError(request, textStatus, errorThrown);
                    }
                });
                break;

            // Move the rest of core into production
            case 5:
                mQuery.ajax({
                    showLoadingBar: true,
                    url: baseUrl + 'upgrade/upgrade.php?task=moveCore&updateState=' + state,
                    dataType: 'json',
                    success: function (response) {
                        if (response.redirect) {
                            window.location = response.redirect;
                        } else {
                            mQuery('td[id=update-step-moving-status]').html(response.stepStatus);

                            if (response.error) {
                                // If an error state, we cannot move on
                                mQuery('td[id=update-step-moving-status]').append(mQuery('<i></i>').addClass('pull-right fa fa-warning text-danger'));
                                mQuery('div[id=main-update-panel]').removeClass('panel-default').addClass('panel-danger');
                                mQuery('div#main-update-panel div.panel-body').prepend('<div class="alert alert-danger">' + response.message + '</div>');
                            } else if (response.complete) {
                                mQuery('td[id=update-step-moving-status]').append(mQuery('<i></i>').addClass('pull-right fa fa-spinner fa-spin'));

                                // If complete then we go into the next step
                                Mautic.processUpdate(container, step + 1, response.updateState);
                            } else {
                                mQuery('td[id=update-step-moving-status]').append(mQuery('<i></i>').addClass('pull-right fa fa-spinner fa-spin'));

                                // In this section, the step hasn't completed yet so we repeat it
                                Mautic.processUpdate(container, step, response.updateState);
                            }
                        }
                    },
                    error: function (request, textStatus, errorThrown) {
                        Mautic.processAjaxError(request, textStatus, errorThrown);
                    }
                });
                break;

            // Move the vendors into production
            case 6:
                mQuery.ajax({
                    showLoadingBar: true,
                    url: baseUrl + 'upgrade/upgrade.php?task=moveVendors&updateState=' + state,
                    dataType: 'json',
                    success: function (response) {
                        if (response.redirect) {
                            window.location = response.redirect;
                        } else {
                            mQuery('td[id=update-step-moving-status]').html(response.stepStatus);

                            if (response.error) {
                                // If an error state, we cannot move on
                                mQuery('td[id=update-step-moving-status]').append(mQuery('<i></i>').addClass('pull-right fa fa-warning text-danger'));
                                mQuery('div[id=main-update-panel]').removeClass('panel-default').addClass('panel-danger');
                                mQuery('div#main-update-panel div.panel-body').prepend('<div class="alert alert-danger">' + response.message + '</div>');
                            } else if (response.complete) {
                                mQuery('td[id=update-step-moving-status]').append(mQuery('<i></i>').addClass('pull-right fa fa-check text-success'));

                                // If complete then we go into the next step
                                mQuery('#updateTable tbody').append('<tr><td>' + response.nextStep + '</td><td id="update-step-cache-status">' + response.nextStepStatus + ' <i class="pull-right fa fa-spinner fa-spin"></i></td></tr>');
                                Mautic.processUpdate(container, step + 1, response.updateState);
                            } else {
                                // In this section, the step hasn't completed yet so we repeat it
                                mQuery('td[id=update-step-moving-status]').append(mQuery('<i></i>').addClass('pull-right fa fa-spinner fa-spin'));
                                Mautic.processUpdate(container, step, response.updateState);
                            }
                        }
                    },
                    error: function (request, textStatus, errorThrown) {
                        Mautic.processAjaxError(request, textStatus, errorThrown);
                    }
                });
                break;

            // Clear the application cache
            case 7:
                mQuery.ajax({
                    showLoadingBar: true,
                    url: baseUrl + 'upgrade/upgrade.php?task=clearCache&updateState=' + state,
                    dataType: 'json',
                    success: function (response) {
                        if (response.redirect) {
                            window.location = response.redirect;
                        } else {
                            mQuery('td[id=update-step-cache-status]').html(response.stepStatus);

                            if (response.error) {
                                mQuery('td[id=update-step-cache-status]').append(mQuery('<i></i>').addClass('pull-right fa fa-warning text-danger'));

                                // If an error state, we cannot move on
                                mQuery('div[id=main-update-panel]').removeClass('panel-default').addClass('panel-danger');
                                mQuery('div#main-update-panel div.panel-body').prepend('<div class="alert alert-danger">' + response.message + '</div>');
                            } else if (response.complete) {
                                mQuery('td[id=update-step-cache-status]').append(mQuery('<i></i>').addClass('pull-right fa fa-check text-success'));

                                // If complete then we go into the next step
                                mQuery('#updateTable tbody').append('<tr><td>' + response.nextStep + '</td><td id="update-step-database-status">' + response.nextStepStatus + ' <i class="pull-right fa fa-spinner fa-spin"></i></td></tr>');
                                Mautic.processUpdate(container, step + 1, response.updateState);
                            } else {
                                mQuery('td[id=update-step-cache-status]').append(mQuery('<i></i>').addClass('pull-right fa fa-spinner fa-spin'));

                                // In this section, the step hasn't completed yet so we repeat it
                                Mautic.processUpdate(container, step, response.updateState);
                            }
                        }
                    },
                    error: function (request, textStatus, errorThrown) {
                        Mautic.processAjaxError(request, textStatus, errorThrown);
                    }
                });
                break;

            // Migrate the database
            case 8:
                mQuery.ajax({
                    showLoadingBar: true,
                    url: mauticAjaxUrl + '?action=core:updateDatabaseMigration',
                    dataType: 'json',
                    success: function (response) {
                        if (response.redirect) {
                            window.location = response.redirect;
                        } else {
                            if (response.success) {
                                mQuery('div[id=' + container + ']').html('<div class="alert alert-mautic">' + response.message + '</div>');
                            } else {
                                mQuery('td[id=update-step-database-status]').html(response.stepStatus);
                                mQuery('div[id=main-update-panel]').removeClass('panel-default').addClass('panel-danger');
                                mQuery('div#main-update-panel div.panel-body').prepend('<div class="alert alert-danger">' + response.message + '</div>');
                            }
                        }
                    },
                    error: function (request, textStatus, errorThrown) {
                        Mautic.processAjaxError(request, textStatus, errorThrown);
                    }
                });
                break;
        }

        Mautic.stopPageLoadingBar();
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
    markNotificationsRead: function () {
        mQuery("#notificationsDropdown").unbind('hide.bs.dropdown');
        mQuery('#notificationsDropdown').on('hidden.bs.dropdown', function () {
            if (!mQuery('#newNotificationIndicator').hasClass('hide')) {
                mQuery('#notifications .is-unread').remove();
                mQuery('#newNotificationIndicator').addClass('hide');

                mQuery.ajax({
                    url: mauticAjaxUrl,
                    type: "GET",
                    data: "action=markNotificationsRead"
                });
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
     * Launch builder
     *
     * @param formName
     */
    launchBuilder: function (formName) {
        var src = mQuery('#builder_url').val();
        src += '?template=' + mQuery('#'+formName+'_template').val();

        var builderCss = {
            margin: "0",
            padding: "0",
            border: "none",
            width: "100%",
            height: "100%"
        };

        var spinnerLeft = (mQuery(document).width() - 300) / 2;
        var overlay = mQuery('<div id="builder-overlay" class="modal-backdrop fade in"><div style="position: absolute; top:50%; left:' + spinnerLeft + 'px"><i class="fa fa-spinner fa-spin fa-5x"></i></div></div>').css(builderCss).appendTo('.builder-content');

        var builder = mQuery("<iframe />", {
            css: builderCss,
            id: "builder-template-content"
        })
            .attr('src', src)
            .appendTo('.builder-content')
            .load(function () {
                mQuery('#builder-overlay').addClass('hide');
                var contents = mQuery(this).contents();
                // here, catch the droppable div and create a droppable widget
                contents.find('.mautic-editable').droppable({
                    iframeFix: true,
                    drop: function (event, ui) {
                        var editorId = mQuery(this).attr("id");
                        var drop = mQuery(ui.draggable).data('drop');
                        var token = mQuery(ui.draggable).data('token');

                        if (drop) {
                            Mautic[drop](event, ui, editorId, token);
                        } else {
                            Mautic.insertBuilderEditorToken(editorId, token);
                        }
                        mQuery(this).removeClass('over-droppable');
                    },
                    over: function (e, ui) {
                        mQuery(this).addClass('over-droppable');
                    },
                    out: function (e, ui) {
                        mQuery(this).removeClass('over-droppable');
                    }
                });

                Mautic.activateBuilderDragTokens();
            });

        //make the panel full screen
        mQuery('.builder').addClass('builder-active').removeClass('hide');
    },

    /**
     * Prepare builder
     *
     * @param target
     */
    builderOnLoad: function (target) {
        Mautic.activateBuilderDragTokens();
    },

    /**
     * Set builder token draggables
     *
     * @param target
     */
    activateBuilderDragTokens: function (target) {
        if (typeof target == 'undefined') {
            target = '.builder-panel';
        }

        //activate builder drag and drop
        mQuery(target + " *[data-token]").draggable({
            iframeFix: true,
            iframeId: 'builder-template-content',
            helper: 'clone',
            appendTo: '.builder',
            zIndex: 8000,
            scroll: true,
            scrollSensitivity: 100,
            scrollSpeed: 100,
            cursorAt: {top: 15, left: 15}
        });
    },

    /**
     * Close the builder
     *
     * @param model
     */
    closeBuilder: function(model) {
        mQuery('#builder-overlay').removeClass('hide');

        // Save content
        var editors = Mautic.getBuilderEditorInstance();
        var content = {};

        var builderContents = mQuery('#builder-template-content').contents();

        // Make sure editors have lost focus so the content is updated
        builderContents.find('.mautic-editable').each(function (index) {
            mQuery(this).blur();
        });

        // Get the content of each editor
        mQuery.each(editors, function(slot, editor) {
            slot = slot.replace("slot-", "");
            content[slot] = editor.getData();
        });

        Mautic.saveBuilderContent(model, builderContents.find('#builder_entity_id').val(), content, function (response) {
            if (response.success) {
                // Kill droppables
                builderContents.find('.mautic-editable').droppable('destroy');

                // Kill draggables
                mQuery("*[data-token]").draggable('destroy');

                // mQuery('.builder').addClass('hide');
                Mautic.stopIconSpinPostEvent();
            }
            // Kill the iframe and overlay
            mQuery('#builder-overlay').remove();

            // Hide builder
            mQuery('.builder').removeClass('builder-active').addClass('hide');
        });

        mQuery('#builder-template-content').remove();
    },

    /**
     * Save the builder content
     *
     * @param model
     * @param entityId
     * @param content
     * @param callback
     */
    saveBuilderContent: function (model, entityId, content, callback) {
        mQuery.ajax({
            url: mauticAjaxUrl + '?action=' + model + ':setBuilderContent',
            type: "POST",
            data: {
                slots: content,
                entity: entityId
            },
            success: function(response) {
                if (typeof callback === "function") {
                    callback(response);
                }
            }
        });
    },

    /**
     * Get ckeditor instance
     *
     * @param id
     * @returns {*}
     */
    getBuilderEditorInstance: function (id) {
        var editors = document.getElementById('builder-template-content').contentWindow.CKEDITOR.instances;
        if (id) {
            return editors[id];
        } else {
            return editors;
        }
    },

    /**
     * Insert token into ckeditor
     *
     * @param editorId
     * @param token
     * @param isHtml
     */
    insertBuilderEditorToken: function(editorId, token, isHtml) {
        var editor = Mautic.getBuilderEditorInstance(editorId);

        if (typeof isHtml == 'undefined') {
            var first = token.charAt(0);
            if (first == '<') {
                isHtml = true;
            }
        }

        if (isHtml) {
            editor.insertHtml(token);
        } else {
            editor.insertText(token);
        }
    },

    /**
     * Show modal to insert a link into ckeditor
     * @param event
     * @param ui
     * @param editorId
     */
    showBuilderLinkModal: function (event, ui, editorId) {
        // Reset in case the modal wasn't closed via cancel
        mQuery('#BuilderLinkModal input[name="link"]').val('');
        mQuery('#BuilderLinkModal input[name="text"]').val('');
        mQuery('#BuilderLinkModal input[name="text"]').parent().removeClass('hide');

        var token  = mQuery(ui.draggable).data('token');
        mQuery('#BuilderLinkModal input[name="editor"]').val(editorId);
        mQuery('#BuilderLinkModal input[name="token"]').val(token);

        var defaultUrl = token.match(/%url=(.*?)%/);

        if (defaultUrl && defaultUrl[1]) {
            mQuery('#BuilderLinkModal input[name="url"]').val(defaultUrl[1]);
        }

        var defaultText = token.match(/%text=(.*?)%/);
        if (defaultText && defaultText[1]) {
            mQuery('#BuilderLinkModal input[name="text"]').val(defaultText[1]);
        } else if (!token.match(/%text%/g)) {
            //hide the text
            mQuery('#BuilderLinkModal input[name="text"]').parent().addClass('hide');
        }

        //append the modal to the builder or else it won't display
        mQuery('#BuilderLinkModal').appendTo('body');
        mQuery('#BuilderLinkModal').modal('show');
    },

    /**
     * Insert link into ckeditor
     */
    insertBuilderLink: function () {
        var editorId = mQuery('#BuilderLinkModal input[name="editor"]').val();
        var token    = mQuery('#BuilderLinkModal input[name="token"]').val();
        var url      = mQuery('#BuilderLinkModal input[name="url"]').val();
        var text     = mQuery('#BuilderLinkModal input[name="text"]').val();

        if (url) {
            if (!text) {
                text = url;
            }
            token = token.replace(/%url(.*?)%/, url).replace(/%text(.*?)%/, text);
            Mautic.insertBuilderEditorToken(editorId, token);
        }

        mQuery('#BuilderLinkModal').modal('hide');
        mQuery('#BuilderLinkModal input[name="editor"]').val('');
        mQuery('#BuilderLinkModal input[name="link"]').val('');
        mQuery('#BuilderLinkModal input[name="text"]').val('');
        mQuery('#BuilderLinkModal input[name="text"]').parent().removeClass('hide');
    },

    /**
     * Show builder feedback modal (accept input and insert into editor)
     *
     * @param event
     * @param ui
     * @param editorId
     */
    showBuilderFeedbackModal: function (event, ui, editorId) {
        // Reset in case the modal wasn't closed via cancel
        mQuery('#BuilderFeedbackModal input[name="feedback"]').val('');
        mQuery('#BuilderFeedbackModal input[name="feedback"]').attr('placeholder', '');

        var token  = mQuery(ui.draggable).data('token');
        mQuery('#BuilderFeedbackModal input[name="editor"]').val(editorId);
        mQuery('#BuilderFeedbackModal input[name="token"]').val(token);

        var placeholder = token.match(/%(.*?)%/);
        if (placeholder && placeholder[1]) {
            mQuery('#BuilderFeedbackModal input[name="feedback"]').attr('placeholder', placeholder[1]);
        }

        //append the modal to the builder or else it won't display
        mQuery('#BuilderFeedbackModal').appendTo('body');
        mQuery('#BuilderFeedbackModal').modal('show');
    },

    /**
     * Insert input feedback into ckeditor
     */
    insertBuilderFeedback: function () {
        var editorId = mQuery('#BuilderFeedbackModal input[name="editor"]').val();
        var token    = mQuery('#BuilderFeedbackModal input[name="token"]').val();
        var feedback = mQuery('#BuilderFeedbackModal input[name="feedback"]').val();

        if (feedback) {
            token = token.replace(/%(.*?)%/, feedback);
            Mautic.insertBuilderEditorToken(editorId, token);
        }

        mQuery('#BuilderFeedbackModal').modal('hide');
        mQuery('#BuilderFeedbackModal input[name="editor"]').val('');
        mQuery('#BuilderFeedbackModal input[name="feedback"]').val('');
        mQuery('#BuilderFeedbackModal input[name="feedback"]').attr('placeholder', '');
    },

    /**
     * Execute an action to AjaxController
     *
     * @param action
     * @param data
     * @param successClosure
     */
    ajaxActionRequest: function(action, data, successClosure) {
        if (typeof Mautic.ajaxActionXhr == 'undefined') {
            Mautic.ajaxActionXhr = {};
        } else if (typeof Mautic.ajaxActionXhr[action] != 'undefined') {
            Mautic.removeLabelLoadingIndicator();
            Mautic.ajaxActionXhr[action].abort();
        }

        Mautic.ajaxActionXhr[action] = mQuery.ajax({
            url: mauticAjaxUrl + '?action=' + action,
            type: 'POST',
            data: data,
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
     * Tests an email server connection
     */
    testEmailServerConnection: function(sendEmail) {
        var data = {
            transport:  mQuery('#config_coreconfig_mailer_transport').val(),
            host:       mQuery('#config_coreconfig_mailer_host').val(),
            port:       mQuery('#config_coreconfig_mailer_port').val(),
            encryption: mQuery('#config_coreconfig_mailer_encryption').val(),
            authMode:   mQuery('#config_coreconfig_mailer_auth_mode').val(),
            user:       mQuery('#config_coreconfig_mailer_user').val(),
            password:   mQuery('#config_coreconfig_mailer_password').val(),
            from_name:  mQuery('#config_coreconfig_mailer_from_name').val(),
            from_email: mQuery('#config_coreconfig_mailer_from_email').val(),
            send_test:  (typeof sendEmail !== 'undefined') ? sendEmail : false
        };

        mQuery('.button_container .fa-spinner').removeClass('hide');

        Mautic.ajaxActionRequest('testEmailServerConnection', data, function(response) {
            var theClass = (response.success) ? 'has-success' : 'has-error';
            var theMessage = response.message;
            mQuery('#mailerTestButtonContainer').removeClass('has-success has-error').addClass(theClass);
            mQuery('#mailerTestButtonContainer .help-block').html(theMessage);
            mQuery('.button_container .fa-spinner').addClass('hide');
        });
    }
};