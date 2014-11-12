var MauticVars = {};
var mQuery = jQuery.noConflict(true);
window.jQuery = mQuery;

mQuery( document ).ready(function() {
    if (typeof mauticContent !== 'undefined') {
        (function ($) {
            $("html").Core({
                console: false
            });
        })(mQuery);
    }

    if (typeof Mousetrap != 'undefined') {
        Mousetrap.bind('shift+d', function(e) {
            mQuery('#mautic_dashboard_index').click();
        });

        Mousetrap.bind('shift+right', function(e) {
            mQuery('.navbar-right > button.navbar-toggle').click();
        });
    }
});

//Fix for back/forward buttons not loading ajax content with History.pushState()
MauticVars.manualStateChange = true;
History.Adapter.bind(window, 'statechange', function () {
    if (MauticVars.manualStateChange == true) {
        //back/forward button pressed
        window.location.reload();
    }
    MauticVars.manualStateChange = true;
});

//live search vars
MauticVars.liveCache            = new Array();
MauticVars.lastSearchStr        = "";
MauticVars.globalLivecache      = new Array();
MauticVars.lastGlobalSearchStr  = "";

//used for spinning icons (to show something is in progress)
MauticVars.iconClasses          = {};

//register the loading bar for ajax page loads
MauticVars.showLoadingBar       = true;

//prevent multiple ajax calls from multiple clicks
MauticVars.routeInProgress       = '';

mQuery.ajaxSetup({
    beforeSend: function () {
        if (MauticVars.showLoadingBar) {
            mQuery("body").addClass("loading-content");
        }
    },
    cache: false,
    complete: function () {
        setTimeout(function () {
            mQuery("body").removeClass("loading-content");
        }, 500);

        //change default back to show
        MauticVars.showLoadingBar = true;
    }
});

if (typeof Chart != 'undefined') {
    // configure global Chart options
    Chart.defaults.global.responsive = true;
}

