/**
 * Takes a given route, retrieves the HTML, and then updates the content
 *
 * @param route
 * @param link
 * @param method
 * @param target
 * @param showPageLoading
 * @param callback
 * @param data
 */
Mautic.loadContent = function (route, link, method, target, showPageLoading, callback, data) {
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
};

/**
 * Generates the title of the current page
 *
 * @param route
 */
Mautic.generatePageTitle = function(route){

    if (-1 !== route.indexOf('timeline')) {
        return
    } else if (-1 !== route.indexOf('view')) {
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

        // Encoded entites are decoded by this process and can cause a XSS
        currentModuleItem = mQuery('<div>'+currentModuleItem+'</div>').text();

        mQuery('title').html( currentModule[0].toUpperCase() + currentModule.slice(1) + ' | ' + currentModuleItem + ' | Mautic' );
    } else {
        //loading basic title
        mQuery('title').html( mQuery('.page-header h3').html() + ' | Mautic' );
    }
};

/**
 * Updates new content
 * @param response
 */
Mautic.processPageContent = function (response) {
    if (response) {
        Mautic.deactivateBackgroup();

        if (response.errors && 'dev' == mauticEnv) {
            alert(response.errors[0].message);
            console.log(response.errors);
        }

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
};

/**
 * Initiate various functions on page load, manual or ajax
 */
Mautic.onPageLoad = function (container, response, inModal) {
    Mautic.initDateRangePicker(container + ' #daterange_date_from', container + ' #daterange_date_to');

    //initiate links
    Mautic.makeLinksAlive(mQuery(container + " a[data-toggle='ajax']"));

    //initialize forms
    mQuery(container + " form[data-toggle='ajax']").each(function (index) {
        Mautic.ajaxifyForm(mQuery(this).attr('name'));
    });

    //initialize ajax'd modals
    Mautic.makeModalsAlive(mQuery(container + " *[data-toggle='ajaxmodal']"))

    //initialize embedded modal forms
    Mautic.activateModalEmbeddedForms(container);

    //initalize live search boxes
    mQuery(container + " *[data-toggle='livesearch']").each(function (index) {
        Mautic.activateLiveSearch(mQuery(this), "lastSearchStr", "liveCache");
    });

    //initialize list filters
    mQuery(container + " *[data-toggle='listfilter']").each(function (index) {
        Mautic.activateListFilterSelect(mQuery(this));
    });

    //initialize tooltips
    var pageTooltips = mQuery(container + " *[data-toggle='tooltip']");
    pageTooltips.tooltip({html: true, container: 'body'});

    // Enable tooltips on checkbox & radio input's to
    // show when hovering their parent LABEL element
    pageTooltips.each(function(i) {
        var thisTooltip   = mQuery(pageTooltips.get(i));
        var elementParent = thisTooltip.parent();

        if (elementParent.get(0).tagName === 'LABEL') {
            elementParent.append('<i class="fa fa-question-circle"></i>');

            elementParent.hover(function () {
                thisTooltip.tooltip('show')
            }, function () {
                thisTooltip.tooltip('hide');
            });
        }
    });


    //initialize sortable lists
    mQuery(container + " *[data-toggle='sortablelist']").each(function (index) {
        Mautic.activateSortable(this);
    });

    mQuery(container + " div.sortable-panels").each(function () {
        Mautic.activateSortablePanels(this);
    });

    //downloads
    mQuery(container + " a[data-toggle='download']").off('click.download');
    mQuery(container + " a[data-toggle='download']").on('click.download', function (event) {
        event.preventDefault();

        Mautic.initiateFileDownload(mQuery(this).attr('href'));
    });

    Mautic.makeConfirmationsAlive(mQuery(container + " a[data-toggle='confirmation']"));

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

    // Initialize callback options
    mQuery(container + " *[data-onload-callback]").each(function() {
        var callback = function(el) {
            if (typeof window["Mautic"][mQuery(el).attr('data-onload-callback')] == 'function') {
                window["Mautic"][mQuery(el).attr('data-onload-callback')].apply('window', [el]);
            }
        }

        mQuery(document).ready(callback(this));
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

    // Initialize tab overflow
    mQuery(container + " .nav-overflow-tabs ul").each(function() {
        Mautic.activateOverflowTabs(this);
    });

    mQuery(container + " .nav.sortable").each(function() {
        Mautic.activateSortableTabs(this);
    });

    // Initialize tab delete buttons
    Mautic.activateTabDeleteButtons(container);

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
    mQuery(container + " .bottom-form-buttons").each(function() {
        if (inModal || mQuery(this).closest('.modal').length) {
            var modal = (inModal) ? container : mQuery(this).closest('.modal');
            if (mQuery(modal).find('.modal-form-buttons').length) {
                //hide the bottom buttons
                mQuery(modal).find('.bottom-form-buttons').addClass('hide');
                var buttons = mQuery(modal).find('.bottom-form-buttons').html();

                //make sure working with a clean slate
                mQuery(modal).find('.modal-form-buttons').html('');

                mQuery(buttons).filter("button").each(function (i, v) {
                    //get the ID
                    var id = mQuery(this).attr('id');
                    var button = mQuery("<button type='button' />")
                        .addClass(mQuery(this).attr('class'))
                        .addClass('btn-copy')
                        .html(mQuery(this).html())
                        .appendTo(mQuery(modal).find('.modal-form-buttons'))
                        .on('click.ajaxform', function (event) {
                            if (mQuery(this).hasClass('disabled')) {

                                return false;
                            }

                            // Disable the form buttons until this action is complete
                            if (!mQuery(this).hasClass('btn-dnd')) {
                                mQuery(this).parent().find('button').prop('disabled', true);
                            }

                            event.preventDefault();
                            if (!mQuery(this).hasClass('btn-nospin')) {
                                Mautic.startIconSpinOnEvent(event);
                            }
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
    });

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

            var maxButtons = ['undo', 'redo', '|', 'bold', 'italic', 'underline', 'paragraphFormat', 'fontFamily', 'fontSize', 'color', 'align', 'formatOL', 'formatUL', 'quote', 'clearFormatting', 'token', 'insertLink', 'insertImage', 'insertGatedVideo', 'insertTable', 'html', 'fullscreen'];
            var minButtons = ['undo', 'redo', '|', 'bold', 'italic', 'underline'];

            if (textarea.hasClass('editor-email')) {
                maxButtons = mQuery.grep(maxButtons, function(value) {
                    return value != 'insertGatedVideo';
                });

                maxButtons.push('dynamicContent');
            }

            if (textarea.hasClass('editor-dynamic-content')) {
                minButtons = ['undo', 'redo', '|', 'bold', 'italic', 'underline', 'paragraphFormat', 'fontFamily', 'fontSize', 'color', 'align', 'formatOL', 'formatUL', 'quote', 'clearFormatting', 'insertLink', 'insertImage', 'insertGatedVideo', 'insertTable', 'html', 'fullscreen'];
            }

            if (textarea.hasClass('editor-basic')) {
                minButtons = ['undo', 'redo', '|', 'bold', 'italic', 'underline', 'paragraphFormat', 'fontFamily', 'fontSize', 'color', 'align', 'formatOL', 'formatUL', 'quote', 'clearFormatting', 'insertLink', 'insertImage', 'insertTable', 'html', 'fullscreen'];
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
                    toolbarButtonsMD: minButtons,
                    toolbarButtonsSM: minButtons,
                    toolbarButtonsXS: minButtons,
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

    if (response && response.updateSelect && typeof response.id !== 'undefined') {
        // An new item is to be injected
        Mautic.updateEntitySelect(response);
    }

    //run specific on loads
    var contentSpecific = false;
    if (response && response.mauticContent) {
        contentSpecific = response.mauticContent;
    } else if (container == 'body') {
        contentSpecific = mauticContent;
    }

    if (response && response.sidebar) {
        var sidebarContent = mQuery('.app-sidebar.sidebar-left');
        var newSidebar     = mQuery(response.sidebar);
        var nav            = sidebarContent.find('li');

        if (nav.length) {
            var openNavIndex;

            nav.each(function(i, el) {
                var $el = mQuery(el);

                if ($el.hasClass('open')) {
                    openNavIndex = i;
                }
            });

            var openNav = mQuery(newSidebar.find('li')[openNavIndex]);

            openNav.addClass('open');
            openNav.find('ul').removeClass('collapse');
        }

        sidebarContent.html(newSidebar);
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
        if (inModal || typeof Mautic.loadedContent[contentSpecific] == 'undefined') {
            Mautic.loadedContent[contentSpecific] = true;
            Mautic[contentSpecific + "OnLoad"](container, response);
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
};

/**
 *
 * @param jQueryObject
 */
Mautic.makeConfirmationsAlive = function(jQueryObject) {
    jQueryObject.off('click.confirmation');
    jQueryObject.on('click.confirmation', function (event) {
        event.preventDefault();
        MauticVars.ignoreIconSpin = true;
        return Mautic.showConfirmation(this);
    });
};

/**
 *
 * @param jQueryObject
 */
Mautic.makeModalsAlive = function(jQueryObject) {
    jQueryObject.off('click.ajaxmodal');
    jQueryObject.on('click.ajaxmodal', function (event) {
        event.preventDefault();

        Mautic.ajaxifyModal(this, event);
    });
};

/**
 *
 * @param jQueryObject
 */
Mautic.makeLinksAlive = function(jQueryObject) {
    jQueryObject.off('click.ajax');
    jQueryObject.on('click.ajax', function (event) {
        event.preventDefault();

        return Mautic.ajaxifyLink(this, event);
    });
};

/**
 * Functions to be ran on ajax page unload
 */
Mautic.onPageUnload = function (container, response) {
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

        if (typeof Mautic.loadedContent[contentSpecific] !== 'undefined') {
            delete Mautic.loadedContent[contentSpecific];
        }
    }
};

/**
 * Retrieves content of href via ajax
 * @param el
 * @param event
 * @returns {boolean}
 */
Mautic.ajaxifyLink = function (el, event) {
    if (mQuery(el).hasClass('disabled')) {
        return false;
    }

    var route = mQuery(el).attr('href');
    if (route.indexOf('javascript') >= 0 || MauticVars.routeInProgress === route) {
        return false;
    }

    if (route.indexOf('batchExport') >= 0) {
        Mautic.initiateFileDownload(route);
        return true;
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
};

/**
 * Convert to chosen select
 *
 * @param el
 */
Mautic.activateChosenSelect = function(el, ignoreGlobal, jQueryVariant) {
    var mQuery = (typeof jQueryVariant != 'undefined') ? jQueryVariant : window.mQuery;
    if (mQuery(el).parents('.no-chosen').length && !ignoreGlobal) {
        // Globally ignored chosens because they are handled manually due to hidden elements, etc
        return;
    }

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
                if (mQuery(el).val() == 'new' && (mQuery(el).attr('data-popup') == "true" || mQuery(el).closest('.modal').length > 0)) {
                    var queryGlue = url.indexOf('?') >= 0 ? '&' : '?';
                    // De-select the new select option
                    mQuery(el).find('option[value="new"]').prop('selected', false);
                    mQuery(el).trigger('chosen:updated');

                    Mautic.loadNewWindow({
                        "windowUrl": url + queryGlue + "contentOnly=1&updateSelect=" + mQuery(el).attr('id')
                    });
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

    if (typeof mQuery(el).data('chosen-placeholder') !== 'undefined') {
        multiPlaceholder = singlePlaceholder = mQuery(el).data('chosen-placeholder');
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
                minTermLength: 2,
                jsonTermKey: searchTerm,
                keepTypingMsg: "Keep typing...",
                lookingForMsg: "Looking for"
            });
        }
    }
};

/**
 * Activate a typeahead lookup
 *
 * @param field
 * @param target
 * @param options
 */
Mautic.activateFieldTypeahead = function (field, target, options, action) {
    if (options && typeof options === 'String') {
        var keys = values = [];

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

    var callback = function (event, datum) {
        if (mQuery("#" + field).length && datum["value"]) {
            mQuery("#" + field).val(datum["value"]);

            var lookupCallback = mQuery('#' + field).data("lookup-callback");
            if (lookupCallback && typeof Mautic[lookupCallback] == 'function') {
                Mautic[lookupCallback](field, datum);
            }
        }
    };

    mQuery(fieldTypeahead).on('typeahead:selected', callback).on('typeahead:autocompleted', callback);
};

/**
 * Convert to multiselect
 *
 * @param el
 */
Mautic.activateMultiSelect = function(el) {
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
};

/**
 * Activate modal buttons for embedded forms
 *
 * @param container
 */
Mautic.activateModalEmbeddedForms = function(container) {
    mQuery(container + " *[data-embedded-form='cancel']").off('click.embeddedform');
    mQuery(container + " *[data-embedded-form='cancel']").on('click.embeddedform', function (event) {
        event.preventDefault();

        var modal = mQuery(this).closest('.modal');
        mQuery(modal).modal('hide');

        if (mQuery(this).attr('data-embedded-form-clear') === 'true') {
            Mautic.resetForm(modal);
        }

        if (typeof mQuery(this).attr('data-embedded-form-callback') != 'undefined') {
            if (typeof window["Mautic"][mQuery(this).attr('data-embedded-form-callback')] == 'function') {
                window["Mautic"][mQuery(this).attr('data-embedded-form-callback')].apply('window', [this, modal]);
            }
        }
    });

    // Configure the modal
    mQuery(container + " *[data-embedded-form='add']").each(function() {
        var submitButton = this;
        var modal = mQuery(this).closest('.modal');
        if (typeof mQuery(modal).data('bs.modal') !== 'undefined' && typeof mQuery(modal).data('bs.modal').options !== 'undefined') {
            mQuery(modal).data('bs.modal').options.keyboard = false;
            mQuery(modal).data('bs.modal').options.backdrop = 'static';
        } else {
            mQuery(modal).attr('data-keyboard', false);
            mQuery(modal).attr('data-backdrop', 'static');
        }

        mQuery(modal).on('show.bs.modal', function () {
            // Don't allow submitting with enter key
            mQuery(this).on("keydown.embeddedForm", ":input:not(textarea)", function(event) {
                if (event.keyCode == 13) {
                    event.preventDefault();
                    if (event.metaKey || event.ctrlKey) {
                        // Submit the modal
                        mQuery(submitButton).click();
                    }
                }
            });
        });

        //clean slate upon close
        mQuery(modal).on('hidden.bs.modal', function () {
            mQuery(this).off("keydown.embeddedForm", ":input:not(textarea)");
        });
    });

    mQuery(container + " *[data-embedded-form='add']").off('click.embeddedform');
    mQuery(container + " *[data-embedded-form='add']").on('click.embeddedform', function (event) {
        event.preventDefault();

        var modal = mQuery(this).closest('.modal');
        mQuery(modal).modal('hide');

        if (typeof mQuery(this).attr('data-embedded-form-callback') != 'undefined') {
            if (typeof window["Mautic"][mQuery(this).attr('data-embedded-form-callback')] == 'function') {
                window["Mautic"][mQuery(this).attr('data-embedded-form-callback')].apply('window', [this, modal]);
            }
        }
    });
};

/**
 * Activate containers datetime inputs
 * @param container
 */
Mautic.activateDateTimeInputs = function(el, type) {
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
};

/**
 * Activates Typeahead.js command lists for search boxes
 * @param elId
 * @param modelName
 */
Mautic.activateSearchAutocomplete = function (elId, modelName) {
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
};

/**
 * Activate live search feature
 *
 * @param el
 * @param searchStrVar
 * @param liveCacheVar
 */
Mautic.activateLiveSearch = function (el, searchStrVar, liveCacheVar) {
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
};

/**
 * Filters a list based on select value
 *
 * @param el
 */
Mautic.activateListFilterSelect = function(el) {
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
};

/**
 * Converts an input to a color picker
 * @param el
 */
Mautic.activateColorPicker = function(el, options) {
    var pickerOptions = mQuery(el).data('color-options');
    if (!pickerOptions) {
        pickerOptions = {
            theme: 'bootstrap',
            change: function (hex, opacity) {
                mQuery(el).trigger('change.minicolors', hex);
            }
        };
    }

    if (typeof options == 'object') {
        pickerOptions = mQuery.extend(pickerOptions, options);
    }

    mQuery(el).minicolors(pickerOptions);
};

/**
 * Activates typeahead
 * @param el
 * @param options
 * @returns {*}
 */
Mautic.activateTypeahead = function (el, options) {
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
};

/**
 * Activate sortable
 *
 * @param el
 */
Mautic.activateSortable = function(el) {
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
};

/**
 * Close global search results
 */
Mautic.closeGlobalSearchResults = function () {
    mQuery('#globalSearchContainer').removeClass('active');
    mQuery('#globalSearchDropdown').removeClass('open');
    mQuery('body').off('click.globalsearch');
};

/**
 * Download a link via iframe
 *
 * @param link
 */
Mautic.initiateFileDownload = function (link) {
    //initialize download links
    var iframe = mQuery("<iframe/>").attr({
        src: link,
        style: "visibility:hidden;display:none"
    }).appendTo(mQuery('body'));
};