var Mautic = {
    /**
     * Initiate various functions on page load, manual or ajax
     */
    onPageLoad: function (container, response) {
        //initiate links
        mQuery(container + " a[data-toggle='ajax']").off('click.ajax');
        mQuery(container + " a[data-toggle='ajax']").on('click.ajax', function (event) {
            event.preventDefault();

            return Mautic.ajaxifyLink(this, event);
        });

        mQuery(".sidebar-left a[data-toggle='ajax']").on('click.ajax', function (event) {
            mQuery("html").removeClass('sidebar-open-ltr');
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

        //little hack to move modal windows outside of positioned divs
        mQuery(container + " *[data-toggle='modal']").each(function (index) {
            var target = mQuery(this).attr('data-target');

            //move the modal to the body tag to get around positioned div issues
            mQuery(target).off('show.bs.modal');
            mQuery(target).on('show.bs.modal', function () {
                if (!mQuery(target).hasClass('modal-moved')) {
                    mQuery(target).appendTo("body");
                    mQuery(target).addClass('modal-moved');
                }
            });
        });

        //initialize date/time
        mQuery(container + " *[data-toggle='datetime']").datetimepicker({
            format: 'Y-m-d H:i',
            lazyInit: true,
            validateOnBlur: false,
            allowBlank: true,
            scrollInput: false
        });

        mQuery(container + " *[data-toggle='date']").datetimepicker({
            timepicker: false,
            format: 'Y-m-d',
            lazyInit: true,
            validateOnBlur: false,
            allowBlank: true,
            scrollInput: false,
            closeOnDateSelect: true
        });

        mQuery(container + " *[data-toggle='time']").datetimepicker({
            datepicker: false,
            format: 'H:i',
            lazyInit: true,
            validateOnBlur: false,
            allowBlank: true,
            scrollInput: false
        });

        mQuery(container + " input[data-toggle='color']").spectrum({
            allowEmpty: true,
            showInput: true,
            preferredFormat: 'hex'
        });

        //convert multiple selects into chosen
        mQuery("select[multiple]").chosen({
            placeholder_text_multiple: ' '
        });

        //spin icons on button click
        mQuery(container + ' .btn:not(.btn-nospin)').on('click.spinningicons', function(event) {
            Mautic.startIconSpinOnEvent(event);
        });

        //Copy form buttons to the toolbar
        if (mQuery(container + " .bottom-form-buttons").length) {
            //hide the toolbar actions if applicable
            mQuery('.toolbar-action-buttons').addClass('hide');

            if (mQuery('.toolbar-form-buttons').hasClass('hide')) {
                //hide the bottom buttons
                mQuery('.bottom-form-buttons').addClass('hide');
                var buttons = mQuery(container + " .bottom-form-buttons").html();
                mQuery(buttons).filter("button").each(function (i, v) {
                    //get the ID
                    var id = mQuery(this).attr('id');
                    var button = mQuery("<button type='button' />")
                        .addClass(mQuery(this).attr('class'))
                        .html(mQuery(this).html())
                        .appendTo('.toolbar-form-buttons')
                        .on('click.ajaxform', function (event) {
                            event.preventDefault();
                            Mautic.startIconSpinOnEvent(event);
                            mQuery('#' + id).click();
                        });
                });
                mQuery('.toolbar-form-buttons').removeClass('hide');
            }
        }

        //activate editors
        if (mQuery(container + " textarea.editor").length) {
            mQuery(container + " textarea.editor").ckeditor();
        }

        //run specific on loads
        var contentSpecific = false;
        if (response && response.mauticContent) {
            contentSpecific = response.mauticContent;
        } else if (container == 'body') {
            contentSpecific = mauticContent;
        }

        if (contentSpecific && typeof Mautic[contentSpecific + "OnLoad"] == 'function') {
            Mautic[contentSpecific + "OnLoad"](container, response);
        }

        if (container == 'body') {
            //activate global live search
            var engine = new Bloodhound({
                datumTokenizer: Bloodhound.tokenizers.obj.whitespace('value'),
                queryTokenizer: Bloodhound.tokenizers.whitespace,
                prefetch: {
                    url: mauticAjaxUrl + "?action=globalCommandList"
                }
            });
            engine.initialize();

            mQuery('#global_search').typeahead({
                    hint: true,
                    highlight: true,
                    minLength: 0,
                    multiple: true
                },
                {
                    name: "global_search",
                    displayKey: 'value',
                    source: engine.ttAdapter()
                }
            ).on('typeahead:selected', function (event, datum) {
                //force live search update
                MauticVars.lastGlobalSearchStr = '';
                mQuery('#global_search').keyup();
            }).on('typeahead:autocompleted', function (event, datum) {
                //force live search update
                MauticVars.lastGlobalSearchStr = '';
                mQuery('#global_search').keyup();
            }).on('keypress', function (event) {
                if ((event.keyCode || event.which) == 13) {
                    mQuery('#global_search').typeahead('close');
                }
            });

            Mautic.activateLiveSearch("#global_search", "lastGlobalSearchStr", "globalLivecache");
        }

        //instantiate sparkline plugin
        mQuery('.plugin-sparkline').sparkline('html', { enableTagOptions: true });

        // instantiate the plugin
        mQuery('.flotchart').flotChart();
    },

    /**
     * Functions to be ran on ajax page unload
     */
    onPageUnload: function (container, response) {
        //unload tooltips so they don't double show
        if (typeof container != 'undefined') {
            mQuery(container + " *[data-toggle='tooltip']").tooltip('destroy');

            //unload lingering modals from body so that there will not be multiple modals generated from new ajaxed content
            mQuery(container + " *[data-toggle='modal']").each(function (index) {
                var target = mQuery(this).attr('data-target');
                mQuery(target).remove();
            });

            mQuery(container + " *[data-toggle='ajaxmodal']").each(function (index) {
                if (mQuery(this).attr('data-ignore-removemodal') != 'true') {
                    var target = mQuery(this).attr('data-target');
                    mQuery(target).remove();
                }
            });
        }

        //run specific unloads
        var contentSpecific = false;
        if (response && response.mauticContent) {
            contentSpecific = response.mauticContent;
        } else if (container == '#app-content') {
            contentSpecific = mauticContent;
        }

        if (contentSpecific && typeof Mautic[contentSpecific + "OnUnload"] == 'function') {
            Mautic[response.mauticContent + "OnUnload"](container, response);
        }
    },

    /**
     * Takes a given route, retrieves the HTML, and then updates the content
     * @param route
     * @param link
     * @param method
     * @param target
     */
    loadContent: function (route, link, method, target) {
        mQuery.ajax({
            url: route,
            type: method,
            dataType: "json",
            success: function (response) {
                if (response) {
                    if (target || response.target) {
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
                Mautic.processAjaxError(request, textStatus, errorThrown);

                //clear routeInProgress
                MauticVars.routeInProgress = '';

                //restore button class if applicable
                Mautic.stopIconSpinPostEvent();
            }
        });

        //prevent firing of href link
        //mQuery(link).attr("href", "javascript: void(0)");
        return false;
    },

    /**
     * Just a little visual that an action is taking place
     * @param event
     */
    startIconSpinOnEvent: function (event)
    {
        if (event && typeof(event.target) !== 'undefined' && mQuery(event.target).length) {
            var hasBtn = mQuery(event.target).hasClass('btn');
            var hasIcon = mQuery(event.target).hasClass('fa');

            var i = (hasBtn && mQuery(event.target).find('i.fa').length) ? mQuery(event.target).find('i.fa') : event.target;

            var notDropdown = mQuery(i).attr('class').indexOf('fa-angle');
            var notDirection = mQuery(i).attr('class').indexOf('fa-caret');

            if (((hasBtn && mQuery(event.target).find('i.fa').length) || hasIcon) && notDropdown == -1 && notDirection == -1) {
                var el              = (hasIcon) ? event.target : mQuery(event.target).find('i.fa').first();
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

                //set a timeout in case it doesn't get reset
                setTimeout(function() {
                    Mautic.stopIconSpinPostEvent(identifierClass);
                }, 10000);
            }
        }
    },

    /**
     * Stops the icon spinning after an event is complete
     */
    stopIconSpinPostEvent: function(specificId)
    {
        if (typeof specificId != 'undefined' && specificId in MauticVars.iconClasses) {
            mQuery('.' + specificId).removeClass('fa fa-spinner fa-spin ' + specificId).addClass(MauticVars.iconClasses[specificId]);
            delete MauticVars.iconClasses[specificId];
        } else {
            mQuery.each(MauticVars.iconClasses, function( index, value ) {
                mQuery('.' + index).removeClass('fa fa-spinner fa-spin ' + index).addClass(value);
            });

            MauticVars.iconClasses = {};
        }
    },

    /**
     * Posts a form and returns the output.
     * Uses jQuery form plugin so it handles files as well.
     * @param form
     * @param callback
     */
    postForm: function (form, callback) {
        var form = mQuery(form);
        var action = form.attr('action');

        if (action.indexOf("ajax=1") == -1) {
            form.attr('action', action + ((/\?/i.test(action)) ? "&ajax=1" : "?ajax=1"));
        }

        form.ajaxSubmit({
            success: function(data) {
                MauticVars.formSubmitInProgress = false;
                callback(data);
            },
            error: function(request, textStatus, errorThrown) {
                MauticVars.formSubmitInProgress = false;

                Mautic.processAjaxError(request, textStatus, errorThrown);
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

                window.setTimeout(function () {
                    mQuery("#flashes .alert").fadeTo(500, 0).slideUp(500, function () {
                        mQuery(this).remove();
                    });
                }, 7000);

                if (response.activeLink) {
                    //remove current classes from menu items
                    mQuery(".side-panel-nav").find(".current").removeClass("current");

                    //remove ancestor classes
                    mQuery(".side-panel-nav").find(".current_ancestor").removeClass("current_ancestor");

                    var link = response.activeLink;
                    if (link !== undefined && link.charAt(0) != '#') {
                        link = "#" + link;
                    }

                    //add current class
                    var parent = mQuery(link).parent();
                    mQuery(parent).addClass("current");

                    //add current_ancestor classes
                    mQuery(parent).parentsUntil(".side-panel-nav", "li").addClass("current_ancestor");
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

        Mautic.stopIconSpinPostEvent();
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
                            value: mQuery(this).attr('value') })
                    );
                }

                //give an ajaxified form the option of not displaying the global loading bar
                var loading = mQuery(this).attr('data-hide-loadingbar');
                if (loading) {
                    MauticVars.showLoadingBar = false;
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
                var modalParent = mQuery('form[name="' + formName + '"]').closest('.modal');
                var isInModal   = modalParent.length > 0 ? true : false;
                if (!isInModal) {
                    Mautic.processPageContent(response);
                } else {
                    var target = '#' + modalParent.attr('id');
                    Mautic.processModalContent(response, target);
                }
            });

            return false;
        }));
    },

    ajaxifyLink: function (el, event) {
        //prevent leaving if currently in a form
        if (mQuery(".form-exit-unlock-id").length) {
            if (mQuery(el).attr('data-ignore-formexit') != 'true') {
                var unlockParameter = (mQuery('.form-exit-unlock-parameter').length) ? mQuery('.form-exit-unlock-parameter').val() : '';
                Mautic.unlockEntity(mQuery('.form-exit-unlock-model').val(), mQuery('.form-exit-unlock-id').val(), unlockParameter);
            }
        }
        var route = mQuery(el).attr('href');
        if (route.indexOf('javascript')>=0 || MauticVars.routeInProgress === route) {
            return false;
        }

        if (event.ctrlKey || event.metaKey ) {
            //open the link in a new window
            route = route.split("?")[0];
            window.open(route, '_blank');
        }

        var link = mQuery(el).attr('data-menu-link');
        if (link !== undefined && link.charAt(0) != '#') {
            link = "#" + link;
        }

        var method = mQuery(el).attr('data-method');
        if (!method) {
            method = 'GET'
        }

        //give an ajaxified link the option of not displaying the global loading bar
        var loading = mQuery(el).attr('data-hide-loadingbar');
        if (loading) {
            MauticVars.showLoadingBar = false;
        }

        MauticVars.routeInProgress = route;

        var target = mQuery(el).attr('data-target');
        if (!target) {
            target = null;
        }

        Mautic.loadContent(route, link, method, target);
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

        MauticVars.showLoadingBar = false;

        var route = mQuery(el).attr('href');
        if (route.indexOf('javascript')>=0) {
            return false;
        }

        var method = mQuery(el).attr('data-method');
        if (!method) {
            method = 'GET'
        }

        //show the modal
        if (mQuery(target + ' .loading-placeholder').length) {
            mQuery(target + ' .loading-placeholder').removeClass('hide');
            mQuery(target + ' .modal-body-content').addClass('hide');
        }

        var header = mQuery(el).attr('data-header');
        if (header) {
            mQuery(target + " .modal-title").html(header);
        }

        //move the modal to the body tag to get around positioned div issues
        mQuery(target).on('show.bs.modal', function () {
            if (!mQuery(target).hasClass('modal-moved')) {
                mQuery(target).appendTo('body');
                mQuery(target).addClass('modal-moved');
            }
        });

        mQuery(target).modal('show');

        mQuery.ajax({
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
            }
        });
    },

    processModalContent: function (response, target) {
        //load the content
        if (mQuery(target + ' .loading-placeholder').length) {
            mQuery(target + ' .loading-placeholder').addClass('hide');
            mQuery(target + ' .modal-body-content').html(response.newContent);
            mQuery(target + ' .modal-body-content').removeClass('hide');
        } else {
            mQuery(target + ' .modal-body').html(response.newContent);
        }

        //activate content specific stuff
        Mautic.onPageLoad(target, response);

        if (response.closeModal) {
            mQuery(target).modal('hide');
        }
    },

    /**
     * Display confirmation modal
     * @param msg
     * @param confirmText
     * @param confirmAction
     * @param confirmParams
     * @param cancelText
     * @param cancelAction
     * @param cancelParams
     */
    showConfirmation: function (msg, confirmText, confirmAction, confirmParams, cancelText, cancelAction, cancelParams) {
        if (cancelAction == '') {
            //default is to close the modal
            cancelAction = "dismissConfirmation";
        }

        if (typeof confirmText == 'undefined') {
            confirmText   = '<i class="fa fa-fw fa-2x fa-check"></i>';
            confirmAction = 'dismissConfirmation';
        }

        var confirmContainer = mQuery("<div />").attr({ "class": "modal fade confirmation-modal" });
        var confirmDialogDiv = mQuery("<div />").attr({ "class": "modal-dialog"});
        var confirmContentDiv = mQuery("<div />").attr({ "class": "modal-content confirmation-inner-wrapper"});
        var confirmFooterDiv = mQuery("<div />").attr({ "class": "modal-body text-center"});
        var confirmHeaderDiv = mQuery("<div />").attr({"class": "modal-header"});
        confirmHeaderDiv.append(mQuery('<h4 />').attr({"class": "modal-title"}).text(msg));
        var confirmButton = mQuery('<button type="button" />')
            .addClass("btn btn-danger")
            .css("marginRight", "5px")
            .css("marginLeft", "5px")
            .click(function () {
                if (typeof Mautic[confirmAction] === "function") {
                    window["Mautic"][confirmAction].apply('window', confirmParams);
                }
            })
            .html(confirmText);
        if (cancelText) {
            var cancelButton = mQuery('<button type="button" />')
                .addClass("btn btn-primary")
                .click(function () {
                    if (typeof Mautic[cancelAction] === "function") {
                        window["Mautic"][cancelAction].apply('window', cancelParams);
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
    reorderTableData: function (name, orderby, tmpl, target) {
        var route = window.location.pathname + "?tmpl=" + tmpl + "&name=" + name + "&orderby=" + orderby;

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
    filterTableData: function (name, filterby, filterValue, tmpl, target) {
        var query = "action=setTableFilter&name=" + name + "&filterby=" + filterby + "&value=" + filterValue;
        mQuery.ajax({
            url: mauticAjaxUrl,
            type: "POST",
            data: query,
            dataType: "json",
            success: function (response) {
                if (response.success) {
                    var route = window.location.pathname + "?tmpl=" + tmpl;
                    Mautic.loadContent(route, '', 'GET', target);
                }
            },
            error: function (request, textStatus, errorThrown) {
                Mautic.processAjaxError(request, textStatus, errorThrown);
            }
        });
    },

    limitTableData: function (name, limit, tmpl, target) {
        var query = "action=setTableLimit&name=" + name + "&limit=" + limit;
        mQuery.ajax({
            url: mauticAjaxUrl,
            type: "POST",
            data: query,
            dataType: "json",
            success: function (response) {
                if (response.success) {
                    var route = window.location.pathname + "?tmpl=" + tmpl;
                    Mautic.loadContent(route, '', 'GET', target);
                }
            },
            error: function (request, textStatus, errorThrown) {
                Mautic.processAjaxError(request, textStatus, errorThrown);
            }
        });
    },

    /**
     * Executes an object action
     *
     * @param action
     * @param menuLink
     */
    executeAction: function (action, menuLink) {
        //dismiss modal if activated
        Mautic.dismissConfirmation();
        mQuery.ajax({
            url: action,
            type: "POST",
            dataType: "json",
            success: function (response) {
                if (!response.target) {
                    //make a bogus target so that it doesn't jump to the top of the page
                    response.target = 'ignoreme';
                }
                Mautic.processPageContent(response);
            },
            error: function (request, textStatus, errorThrown) {
                Mautic.processAjaxError(request, textStatus, errorThrown);
            }
        });
    },

    /**
     * Executes a batch action
     *
     * @param action
     * @param menuLink
     */
    executeBatchAction: function (action, menuLink) {
	    // Retrieve all of the selected items
        var items = JSON.stringify(mQuery('input[class=list-checkbox]:checked').map(function() {
            return mQuery(this).val();
        }).get());

	    // Append the items to the action to send with the POST
	    var action = action + '?ids=' + items;

	    // Hand over processing to the executeAction method
	    Mautic.executeAction(action, menuLink);
    },

    /**
     * Activates Typeahead.js command lists for search boxes
     * @param elId
     * @param modelName
     */
    activateSearchAutocomplete: function (elId, modelName) {
        if (mQuery('#' + elId).length) {
            var livesearch = (mQuery('#' + elId).attr("data-toggle=['livesearch']")) ? true : false;

            var engine = new Bloodhound({
                datumTokenizer: Bloodhound.tokenizers.obj.whitespace('value'),
                queryTokenizer: Bloodhound.tokenizers.whitespace,
                prefetch: {
                    url: mauticAjaxUrl + "?action=commandList&model=" + modelName
                }
            });
            engine.initialize();

            mQuery('#' + elId).typeahead({
                    hint: true,
                    highlight: true,
                    minLength: 0,
                    multiple: true
                },
                {
                    name: elId,
                    displayKey: 'value',
                    source: engine.ttAdapter()
                }
            ).on('typeahead:selected', function (event, datum) {
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
            }).on('keypress', function (event) {
                if ((event.keyCode || event.which) == 13) {
                    mQuery('#' + elId).typeahead('close');
                }
            });
        }
    },

    activateLiveSearch: function(el, searchStrVar, liveCacheVar) {
        if (!mQuery(el).length) {
            return;
        }

        mQuery(el).on('keyup', {}, function (event) {
            var searchStr = mQuery(el).val().trim();
            var target    = mQuery(el).attr('data-target');
            var diff      = searchStr.length - MauticVars[searchStrVar].length;

            if (diff < 0) {
                diff = parseInt(diff) * -1;
            }

            var spaceKeyPressed  = (event.which == 32 || event.keyCode == 32);
            var enterKeyPressed  = (event.which == 13 || event.keyCode == 13);
            var deleteKeyPressed = (event.which == 8 || event.keyCode == 8);


            var overlayEnabled = mQuery(el).attr('data-overlay');
            if (!overlayEnabled || overlayEnabled == 'false') {
                overlayEnabled = false;
            } else {
                overlayEnabled = true;
            }

            var overlayTarget = mQuery(el).attr('data-overlay-target');
            if (!overlayTarget) overlayTarget = target;

            if (!deleteKeyPressed && overlayEnabled) {
                var overlay = mQuery('<div />', {"class": "content-overlay"}).html(mQuery(el).attr('data-overlay-text'));
                if (mQuery(el).attr('data-overlay-background')) {
                    overlay.css('background', mQuery(el).attr('data-overlay-background'));
                }
                if (mQuery(el).attr('data-overlay-color')) {
                    overlay.css('color', mQuery(el).attr('data-overlay-color'));
                }
            }

            if (
                !MauticVars.searchIsActive &&
                (
                    //searchStr in MauticVars[liveCacheVar] ||
                    (!searchStr && MauticVars[searchStrVar].length) ||
                    diff >= 3 ||
                    spaceKeyPressed ||
                    enterKeyPressed
                )
            ) {
                MauticVars.searchIsActive = true;
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
                response.target         = target;
                response.overlayEnabled = overlayEnabled;
                response.overlayTarget  = overlayTarget;

                Mautic.processPageContent(response);
                MauticVars.searchIsActive = false;
            } else {
                var searchName = el.attr('name');
                if (searchName == 'undefined') {
                    searchName = 'search';
                }

                mQuery.ajax({
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
                        response.target         = target;
                        response.overlayEnabled = overlayEnabled;
                        response.overlayTarget  = overlayTarget;

                        Mautic.processPageContent(response);

                        MauticVars.searchIsActive = false;
                    },
                    error: function (request, textStatus, errorThrown) {
                        Mautic.processAjaxError(request, textStatus, errorThrown);
                    }
                });
            }
        }
    },

    /**
     * Removes a list option from a list generated by ListType
     * @param el
     */
    removeFormListOption: function(el) {
        var sortableDiv = mQuery(el).parents('div.sortable');
        var inputCount  = mQuery(sortableDiv).parents('div.form-group').find('input.sortable-itemcount');
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

        //destroy tooltips so it can be regenerated
        mQuery(el).tooltip('destroy');
        //clear the lookup cache
        MauticVars.liveCache      = new Array();
        MauticVars.showLoadingBar = false;

        if (extra) {
            extra = '&' + extra;
        }
        mQuery.ajax({
            url: mauticAjaxUrl,
            type: "POST",
            data: "action=togglePublishStatus&model=" + model + '&id=' + id + extra,
            dataType: "json",
            success: function (response) {
                Mautic.stopIconSpinPostEvent();

                if (response.statusHtml) {
                    mQuery(el).replaceWith(response.statusHtml);
                    mQuery(el).tooltip({html: true, container: 'body'});
                }
            },
            error: function (request, textStatus, errorThrown) {
                Mautic.processAjaxError(request, textStatus, errorThrown);
            }
        });
    },

    /**
     * Apply filter
     * @param list
     */
    setSearchFilter: function(el, searchId) {
        if (typeof searchId == 'undefined')
            searchId = '#list-search';
        else
            searchId = '#' + searchId;
        var filter  = mQuery(el).val();
        var current = mQuery('#list-search').typeahead('val');
        current    += " " + filter;

        //append the filter
        mQuery(searchId).typeahead('val', current);

        //submit search
        var e = mQuery.Event( "keypress", { which: 13 } );
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
    unlockEntity: function(model, id, parameter) {
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
    processAjaxError: function(request, textStatus, errorThrown) {
       if (typeof mauticEnv !== 'undefined' && mauticEnv == 'dev') {
           console.log(request);

           if (typeof request.responseJSON !== 'undefined' && typeof request.responseJSON.error !== 'undefined') {
               var error = request.responseJSON.error.code + ': ' + errorThrown + '; ' + request.responseJSON.error.exception;
               alert(error);
           } else if (typeof request.responseText !== 'undefined') {
               var regex = /{(.+?)}}/g //g flag so the regex is global

               //check to see if the error is embedded in html
               var match = request.responseText.match(regex);

               if (match) {
                   var errorObj = mQuery.parseJSON(match);
                   var error = errorObj.error.code + ': ' + errorThrown + '; ' + errorObj.error.exception;
                   alert(error);
               }
           }
       }
    }
};
