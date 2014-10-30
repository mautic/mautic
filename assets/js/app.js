var MauticVars = {};
var mQuery = jQuery.noConflict(true);

if (typeof mauticContent !== 'undefined') {
    (function ($) {
        $("html").Core({
            console: false
        });
    })(mQuery);
}

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
    xhr: function () {
        var xhr = new window.XMLHttpRequest();
        if (MauticVars.showLoadingBar) {
            xhr.upload.addEventListener("progress", function (evt) {
                if (evt.lengthComputable) {
                    var percentComplete = Math.round((evt.loaded / evt.total) * 100);
                    mQuery(".loading-bar .progress-bar").attr('aria-valuenow', percentComplete);
                    mQuery(".loading-bar .progress-bar").css('width', percentComplete + "%");
                }
            }, false);
            xhr.addEventListener("progress", function (evt) {
                if (evt.lengthComputable) {
                    var percentComplete = Math.round((evt.loaded / evt.total) * 100);
                    mQuery(".loading-bar .progress-bar").attr('aria-valuenow', percentComplete);
                    mQuery(".loading-bar .progress-bar").css('width', percentComplete + "%");
                }
            }, false);
        }
        return xhr;
    },
    complete: function () {
        if (MauticVars.showLoadingBar) {
            setTimeout(function () {
                mQuery("body").removeClass("loading-content");
                mQuery(".loading-bar .progress-bar").attr('aria-valuenow', 0);
                mQuery(".loading-bar .progress-bar").css('width', "0%");
            }, 500);
        } else {
            //change default back to show
            MauticVars.showLoadingBar = true;
        }
    }
});

var Mautic = {
    /**
     * Initiate various functions on page load, manual or ajax
     */
    onPageLoad: function (container, response) {
        container = typeof container !== 'undefined' ? container : 'body';

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
                            mQuery('#' + id).click();
                        });
                });
                mQuery('.toolbar-form-buttons').removeClass('hide');
            }
        }

        //Activate hidden shelves
        mQuery(container + ' .hidden-shelf').each(function(index) {
            var shelf    = mQuery(this);
            var handle   = mQuery(this).find('.shelf-handle').first();
            var contents = mQuery(this).find('.shelf-contents').first();

            mQuery(handle).off('click.shelf');
            mQuery(handle).on('click.shelf', function(event) {
                if (mQuery(contents).css('display') == 'block') {
                    mQuery(handle).find('i').removeClass('fa-chevron-circle-up').addClass('fa-chevron-circle-down')
                    mQuery(contents).slideUp();
                } else {
                    mQuery(handle).find('i').removeClass('fa-chevron-circle-down').addClass('fa-chevron-circle-up')
                    mQuery(contents).slideDown();
                }
            });
        });

        //run specific on loads
        var contentSpecific = (response && response.mauticContent) ? response.mauticContent : mauticContent;
        if (typeof Mautic[contentSpecific + "OnLoad"] == 'function') {
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
        container = typeof container !== 'undefined' ? container : 'body';

        mQuery(container + " *[data-toggle='tooltip']").tooltip('destroy');

        //unload tinymce editor so that it can be reloaded if needed with new ajax content
        mQuery(container + " textarea[data-toggle='editor']").each(function (index) {
            mQuery(this).tinymce().remove();
        });

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

        //run specific unloads
        if (typeof Mautic[mauticContent + "OnUnload"] == 'function') {
            Mautic[mauticContent + "OnUnload"](container, response);
        }
    },

    /**
     * Takes a given route, retrieves the HTML, and then updates the content
     * @param route
     * @param link
     * @param method
     * @param target
     * @param event
     */
    loadContent: function (route, link, method, target, event) {
        //keep browser backbutton from loading cached ajax response
        //var ajaxRoute = route + ((/\?/i.test(route)) ? "&ajax=1" : "?ajax=1");

        //little animation to let the user know that something is happening
        if (typeof event != 'undefined' && event.target) {
            Mautic.startIconSpinOnEvent(event);
        }

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

                        if (mQuery(".page-wrapper").hasClass("right-active")) {
                            mQuery(".page-wrapper").removeClass("right-active");
                        }

                        Mautic.processPageContent(response);
                    }

                    //restore button class if applicable
                    Mautic.stopIconSpinPostEvent();

                    //clear routeInProgress
                    MauticVars.routeInProgress = '';
                }
            },
            error: function (request, textStatus, errorThrown) {
                if (mauticEnv == 'dev') {
                    alert(errorThrown);
                }
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
        var hasBtn = mQuery(event.target).hasClass('btn');
        var hasIcon = mQuery(event.target).hasClass('fa');
        if ((hasBtn && mQuery(event.target).find('i.fa').length) || hasIcon) {
            MauticVars.iconButton = (hasIcon) ? event.target :  mQuery(event.target).find('i.fa').first();
            MauticVars.iconClassesRemoved = mQuery(MauticVars.iconButton).attr('class');
            var specialClasses = ['fa-fw', 'fa-lg', 'fa-2x', 'fa-3x', 'fa-4x', 'fa-5x', 'fa-li'];
            var appendClasses  = "";

            //check for special classes to add to spinner
            for (var i=0; i<specialClasses.length; i++) {
                if (mQuery(MauticVars.iconButton).hasClass(specialClasses[i])) {
                    appendClasses += " " + specialClasses[i];
                }
            }
            mQuery(MauticVars.iconButton).removeClass();
            mQuery(MauticVars.iconButton).addClass('fa fa-spinner fa-spin' + appendClasses);
        }
    },

    /**
     * Stops the icon spinning after an event is complete
     */
    stopIconSpinPostEvent: function()
    {
        if (typeof MauticVars.iconClassesRemoved != 'undefined') {
            if (mQuery(MauticVars.iconButton).hasClass('fa-spin')) {
                mQuery(MauticVars.iconButton).removeClass('fa fa-spinner fa-spin').addClass(MauticVars.iconClassesRemoved);
            }
            delete MauticVars.iconButton;
            delete MauticVars.iconClassesRemoved;
        }
    },

    /**
     * Opens or closes submenus in main navigation
     * @param link
     */
    toggleSubMenu: function (link, event) {
        if (mQuery(link).length) {
            //get the parent li element
            var parent = mQuery(link).parent();
            var child = mQuery(parent).find("ul").first();
            if (child.length) {
                var toggle = event.target;

                if (child.hasClass("subnav-closed")) {
                    //open the submenu
                    child.removeClass("subnav-closed").addClass("subnav-open");
                    mQuery(toggle).removeClass("fa-angle-right").addClass("fa-angle-down");
                } else if (child.hasClass("subnav-open")) {
                    //close the submenu
                    child.removeClass("subnav-open").addClass("subnav-closed");
                    mQuery(toggle).removeClass("fa-angle-down").addClass("fa-angle-right");
                }
            }
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
            error: function(info, type, errorThrown) {
                MauticVars.formSubmitInProgress = false;
                if ('console' in window) {
                    console.log('form error log', info);
                }
                if (mauticEnv == 'dev') {
                    alert(errorThrown);
                }
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

            //update type of content displayed
            if (response.mauticContent) {
                mauticContent = response.mauticContent;
            }

            //inactive tooltips, etc
            Mautic.onPageUnload(response.target, response);

            if (response.route) {
                //update URL in address bar
                MauticVars.manualStateChange = false;
                History.pushState(null, "Mautic", response.route);
            }

            //set content
            if (response.newContent) {
                if (response.replaceContent && response.replaceContent == 'true') {
                    mQuery(response.target).replaceWith(response.newContent);
                } else {
                    mQuery(response.target).html(response.newContent);
                }
            }

            window.setTimeout(function() {
                mQuery("#flashes .alert").fadeTo(500, 0).slideUp(500, function(){
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

            //scroll to the top
            if (response.target == '#app-content') {
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

        Mautic.loadContent(route, link, method, target, event);
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

        //little animation to let the user know that something is happening
        if (typeof event != 'undefined' && event.target) {
            Mautic.startIconSpinOnEvent(event);
        }


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
                mQuery(target).appendTo("body");
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
                if (mauticEnv == 'dev') {
                    alert(errorThrown);
                }
                Mautic.stopIconSpinPostEvent();
            }
        });
    },

    processModalContent: function (response, target) {
        //load the content
        if (mQuery(target + ' .loading-placeholder').length) {
            mQuery(target + ' .loading-placeholder').addClass('hide');
            mQuery(target + " .modal-body-content").html(response.newContent);
            mQuery(target + " .modal-body-content").removeClass('hide');
        } else {
            mQuery(target + " .modal-body").html(response.newContent);
        }

        //inactive tooltips, etc
        Mautic.onPageUnload(target, response);

        //activate content specific stuff
        Mautic.onPageLoad(target, response);

        if (response.closeModal) {
            mQuery(target).modal('hide');
        }
    },

    /**
     * Show/hide side panels
     * @param position
     */
    toggleSidePanel: function (position) {
        //spring the right panel back into place after clicking elsewhere
        if (position == "right") {
            //toggle active state
            mQuery(".page-wrapper").toggleClass("right-active");
            //prevent firing event multiple times if directly toggling the panel
            mQuery(".main-panel-wrapper").off("click");
            mQuery(".main-panel-wrapper").click(function (e) {
                e.preventDefault();
                if (mQuery(".page-wrapper").hasClass("right-active")) {
                    mQuery(".page-wrapper").removeClass("right-active");
                }
                //prevent firing event multiple times
                mQuery(".main-panel-wrapper").off("click");
            });

            mQuery(".top-panel").off("click");
            mQuery(".top-panel").click(function (e) {
                if (!mQuery(e.target).parents('.panel-toggle').length) {
                    //dismiss the panel if clickng anywhere in the top panel except the toggle button
                    e.preventDefault();
                    if (mQuery(".page-wrapper").hasClass("right-active")) {
                        mQuery(".page-wrapper").removeClass("right-active");
                    }
                    //prevent firing event multiple times
                    mQuery(".top-panel").off("click");
                }
            });

        } else {
            //toggle hidden state
            mQuery(".page-wrapper").toggleClass("hide-left");
        }
    },

    /**
     * Stick a side panel
     * @param position
     */
    stickSidePanel: function (position) {
        MauticVars.showLoadingBar = false;
        var query = "action=togglePanel&panel=" + position;
        mQuery.ajax({
            url: mauticAjaxUrl,
            type: "POST",
            data: query,
            dataType: "json"
        });

        if (position == "left") {
            mQuery(".left-side-bar-pin i").toggleClass("unpinned");

            //auto collapse the left side panel
            if (mQuery(".left-side-bar-pin i").hasClass("unpinned")) {
                //prevent firing event multiple times if directly toggling the panel
                mQuery(".main-panel-wrapper").off("click");
                mQuery(".main-panel-wrapper").click(function (e) {
                    e.preventDefault();
                    if (!mQuery(".page-wrapper").hasClass("hide-left")) {
                        mQuery(".page-wrapper").addClass("hide-left");
                    }
                    //prevent firing event multiple times
                    mQuery(".main-panel-wrapper").off("click");
                });

                mQuery(".top-panel").off("click");
                mQuery(".top-panel").click(function (e) {
                    if (!mQuery(e.target).parents('.panel-toggle').length) {
                        //dismiss the panel if clickng anywhere in the top panel except the toggle button
                        e.preventDefault();
                        if (!mQuery(".page-wrapper").hasClass("hide-left")) {
                            mQuery(".page-wrapper").addClass("hide-left");
                        }
                        //prevent firing event multiple times
                        mQuery(".top-panel").off("click");
                    }
                });
            }
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

        var confirmContainer = mQuery("<div />").attr({ "class": "confirmation-modal" });
        var confirmInnerDiv = mQuery("<div />").attr({ "class": "confirmation-inner-wrapper"});
        var confirmMsgSpan = mQuery("<span />").css("display", "block").html(msg);
        var confirmButton = mQuery('<button type="button" />')
            .addClass("btn btn-danger btn-xs")
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
                .addClass("btn btn-primary btn-xs")
                .click(function () {
                    if (typeof Mautic[cancelAction] === "function") {
                        window["Mautic"][cancelAction].apply('window', cancelParams);
                    }
                })
                .html(cancelText);
        }

        confirmInnerDiv.append(confirmMsgSpan);
        confirmInnerDiv.append(confirmButton);

        if (typeof cancelButton != 'undefined') {
            confirmInnerDiv.append(cancelButton);
        }

        confirmContainer.append(confirmInnerDiv);
        mQuery('body').append(confirmContainer)
    },

    /**
     * Dismiss confirmation modal
     */
    dismissConfirmation: function () {
        if (mQuery('.confirmation-modal').length) {
            mQuery('.confirmation-modal').remove();
        }
    },

    /**
     * Reorder table data
     * @param name
     * @param orderby
     */
    reorderTableData: function (name, orderby, tmpl, target) {
        var query = "action=setTableOrder&name=" + name + "&orderby=" + orderby;
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
                if (mauticEnv == 'dev') {
                    alert(errorThrown);
                }
            }
        });
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
                if (mauticEnv == 'dev') {
                    alert(errorThrown);
                }
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
                if (mauticEnv == 'dev') {
                    alert(errorThrown);
                }
            }
        });
    },
    /**
     * Executes an object action
     * @param action
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
                if (mauticEnv == 'dev') {
                    alert(errorThrown);
                }
            }
        });
    },

    /**
     * Shows the search search input in an search list
     */
    showSearchInput: function () {
        if (mQuery('.toolbar').length) {
            mQuery('.toolbar').addClass('show-search').removeClass('hide-search');
        }
    },

    /**
     * Hides the search search input in an search list
     */
    hideSearchInput: function (elId) {
        if (mQuery('.toolbar').length && mQuery('#' + elId).length && !mQuery('#' + elId).val() && !mQuery('#' + elId).is(":focus")) {
            mQuery('.toolbar').addClass('hide-search').removeClass('show-search');
        }
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
        mQuery(el).on('keyup', {}, function (event) {
            var searchStr = mQuery(el).val().trim();
            var diff = searchStr.length - MauticVars[searchStrVar].length;
            var overlayEnabled = mQuery(el).attr('data-overlay');

            if (overlayEnabled != 'false') {
                var overlay = mQuery('<div />', {"class": "content-overlay"}).html(mQuery(el).attr('data-overlay-text'));
                if (mQuery(el).attr('data-overlay-background')) {
                    overlay.css('background', mQuery(el).attr('data-overlay-background'));
                }
                if (mQuery(el).attr('data-overlay-color')) {
                    overlay.css('color', mQuery(el).attr('data-overlay-color'));
                }
                var target = mQuery(el).attr('data-target');
                var overlayTarget = mQuery(el).attr('data-overlay-target');
                if (!overlayTarget) overlayTarget = target;
            }
            if (
                !MauticVars.searchIsActive &&
                (
                    searchStr in MauticVars[liveCacheVar] ||
                    diff >= 3 ||
                    event.which == 32 || event.keyCode == 32 ||
                    event.which == 13 || event.keyCode == 13
                )
            ) {
                MauticVars.searchIsActive = true;
                MauticVars[searchStrVar] = searchStr;
                event.data.livesearch = true;

                if (overlayEnabled != 'false') {
                    mQuery(overlayTarget + ' .content-overlay').remove();
                }

                Mautic.filterList(event, mQuery(el).attr('id'), mQuery(el).attr('data-action'), target, liveCacheVar);
            } else if (overlayEnabled != 'false') {
                if (!mQuery(overlayTarget + ' .content-overlay').length) {
                    mQuery(overlayTarget).prepend(overlay);
                }
            }
        });
        //find associated button
        var btn = "button[data-livesearch-parent='" + mQuery(el).attr('id') + "']";
        if (mQuery(btn).length) {
            if (mQuery(el).val()) {
                mQuery(btn).attr('data-livesearch-action', 'clear');
                mQuery(btn + ' i').removeClass('fa-search').addClass('fa-eraser');
            } else {
                mQuery(btn).attr('data-livesearch-action', 'search');
                mQuery(btn + ' i').removeClass('fa-eraser').addClass('fa-search');
            }
            mQuery(btn).on('click', {'parent': mQuery(el).attr('id')}, function (event) {
                Mautic.filterList(event,
                    event.data.parent,
                    mQuery('#' + event.data.parent).attr('data-action'),
                    mQuery('#' + event.data.parent).attr('data-target'),
                    'liveCache',
                    mQuery(this).attr('data-livesearch-action')
                );
            });
        }
    },

    /**
     * Filters list based on search contents
     */
    filterList: function (e, elId, route, target, liveCacheVar, action) {
        if (typeof liveCacheVar == 'undefined') {
            liveCacheVar = "liveCache";
        }

        var el = mQuery('#' + elId);
        //only submit if the element exists, its a livesearch, or on button click
        if (el.length && (e.data.livesearch || mQuery(e.target).prop("tagName") == 'BUTTON')) {
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
            if (value && value in MauticVars[liveCacheVar]) {
                var response = {"newContent": MauticVars[liveCacheVar][value]};
                response.target = target;
                Mautic.processPageContent(response);
                MauticVars.searchIsActive = false;
            } else {
                //disable page loading bar
                MauticVars.showLoadingBar = false;

                mQuery.ajax({
                    url: route,
                    type: "GET",
                    data: el.attr('name') + "=" + encodeURIComponent(value) + '&tmpl=list',
                    dataType: "json",
                    success: function (response) {
                        //cache the response
                        if (response.newContent) {
                            MauticVars[liveCacheVar][value] = response.newContent;
                        }
                        //note the target to be updated
                        response.target = target;
                        Mautic.processPageContent(response);

                        MauticVars.searchIsActive = false;
                    },
                    error: function (request, textStatus, errorThrown) {
                        if (mauticEnv == 'dev') {
                            alert(errorThrown);
                        }
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

        //start icon spin
        Mautic.startIconSpinOnEvent(event);

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
                if (mauticEnv == 'dev') {
                    alert(errorThrown);
                }
            }
        });
    },

    /**
     * Adds active class to selected list item in left/right panel view
     * @param prefix
     * @param id
     */
    activateListItem: function(prefix,id) {
        mQuery('.page-list-item').removeClass('active');
        mQuery('#'+prefix+'-' + id).addClass('active');
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
    }
};//ApiBundle
Mautic.clientOnLoad = function (container) {
    if (mQuery(container + ' #list-search').length) {
        Mautic.activateSearchAutocomplete('list-search', 'api.client');
    }
};//AssetBundle
Mautic.assetOnLoad = function (container) {
    // if (mQuery(container + ' #list-search').length) {
    //     Mautic.activateSearchAutocomplete('list-search', 'asset.asset');
    // }

    if (mQuery(container + ' form[name="asset"]').length) {
       Mautic.activateCategoryLookup('asset', 'asset');
    }
};
//CampaignBundle

/**
 * Setup the campaign view
 *
 * @param container
 */
Mautic.campaignOnLoad = function (container) {
    if (mQuery(container + ' #list-search').length) {
        Mautic.activateSearchAutocomplete('list-search', 'campaign');
    }

    if (mQuery(container + ' form[name="campaign"]').length) {
        Mautic.activateCategoryLookup('campaign', 'campaign');
    }

    if (mQuery('#campaignEvents').length) {
        //make the fields sortable
        mQuery('#campaignEvents').nestedSortable({
            items: 'li',
            toleranceElement: '> div',
            isTree: true,
            placeholder: "campaign-event-placeholder",
            helper: function() {
                return mQuery('<div><i class="fa fa-lg fa-crosshairs"></i></div>');
            },
            cursorAt: {top: 15, left: 15},
            tabSize: 10,
            stop: function(i) {
                MauticVars.showLoadingBar = false;
                mQuery.ajax({
                    type: "POST",
                    url: mauticAjaxUrl + "?action=campaign:reorderCampaignEvents",
                    data: mQuery('#campaignEvents').nestedSortable("serialize")
                });
            }
        });

        mQuery('#campaignEvents .campaign-event-details').on('mouseover.campaignevents', function() {
            mQuery(this).find('.form-buttons').removeClass('hide');
        }).on('mouseout.campaignevents', function() {
            mQuery(this).find('.form-buttons').addClass('hide');
        });

        mQuery('#campaignEvents .campaign-event-details').on('dblclick.campaignevents', function() {
            mQuery(this).find('.btn-edit').first().click();
        });
    }
};

/**
 * Setup the campaign event view
 *
 * @param container
 * @param response
 */
Mautic.campaignEventOnLoad = function (container, response) {
    //new action created so append it to the form
    if (response.eventHtml) {
        var newHtml = response.eventHtml;
        var eventId = '#CampaignEvent_' + response.eventId;
        if (mQuery(eventId).length) {
            //replace content
            mQuery(eventId).replaceWith(newHtml);
            var newField = false;
        } else {
            //append content
            mQuery(newHtml).appendTo('#campaignEvents');
            var newField = true;
        }
        //activate new stuff
        mQuery(eventId + " a[data-toggle='ajax']").click(function (event) {
            event.preventDefault();
            return Mautic.ajaxifyLink(this, event);
        });

        //initialize ajax'd modals
        mQuery(eventId + " a[data-toggle='ajaxmodal']").on('click.ajaxmodal', function (event) {
            event.preventDefault();

            Mautic.ajaxifyModal(this, event);
        });

        //initialize tooltips
        mQuery(eventId + " *[data-toggle='tooltip']").tooltip({html: true});

        mQuery(eventId).off('.campaignevents');
        mQuery(eventId).on('mouseover.campaignevents', function() {
            mQuery(this).find('.form-buttons').removeClass('hide');
        }).on('mouseout.campaignevents', function() {
            mQuery(this).find('.form-buttons').addClass('hide');
        });
        mQuery(eventId).on('dblclick.campaignevents', function() {
            mQuery(this).find('.btn-edit').first().click();
        });

        //show events panel
        if (!mQuery('#events-panel').hasClass('in')) {
            mQuery('a[href="#events-panel"]').trigger('click');
        }

        if (mQuery('#campaign-event-placeholder').length) {
            mQuery('#campaign-event-placeholder').remove();
        }
    }
};

/**
 * Change the links in the available event list when the campaign type is changed
 */
Mautic.updateCampaignEventLinks = function () {
    //find and update all the event links with the campaign type

    var campaignType = mQuery('#campaign_type .active input').val();
    if (typeof campaignType == 'undefined') {
        campaignType = 'interval';
    }

    mQuery('#campaignEventList a').each(function () {
        var href    = mQuery(this).attr('href');
        var newType = (campaignType == 'interval') ? 'date' : 'interval';

        href = href.replace('campaignType=' + campaignType, 'campaignType=' + newType);
        mQuery(this).attr('href', href);
    });
};

/**
 * Enable/Disable timeframe settings if the toggle for immediate trigger is changed
 */
Mautic.campaignToggleTimeframes = function() {
    var immediateChecked = mQuery('#campaignevent_triggerMode_0').prop('checked');
    var intervalChecked  = mQuery('#campaignevent_triggerMode_1').prop('checked');
    var dateChecked      = mQuery('#campaignevent_triggerMode_2').prop('checked');

    if (mQuery('#campaignevent_triggerInterval').length) {
        if (immediateChecked) {
            mQuery('#campaignevent_triggerInterval').attr('disabled', true);
            mQuery('#campaignevent_triggerIntervalUnit').attr('disabled', true);
            mQuery('#campaignevent_triggerDate').attr('disabled', true);
        } else if (intervalChecked) {
            mQuery('#campaignevent_triggerInterval').attr('disabled', false);
            mQuery('#campaignevent_triggerIntervalUnit').attr('disabled', false);
            mQuery('#campaignevent_triggerDate').attr('disabled', true);
        } else if (dateChecked) {
            mQuery('#campaignevent_triggerInterval').attr('disabled', true);
            mQuery('#campaignevent_triggerIntervalUnit').attr('disabled', true);
            mQuery('#campaignevent_triggerDate').attr('disabled', false);
        }
    }
};/** CategoryBundle **/

Mautic.categoryOnLoad = function (container) {
    if (mQuery(container + ' #list-search').length) {
        Mautic.activateSearchAutocomplete('list-search', 'category');
    }
};

Mautic.activateCategoryLookup = function (formName, bundlePrefix) {
    //activate lookups
    if (mQuery('#'+formName+'_category_lookup').length) {
        var cats = new Bloodhound({
            datumTokenizer: Bloodhound.tokenizers.obj.whitespace('label'),
            queryTokenizer: Bloodhound.tokenizers.whitespace,
            prefetch: {
                url: mauticAjaxUrl + "?action=category:categoryList&bundle=" + bundlePrefix,
                ajax: {
                    beforeSend: function () {
                        MauticVars.showLoadingBar = false;
                    }
                }
            },
            remote: {
                url: mauticAjaxUrl + "?action=category:categoryList&bundle=" + bundlePrefix + "&filter=%QUERY",
                ajax: {
                    beforeSend: function () {
                        MauticVars.showLoadingBar = false;
                    }
                }
            },
            dupDetector: function (remoteMatch, localMatch) {
                return (remoteMatch.label == localMatch.label);
            },
            ttl: 1,
            limit: 10
        });
        cats.initialize();

        mQuery("#" + formName + "_category_lookup").typeahead(
            {
                hint: true,
                highlight: true,
                minLength: 2
            },
            {
                name: bundlePrefix + '_category',
                displayKey: 'label',
                source: cats.ttAdapter()
            }).on('typeahead:selected', function (event, datum) {
                mQuery("#" + formName + "_category").val(datum["value"]);
            }).on('typeahead:autocompleted', function (event, datum) {
                mQuery("#" + formName + "_category").val(datum["value"]);
            }).on('keypress', function (event) {
                if ((event.keyCode || event.which) == 13) {
                    mQuery('#' + formName + '_category_lookup').typeahead('close');
                }
            });
    }
}/* ChatBundle */

Mautic.activateChatListUpdate = function() {
    Mautic['chatListUpdaterInterval'] = setInterval(function() {
        if (mQuery('#ChatUsers').length) {
            Mautic.updateChatList();
        } else {
            clearInterval(Mautic['chatListUpdaterInterval']);
        }
    }, 30000);
};

Mautic.updateChatList = function (killTimer) {
    mQuery.ajax({
        type: "POST",
        url: mauticAjaxUrl + "?action=chat:updateList",
        dataType: "json",
        success: function (response) {
            mQuery('#ChatHeader').html('');
            mQuery('#ChatSubHeader').html('');

            mQuery('#ChatList').replaceWith(response.newContent);
            response.target = '#ChatList';
            Mautic.processPageContent(response);

            if (killTimer) {
                 clearInterval(Mautic['chatUpdaterInterval']);
            }
        },
        error: function (request, textStatus, errorThrown) {
            if (mauticEnv == 'dev') {
                alert(errorThrown);
            }
        }
    });
};

Mautic.startUserChat = function (userId, fromDate) {
    if (typeof fromDate == 'undefined') {
        fromDate = '';
    }

    mQuery.ajax({
        type: "POST",
        url: mauticAjaxUrl + "?action=chat:startUserChat",
        data: 'chatId=' + userId + '&from=' + fromDate,
        dataType: "json",
        success: function (response) {
            if (response.success) {
                mQuery('#ChatHeader').html(response.withName);
                if (response.lastSeen) {
                    mQuery('#ChatSubHeader').html(response.lastSeen);
                }

                Mautic.updateChatConversation(response);

                Mautic.activateChatUpdater(response.withId, 'user');
                Mautic.activateChatInput(response.withId, 'user');

                //activate links, etc
                response.target = ".offcanvas-right";
                Mautic.processPageContent(response);
            }
        },
        error: function (request, textStatus, errorThrown) {
            if (mauticEnv == 'dev') {
                alert(errorThrown);
            }
        }
    });
};

Mautic.startChannelChat = function (channelId, fromDate) {
    if (typeof fromDate == 'undefined') {
        fromDate = '';
    }

    mQuery.ajax({
        type: "POST",
        url: mauticAjaxUrl + "?action=chat:startChannelChat",
        data: 'chatId=' + channelId + '&from=' + fromDate,
        dataType: "json",
        success: function (response) {
            if (response.success) {
                mQuery('#ChatHeader').html(response.channelName);
                if (response.channelDesc) {
                    mQuery('#ChatSubHeader').html(response.channelDesc);
                }

                Mautic.updateChatConversation(response);
                Mautic.activateChatUpdater(response.channelId, 'channel');
                Mautic.activateChatInput(response.channelId, 'channel');

                //activate links, etc
                response.target = ".offcanvas-right";
                Mautic.processPageContent(response);
            }
        },
        error: function (request, textStatus, errorThrown) {
            if (mauticEnv == 'dev') {
                alert(errorThrown);
            }
        }
    });
};

Mautic.activateChatInput = function(itemId, chatType) {
    //activate enter key
    mQuery('#ChatMessageInput').off('keydown.chat');
    mQuery('#ChatMessageInput').on('keydown.chat', function(e) {
        if (e.which == 10 || e.which == 13) {
            //submit the text
            Mautic.sendChatMessage(itemId, chatType);
        }

        //remove new message marker
        if (mQuery('.chat-new-divider').length) {
            mQuery('.chat-new-divider').remove();
        }
    });

    mQuery('#ChatMessageInput').off('click.chat');
    mQuery('#ChatMessageInput').on('click.chat', function(e) {
        //remove new message marker
        if (mQuery('.chat-new-divider').length) {
            mQuery('.chat-new-divider').remove();
            Mautic.markMessagesRead(itemId, chatType);
        }
    });
};

Mautic.getLastChatGroup = function() {
    var group = mQuery('#ChatConversation .chat-group').last().find('.chat-group-firstid');
    return group.length ? group.val() : '';
};

Mautic.markMessagesRead = function(itemId, chatType) {
    var lastId  = mQuery('#ChatLastMessageId').val();
    mQuery.ajax({
        type: "POST",
        url: mauticAjaxUrl + "?action=chat:markRead",
        data: 'chatId=' + itemId + '&chatType=' + chatType + '&lastId=' + lastId,
        dataType: "json"
    });
};

Mautic.activateChatUpdater = function(itemId, chatType) {
    Mautic['chatUpdaterInterval'] = setInterval(function(){
        var lastId  = mQuery('#ChatLastMessageId').val();
        var groupId = Mautic.getLastChatGroup();

        //only update if not in a form or single chat
        if (mQuery('#ChatUsers').length) {
            mQuery.ajax({
                type: "POST",
                url: mauticAjaxUrl + "?action=chat:getMessages",
                data: 'chatId=' + itemId + '&chatType=' + chatType + '&lastId=' + lastId + '&groupId=' + groupId,
                dataType: "json",
                success: function (response) {
                    Mautic.updateChatConversation(response, chatType);
                },
                error: function (request, textStatus, errorThrown) {
                    if (mauticEnv == 'dev') {
                        alert(errorThrown);
                    }
                }
            });
        } else {
            //clear the interval
            clearInterval(Mautic['chatUpdateInterval']);
        }
    }, 10000);
};

Mautic.sendChatMessage = function(toId, chatType) {
    var msgText = mQuery('#ChatMessageInput').val();
    mQuery('#ChatMessageInput').val('');
    var lastId  = mQuery('#ChatLastMessageId').val();
    var groupId = Mautic.getLastChatGroup();

    if (msgText) {
        var dataObj = {
            chatId: toId,
            msg: msgText,
            lastId: lastId,
            groupId: groupId,
            chatType: chatType
        };
        mQuery.ajax({
            type: "POST",
            url: mauticAjaxUrl + "?action=chat:sendMessage",
            data: dataObj,
            dataType: "json",
            success: function (response) {
                Mautic.updateChatConversation(response, chatType);
            },
            error: function (request, textStatus, errorThrown) {
                if (mauticEnv == 'dev') {
                    alert(errorThrown);
                }
            }
        });
    }
};

Mautic.updateChatConversation = function(response, chatType)
{
    var dividerAppended = false;
    var contentUpdated  = false;

    //clear the chat list updater for now
    clearInterval(Mautic['chatListUpdaterInterval']);

    if (response.conversationHtml) {
        mQuery('#ChatConversation').html(response.conversationHtml);
    }

    if (response.firstId && mQuery('#ChatMessage' + response.firstId).length) {
        return;
    }

    var useId = (chatType == 'user') ? 'ChatWithUserId' : 'ChatChannelId';

    if (mQuery('#'+useId).length && mQuery('#'+useId).val() == response.withId) {
        if (!mQuery('.chat-new-divider').length && response.divider) {
            if (response.lastReadId && response.lastReadId != response.latestId && mQuery('#ChatMessage' + response.lastReadId).length) {
                dividerAppended = true;
                mQuery(response.divider).insertAfter('#ChatMessage' + response.lastReadId);
            }
        }

        if (response.appendToGroup) {
            if (!dividerAppended && !mQuery('.chat-new-divider').length && response.divider) {
                dividerAppended = true;
                mQuery('#ChatGroup' + response.groupId + ' .media-body').append(response.divider);
            }
            mQuery('#ChatGroup' + response.groupId + ' .media-body').append(response.appendToGroup);
            contentUpdated = true;
        }

        if (response.messages) {
            if (!dividerAppended && !mQuery('.chat-new-divider').length && response.divider) {
                mQuery('#ChatMessages').append(response.divider);
            }
            mQuery('#ChatMessages').append(response.messages);
            contentUpdated = true;
        }
    }

    if (contentUpdated) {
        //Scroll to bottom of chat (latest messages)
        mQuery('#ChatConversation').scrollTop(mQuery('#ChatConversation')[0].scrollHeight);
    }

    if (response.latestId) {
        var currentLastId = mQuery('#ChatLastMessageId').val();
        if (response.latestId > currentLastId) {
            //only update the latest ID if the given is higher than what's set incase JS gets a head of itself
            mQuery('#ChatLastMessageId').val(response.latestId);
        }
    }
};

Mautic.addChatChannel = function() {
    mQuery.ajax({
        type: "POST",
        url: mauticAjaxUrl + "?action=chat:addChannel",
        dataType: "json",
        success: function (response) {

        },
        error: function (request, textStatus, errorThrown) {
            if (mauticEnv == 'dev') {
                alert(errorThrown);
            }
        }
    });
};/** EmailBundle **/
Mautic.emailOnLoad = function (container) {
    if (mQuery(container + ' #list-search').length) {
        Mautic.activateSearchAutocomplete('list-search', 'email');
    }

    if (mQuery(container + ' form[name="emailform"]').length) {
        Mautic.activateCategoryLookup('emailform', 'email');
    }
};

Mautic.emailUnLoad = function() {
    //remove email builder from body
    mQuery('.email-builder').remove();
};

Mautic.launchEmailEditor = function () {
    var src = mQuery('#EmailBuilderUrl').val();
    src += '?template=' + mQuery('#emailform_template').val();

    var builder = mQuery("<iframe />", {
        css: {
            margin: "0",
            padding: "0",
            border: "none",
            width: "100%",
            height: "100%"
        },
        id: "builder-template-content"
    })
        .attr('src', src)
        .appendTo('.email-builder-content')
        .load(function () {
            var $this = mQuery(this);
            var contents = $this.contents();
            // here, catch the droppable div and create a droppable widget
            contents.find('.mautic-editable').droppable({
                iframeFix: true,
                drop: function (event, ui) {
                    var instance = mQuery(this).attr("id");
                    var editor   = document.getElementById('builder-template-content').contentWindow.CKEDITOR.instances;
                    var token = mQuery(ui.draggable).find('input.email-token').val();
                    editor[instance].insertText(token);
                    mQuery(this).removeClass('over-droppable');
                },
                over: function (e, ui) {
                    mQuery(this).addClass('over-droppable');
                },
                out: function (e, ui) {
                    mQuery(this).removeClass('over-droppable');
                }
            });
        });

    //Append to body to break out of the main panel
    mQuery('.email-builder').appendTo('body');
    //make the panel full screen
    mQuery('.email-builder').addClass('email-builder-active');
    //show it
    mQuery('.email-builder').removeClass('hide');

    Mautic.pageEditorOnLoad('.email-builder-panel');
};

Mautic.closeEmailEditor = function() {
    mQuery('.email-builder').addClass('hide');

    //make sure editors have lost focus so the content is updated
    mQuery('#builder-template-content').contents().find('.mautic-editable').each(function (index) {
        mQuery(this).blur();
    });

    setTimeout( function() {
        //kill the draggables
        mQuery('#builder-template-content').contents().find('.mautic-editable').droppable('destroy');
        mQuery("ul.draggable li").draggable('destroy');

        //kill the iframe
        mQuery('#builder-template-content').remove();

        //move the email builder back into form
        mQuery('.email-builder').appendTo('.bundle-main-inner-wrapper');
    }, 3000);
};

Mautic.emailEditorOnLoad = function (container) {
    //activate builder drag and drop
    mQuery(container + " ul.draggable li").draggable({
        iframeFix: true,
        iframeId: 'builder-template-content',
        helper: function() {
            return mQuery('<div><i class="fa fa-lg fa-crosshairs"></i></div>');
        },
        appendTo: '.email-builder',
        zIndex: 8000,
        scroll: true,
        scrollSensitivity: 100,
        scrollSpeed: 100,
        cursorAt: {top: 15, left: 15}
    });
};//FormBundle
Mautic.formOnLoad = function (container) {
    if (mQuery(container + ' #list-search').length) {
        Mautic.activateSearchAutocomplete('list-search', 'form.form');
    }

    if (mQuery(container + ' form[name="mauticform"]').length) {
        Mautic.activateCategoryLookup('mauticform', 'form');
    }

    if (mQuery('#mauticforms_fields')) {
        //make the fields sortable
        mQuery('#mauticforms_fields').sortable({
            items: '.mauticform-row',
            handle: '.reorder-handle',
            stop: function(i) {
                MauticVars.showLoadingBar = false;
                mQuery.ajax({
                    type: "POST",
                    url: mauticAjaxUrl + "?action=form:reorderFields",
                    data: mQuery('#mauticforms_fields').sortable("serialize")});
            }
        });

        mQuery('#mauticforms_fields .mauticform-row').on('mouseover.mauticformfields', function() {
           mQuery(this).find('.form-buttons').removeClass('hide');
        }).on('mouseout.mauticformfields', function() {
            mQuery(this).find('.form-buttons').addClass('hide');
        });
    }

    if (mQuery('#mauticforms_actions')) {
        //make the fields sortable
        mQuery('#mauticforms_actions').sortable({
            items: '.mauticform-row',
            handle: '.reorder-handle',
            stop: function(i) {
                MauticVars.showLoadingBar = false;
                mQuery.ajax({
                    type: "POST",
                    url: mauticAjaxUrl + "?action=form:reorderActions",
                    data: mQuery('#mauticforms_actions').sortable("serialize")});
            }
        });

        mQuery('#mauticforms_actions .mauticform-row').on('mouseover.mauticformactions', function() {
            mQuery(this).find('.form-buttons').removeClass('hide');
        }).on('mouseout.mauticformactions', function() {
            mQuery(this).find('.form-buttons').addClass('hide');
        });
    }
};

Mautic.formFieldOnLoad = function (container, response) {
    //new field created so append it to the form
    if (response.fieldHtml) {
        var newHtml = response.fieldHtml;
        var fieldId = '#mauticform_' + response.fieldId;
        if (mQuery(fieldId).length) {
            //replace content
            mQuery(fieldId).replaceWith(newHtml);
            var newField = false;
        } else {
            //append content
            mQuery(newHtml).appendTo('#mauticforms_fields');
            var newField = true;
        }
        //activate new stuff
        mQuery(fieldId + " a[data-toggle='ajax']").click(function (event) {
            event.preventDefault();
            return Mautic.ajaxifyLink(this, event);
        });
        //initialize tooltips
        mQuery(fieldId + " *[data-toggle='tooltip']").tooltip({html: true});

        //initialize ajax'd modals
        mQuery(fieldId + " a[data-toggle='ajaxmodal']").on('click.ajaxmodal', function (event) {
            event.preventDefault();

            Mautic.ajaxifyModal(this, event);
        });

        mQuery('#mauticforms_fields .mauticform-row').off(".mauticform");
        mQuery('#mauticforms_fields .mauticform-row').on('mouseover.mauticformfields', function() {
            mQuery(this).find('.form-buttons').removeClass('hide');
        }).on('mouseout.mauticformfields', function() {
            mQuery(this).find('.form-buttons').addClass('hide');
        });

        //show fields panel
        if (!mQuery('#fields-panel').hasClass('in')) {
            mQuery('a[href="#fields-panel"]').trigger('click');
        }

        if (newField) {
            mQuery('.bundle-main-inner-wrapper').scrollTop(mQuery('.bundle-main-inner-wrapper').height());
        }

        if (mQuery('#form-field-placeholder').length) {
            mQuery('#form-field-placeholder').remove();
        }
    }
};

Mautic.formActionOnLoad = function (container, response) {
    //new action created so append it to the form
    if (response.actionHtml) {
        var newHtml = response.actionHtml;
        var actionId = '#mauticform_action_' + response.actionId;
        if (mQuery(actionId).length) {
            //replace content
            mQuery(actionId).replaceWith(newHtml);
            var newField = false;
        } else {
            //append content
            mQuery(newHtml).appendTo('#mauticforms_actions');
            var newField = true;
        }
        //activate new stuff
        mQuery(actionId + " a[data-toggle='ajax']").click(function (event) {
            event.preventDefault();
            return Mautic.ajaxifyLink(this, event);
        });
        //initialize tooltips
        mQuery(actionId + " *[data-toggle='tooltip']").tooltip({html: true});

        //initialize ajax'd modals
        mQuery(actionId + " a[data-toggle='ajaxmodal']").on('click.ajaxmodal', function (event) {
            event.preventDefault();

            Mautic.ajaxifyModal(this, event);
        });

        mQuery('#mauticforms_actions .mauticform-row').off(".mauticform");
        mQuery('#mauticforms_actions .mauticform-row').on('mouseover.mauticformactions', function() {
            mQuery(this).find('.form-buttons').removeClass('hide');
        }).on('mouseout.mauticformactions', function() {
            mQuery(this).find('.form-buttons').addClass('hide');
        });

        //show actions panel
        if (!mQuery('#actions-panel').hasClass('in')) {
            mQuery('a[href="#actions-panel"]').trigger('click');
        }

        if (newField) {
            mQuery('.bundle-main-inner-wrapper').scrollTop(mQuery('.bundle-main-inner-wrapper').height());
        }

        if (mQuery('#form-action-placeholder').length) {
            mQuery('#form-action-placeholder').remove();
        }
    }
};

Mautic.onPostSubmitActionChange = function(value) {
    if (value == 'return') {
        //remove required class
        mQuery('#mauticform_postActionProperty').prev().removeClass('required');
    } else {
        mQuery('#mauticform_postActionProperty').prev().addClass('required');
    }

    mQuery('#mauticform_postActionProperty').next().html('');
    mQuery('#mauticform_postActionProperty').parent().removeClass('has-error');
};//LeadBundle
Mautic.leadOnLoad = function (container) {
    if (mQuery(container + ' form[name="lead"]').length) {
        Mautic.activateLeadOwnerTypeahead('lead_owner_lookup');

        mQuery("*[data-toggle='field-lookup']").each(function (index) {
            var target = mQuery(this).attr('data-target');
            var field  = mQuery(this).attr('id');
            var options = mQuery(this).attr('data-options');
            Mautic.activateLeadFieldTypeahead(field, target, options);
        });

        Mautic.updateLeadFieldProperties(mQuery('#leadfield_type').val());
    }

    Mautic.loadRemoteContentToModal('note-modal');

    if (mQuery(container + ' #list-search').length) {
        Mautic.activateSearchAutocomplete('list-search', 'lead.lead');
    }

    // Shuffle
    // ================================
    var grid   = mQuery("#shuffle-grid"),
        filter = mQuery("#shuffle-filter"),
        sizer  = grid.find("shuffle-sizer");

    // instatiate shuffle
    grid.shuffle({
        itemSelector: ".shuffle",
        sizer: sizer
    });

    // Filter options
    (function () {
        filter.on("keyup change", function () {
            var val = this.value.toLowerCase();
            grid.shuffle("shuffle", function (el, shuffle) {

                // Only search elements in the current group
                if (shuffle.group !== "all" && mQuery.inArray(shuffle.group, el.data("groups")) === -1) {
                    return false;
                }

                var text = mQuery.trim(el.find(".panel-body > h5").text()).toLowerCase();
                return text.indexOf(val) !== -1;
            });
        });
    })();

    // Update shuffle on sidebar minimize/maximize
    mQuery("html")
        .on("fa.sidebar.minimize", function () { grid.shuffle("update"); })
        .on("fa.sidebar.maximize", function () { grid.shuffle("update"); });
};

Mautic.activateLeadFieldTypeahead = function(field, target, options) {
    if (options) {
        //set to zero so the list shows from the start
        var taMinLength = 0;
        var keys = values = [];
        //check to see if there is a key/value split
        options = options.split('||');
        if (options.length == 2) {
            keys   = options[0].split('|');
            values = options[1].split('|');
        } else {
            values = options[0].split('|');
        }

        var substringMatcher = function(strs, strKeys) {
            return function findMatches(q, cb) {
                var matches, substringRegex;

                // an array that will be populated with substring matches
                matches = [];

                // regex used to determine if a string contains the substring `q`
                substrRegex = new RegExp(q, 'i');

                // iterate through the pool of strings and for any string that
                // contains the substring `q`, add it to the `matches` array
                mQuery.each(strs, function(i, str) {
                    if (substrRegex.test(str)) {
                        // the typeahead jQuery plugin expects suggestions to a
                        // JavaScript object, refer to typeahead docs for more info
                        if (strKeys.length && typeof strKeys[i] != 'undefined') {
                            matches.push({
                                value: str,
                                id:    strKeys[i]
                            });
                        } else {
                            matches.push({value: str});
                        }
                    }
                });

                cb(matches);
            };
        };

        var source = substringMatcher(values, keys);
    } else {
        //set the length to 2 so it requires at least 2 characters to search
        var taMinLength = 2;

        this[field] = new Bloodhound({
            datumTokenizer: Bloodhound.tokenizers.obj.whitespace('value'),
            queryTokenizer: Bloodhound.tokenizers.whitespace,
            prefetch: {
                url: mauticAjaxUrl + "?action=lead:fieldList&field=" + target,
                ajax: {
                    beforeSend: function () {
                        MauticVars.showLoadingBar = false;
                    }
                }
            },
            remote: {
                url: mauticAjaxUrl + "?action=lead:fieldList&field=" + target + "&filter=%QUERY",
                ajax: {
                    beforeSend: function () {
                        MauticVars.showLoadingBar = false;
                    }
                }
            },
            dupDetector: function (remoteMatch, localMatch) {
                return (remoteMatch.value == localMatch.value);
            },
            ttl: 1800000,
            limit: 5
        });
        this[field].initialize();
        var source = this[field].ttAdapter();
    }

    mQuery('#' + field).typeahead(
        {
            hint: true,
            highlight: true,
            minLength: taMinLength
        },
        {
            name: field,
            displayKey: 'value',
            source: source
        }
    ).on('typeahead:selected', function (event, datum) {
        if (mQuery("#" + field + "_id").length && datum["id"]) {
            mQuery("#" + field + "_id").val(datum["id"]);
        }
    }).on('typeahead:autocompleted', function (event, datum) {
        if (mQuery("#" + field + "_id").length && datum["id"]) {
            mQuery("#" + field + "_id").val(datum["id"]);
        }
    }).on('keypress', function (event) {
        if ((event.keyCode || event.which) == 13) {
            mQuery('#' + field).typeahead('close');
        }
    });
};

Mautic.activateLeadOwnerTypeahead = function(el) {
    var owners = new Bloodhound({
        datumTokenizer: Bloodhound.tokenizers.obj.whitespace('label'),
        queryTokenizer: Bloodhound.tokenizers.whitespace,
        prefetch: {
            url: mauticAjaxUrl + "?action=lead:userList",
            ajax: {
                beforeSend: function () {
                    MauticVars.showLoadingBar = false;
                }
            }
        },
        remote: {
            url: mauticAjaxUrl + "?action=lead:userList&filter=%QUERY",
            ajax: {
                beforeSend: function () {
                    MauticVars.showLoadingBar = false;
                }
            }
        },
        dupDetector: function (remoteMatch, localMatch) {
            return (remoteMatch.label == localMatch.label);
        },
        ttl: 1800000,
        limit: 5
    });
    owners.initialize();
    mQuery("#"  + el).typeahead(
        {
            hint: true,
            highlight: true,
            minLength: 2
        },
        {
            name: 'lead_owners',
            displayKey: 'label',
            source: owners.ttAdapter()
        }).on('typeahead:selected', function (event, datum) {
            if (mQuery("#lead_owner").length) {
                mQuery("#lead_owner").val(datum["value"]);
            }
        }).on('typeahead:autocompleted', function (event, datum) {
            if (mQuery("#lead_owner").length) {
                mQuery("#lead_owner").val(datum["value"]);
            }
        }
    ).on( 'focus', function() {
        mQuery(this).typeahead( 'open');
    });
};

Mautic.leadlistOnLoad = function(container) {
    if (mQuery(container + ' #list-search').length) {
        Mautic.activateSearchAutocomplete('list-search', 'lead.list');
    }

    mQuery('#leadlist_filters_left li').draggable({
        appendTo: "body",
        helper: "clone"
    });

    mQuery('#leadlist_filters_right').sortable({
        items: "li",
        handle: '.sortable-handle'
    });

    if (mQuery('#leadlist_filters_right').length) {
        mQuery('#leadlist_filters_right .remove-selected').each( function (index, el) {
            mQuery(el).on('click', function () {
                mQuery(this).parent().remove();
                if (!mQuery('#leadlist_filters_right li:not(.placeholder)').length) {
                    mQuery('#leadlist_filters_right li.placeholder').removeClass('hide');
                } else {
                    mQuery('#leadlist_filters_right li.placeholder').addClass('hide');
                }
            });
        });
    }

    mQuery("*[data-toggle='field-lookup']").each(function (index) {
        var target = mQuery(this).attr('data-target');
        var options = mQuery(this).attr('data-options');
        var field  = mQuery(this).attr('id');
        Mautic.activateLeadFieldTypeahead(field, target, options);
    });
};

Mautic.addLeadListFilter = function (elId) {
    var filterId = '#available_' + elId;
    var label  = mQuery(filterId + ' span.leadlist-filter-name').text();

    //create a new filter
    var li = mQuery("<div />").addClass("padding-sm").text(label).appendTo(mQuery('#leadlist_filters_right'));

    //add a delete button
    mQuery("<i />").addClass("fa fa-fw fa-trash-o remove-selected pull-right").prependTo(li).on('click', function() {
        mQuery(this).parent().remove();
    });

    //add a sortable handle
    mQuery("<i />").addClass("fa fa-fw fa-ellipsis-v sortable-handle pull-right").prependTo(li);

    var fieldType = mQuery(filterId).find("input.field_type").val();
    var alias     = mQuery(filterId).find("input.field_alias").val();

    //add wrapping div and add the template html

    var container = mQuery('<div />')
        .addClass('filter-container')
        .appendTo(li);

    if (fieldType == 'country' || fieldType == 'timezone') {
        container.html(mQuery('#filter-' + fieldType + '-template').html());
    } else {
        container.html(mQuery('#filter-template').html());
    }
    mQuery(container).find("input[name='leadlist[filters][field][]']").val(alias);
    mQuery(container).find("input[name='leadlist[filters][type][]']").val(fieldType);

    //give the value element a unique id
    var uniqid = "id_" + Date.now();
    var filter = mQuery(container).find("input[name='leadlist[filters][filter][]']");
    filter.attr('id', uniqid);

    //activate fields
    if (fieldType == 'lookup' || fieldType == 'select') {
        var fieldCallback = mQuery(filterId).find("input.field_callback").val();
        if (fieldCallback) {
            var fieldOptions = mQuery(filterId).find("input.field_list").val();
            Mautic[fieldCallback](uniqid, alias, fieldOptions);
        }
    } else if (fieldType == 'datetime') {
        filter.datetimepicker({
            format: 'Y-m-d H:i',
            lazyInit: true,
            validateOnBlur: false,
            allowBlank: true,
            scrollInput: false
        });
    } else if (fieldType == 'date') {
        filter.datetimepicker({
            timepicker: false,
            format: 'Y-m-d',
            lazyInit: true,
            validateOnBlur: false,
            allowBlank: true,
            scrollInput: false,
            closeOnDateSelect: true
        });
    } else if (fieldType == 'time') {
        filter.datetimepicker({
            datepicker: false,
            format: 'H:i',
            lazyInit: true,
            validateOnBlur: false,
            allowBlank: true,
            scrollInput: false
        });
    } else if (fieldType == 'lookup_id' || fieldType == 'boolean') {
        //switch the filter and display elements
        var oldFilter = mQuery(container).find("input[name='leadlist[filters][filter][]']");
        var newDisplay = oldFilter.clone();
        newDisplay.attr('id', uniqid);
        newDisplay.attr('name', 'leadlist[filters][display][]');

        var oldDisplay = mQuery(container).find("input[name='leadlist[filters][display][]']");
        var newFilter = oldDisplay.clone();
        newFilter.attr('id', uniqid + "_id");
        newFilter.attr('name', 'leadlist[filters][filter][]');

        oldFilter.replaceWith(newFilter);
        oldDisplay.replaceWith(newDisplay);

        var fieldCallback = mQuery(filterId).find("input.field_callback").val();
        if (fieldCallback) {
            var fieldOptions = mQuery(filterId).find("input.field_list").val();
            Mautic[fieldCallback](uniqid, alias, fieldOptions);
        }
    } else {
        filter.attr('type', fieldType);
    }
};

Mautic.leadfieldOnLoad = function (container) {

    var fixHelper = function(e, ui) {
        ui.children().each(function() {
            mQuery(this).width(mQuery(this).width());
        });
        return ui;
    };

    if (mQuery(container + ' .leadfield-list').length) {
        mQuery(container + ' .leadfield-list tbody').sortable({
            handle: '.fa-ellipsis-v',
            helper: fixHelper,
            stop: function(i) {
                mQuery.ajax({
                    type: "POST",
                    url: mauticAjaxUrl + "?action=lead:reorder",
                    data: mQuery(container + ' .leadfield-list tbody').sortable("serialize")});
            }
        });
    }

};

/**
 * Update the properties for field data types
 */
Mautic.updateLeadFieldProperties = function(selectedVal) {
    if (mQuery('#field-templates .'+selectedVal).length) {
        mQuery('#leadfield_properties').html(mQuery('#field-templates .'+selectedVal).html());

        mQuery("#leadfield_properties *[data-toggle='tooltip']").tooltip({html: true});
    } else {
        mQuery('#leadfield_properties').html('');
    }

    if (selectedVal == 'time') {
        mQuery('#leadfield_isListable').closest('.row').addClass('hide');
    } else {
        mQuery('#leadfield_isListable').closest('.row').removeClass('hide');
    }
};

Mautic.refreshLeadSocialProfile = function(network, leadId, event) {
    Mautic.startIconSpinOnEvent(event);
    var query = "action=lead:updateSocialProfile&network=" + network + "&lead=" + leadId;
    mQuery.ajax({
        url: mauticAjaxUrl,
        type: "POST",
        data: query,
        dataType: "json",
        success: function (response) {
            if (response.success) {
                //loop through each network
                mQuery.each(response.profiles, function( index, value ){
                    if (mQuery('#' + index + 'CompleteProfile').length) {
                        mQuery('#' + index + 'CompleteProfile').html(value.newContent);
                    }
                });
            }
            Mautic.stopIconSpinPostEvent(event);
        },
        error: function (request, textStatus, errorThrown) {
            if (mauticEnv == 'dev') {
                alert(errorThrown);
            }
            Mautic.stopIconSpinPostEvent(event);
        }
    });
};

Mautic.loadRemoteContentToModal = function(elementId) {
    mQuery('#'+elementId).on('loaded.bs.modal', function (e) {
        // take HTML content from JSON and place it back
        var remoteContent = mQuery.parseJSON(e.target.textContent).newContent;
        mQuery(this).find('.modal-content').html(remoteContent);

        var modalForm = mQuery(this).find('form');

        // form submit
        modalForm.ajaxForm({
            beforeSubmit: function(formData) {
                // disable buttons while sending data
                modalForm.find('button').prop('disabled', true);

                // show work in progress
                Mautic.showModalAlert('<i class="fa fa-spinner fa-spin"></i> Saving...', 'info');

                // cancel form if cancel button was hit
                var submitForm = true;
                mQuery.each(formData, function( index, value ) {
                    if (value.type === 'submit' && value.name.indexOf('[buttons][cancel]') >= 0) {
                        submitForm = false;
                    }
                });

                if (submitForm) {
                    return true;
                } else {
                    mQuery('#'+elementId).modal('hide');
                }
            },
            success: function(response) {
                modalForm.find('button').prop('disabled', false);
                Mautic.showModalAlert('Saved successfully.', 'success');
            },
            error: function(response) {
                modalForm.find('button').prop('disabled', false);
                Mautic.showModalAlert(response.statusText, 'danger');
            }
        });
    });
};

Mautic.showModalAlert = function(msg, type) {
    mQuery('.alert-modal').hide('fast').remove();
    mQuery('.bottom-form-buttons')
        .before('<div class="alert alert-modal alert-'+type+'" role="alert">'+msg+'</div>')
        .hide().show('fast');
};

Mautic.toggleLeadList = function(toggleId, leadId, listId) {
    var toggleOn  = 'fa-toggle-on text-success';
    var toggleOff = 'fa-toggle-off text-danger';

    var action = mQuery('#' + toggleId).hasClass('fa-toggle-on') ? 'remove' : 'add';
    var query = "action=lead:toggleLeadList&leadId=" + leadId + "&listId=" + listId + "&listAction=" + action;

    if (action == 'remove') {
        //switch it on
        mQuery('#' + toggleId).removeClass(toggleOn).addClass(toggleOff);
    } else {
        mQuery('#' + toggleId).removeClass(toggleOff).addClass(toggleOn);
    }

    mQuery.ajax({
        url: mauticAjaxUrl,
        type: "POST",
        data: query,
        dataType: "json",
        success: function (response) {
            if (!response.success) {
                //return the icon back
                if (action == 'remove') {
                    //switch it on
                    mQuery('#' + toggleId).addClass(toggleOff).addClass(toggleOn);
                } else {
                    mQuery('#' + toggleId).removeClass(toggleOn).addClass(toggleOff);
                }
            }
        },
        error: function (request, textStatus, errorThrown) {
            //return the icon back
            if (action == 'remove') {
                //switch it on
                mQuery('#' + toggleId).addClass(toggleOff).addClass(toggleOn);
            } else {
                mQuery('#' + toggleId).removeClass(toggleOn).addClass(toggleOff);
            }
        }
    });
};

Mautic.toggleLeadCampaign = function(toggleId, leadId, campaignId) {
    var toggleOn  = 'fa-toggle-on text-success';
    var toggleOff = 'fa-toggle-off text-danger';

    var action = mQuery('#' + toggleId).hasClass('fa-toggle-on') ? 'remove' : 'add';
    var query = "action=lead:toggleLeadCampaign&leadId=" + leadId + "&campaignId=" + campaignId + "&campaignAction=" + action;

    if (action == 'remove') {
        //switch it on
        mQuery('#' + toggleId).removeClass(toggleOn).addClass(toggleOff);
    } else {
        mQuery('#' + toggleId).removeClass(toggleOff).addClass(toggleOn);
    }

    mQuery.ajax({
        url: mauticAjaxUrl,
        type: "POST",
        data: query,
        dataType: "json",
        success: function (response) {
            if (!response.success) {
                //return the icon back
                if (action == 'remove') {
                    //switch it on
                    mQuery('#' + toggleId).addClass(toggleOff).addClass(toggleOn);
                } else {
                    mQuery('#' + toggleId).removeClass(toggleOn).addClass(toggleOff);
                }
            }
        },
        error: function (request, textStatus, errorThrown) {
            //return the icon back
            if (action == 'remove') {
                //switch it on
                mQuery('#' + toggleId).addClass(toggleOff).addClass(toggleOn);
            } else {
                mQuery('#' + toggleId).removeClass(toggleOn).addClass(toggleOff);
            }
        }
    });
};

Mautic.leadNoteOnLoad = function (container, response) {
    if (response.noteHtml) {
        var el = '#LeadNote' + response.noteId;
        if (mQuery(el).length) {
            mQuery(el).replaceWith(response.noteHtml);
        } else {
            mQuery('#notes-container ul.events').prepend(response.noteHtml);
        }

        //initialize ajax'd modals
        mQuery(el + " *[data-toggle='ajaxmodal']").off('click.ajaxmodal');
        mQuery(el + " *[data-toggle='ajaxmodal']").on('click.ajaxmodal', function (event) {
            event.preventDefault();

            Mautic.ajaxifyModal(this, event);
        });

        //initiate links
        mQuery(el + " a[data-toggle='ajax']").off('click.ajax');
        mQuery(el + " a[data-toggle='ajax']").on('click.ajax', function (event) {
            event.preventDefault();

            return Mautic.ajaxifyLink(this, event);
        });
    } else if (response.deleteId && mQuery('#LeadNote' + response.deleteId).length) {
        mQuery('#LeadNote' + response.deleteId).remove();
    }
};

//PageBundle
Mautic.pageOnLoad = function (container) {
    if (mQuery(container + ' #list-search').length) {
        Mautic.activateSearchAutocomplete('list-search', 'page.page');
    }

    if (mQuery(container + ' form[name="page"]').length) {
       Mautic.activateCategoryLookup('page', 'page');
    }
};

Mautic.pageUnLoad = function() {
    //remove page builder from body
    mQuery('.page-builder').remove();
};

Mautic.launchPageEditor = function () {
    var src = mQuery('#pageBuilderUrl').val();
    src += '?template=' + mQuery('#page_template').val();

    var builder = mQuery("<iframe />", {
        css: {
            margin: "0",
            padding: "0",
            border: "none",
            width: "100%",
            height: "100%"
        },
        id: "builder-template-content"
    })
        .attr('src', src)
        .appendTo('.page-builder-content')
        .load(function () {
            var $this = mQuery(this);
            var contents = $this.contents();
            // here, catch the droppable div and create a droppable widget
            contents.find('.mautic-editable').droppable({
                iframeFix: true,
                drop: function (event, ui) {
                    var instance = mQuery(this).attr("id");
                    var editor   = document.getElementById('builder-template-content').contentWindow.CKEDITOR.instances;
                    var token = mQuery(ui.draggable).find('input.page-token').val();
                    editor[instance].insertText(token);
                    mQuery(this).removeClass('over-droppable');
                },
                over: function (e, ui) {
                    mQuery(this).addClass('over-droppable');
                },
                out: function (e, ui) {
                    mQuery(this).removeClass('over-droppable');
                }
            });
        });

    //Append to body to break out of the main panel
    mQuery('.page-builder').appendTo('body');
    //make the panel full screen
    mQuery('.page-builder').addClass('page-builder-active');
    //show it
    mQuery('.page-builder').removeClass('hide');

    Mautic.pageEditorOnLoad('.page-builder-panel');
};

Mautic.closePageEditor = function() {
    mQuery('.page-builder').addClass('hide');

    //make sure editors have lost focus so the content is updated
    mQuery('#builder-template-content').contents().find('.mautic-editable').each(function (index) {
        mQuery(this).blur();
    });

    setTimeout( function() {
        //kill the draggables
        mQuery('#builder-template-content').contents().find('.mautic-editable').droppable('destroy');
        mQuery("ul.draggable li").draggable('destroy');

        //kill the iframe
        mQuery('#builder-template-content').remove();

        //move the page builder back into form
        mQuery('.page-builder').appendTo('.bundle-main-inner-wrapper');
    }, 3000);
};

Mautic.pageEditorOnLoad = function (container) {
    //activate builder drag and drop
    mQuery(container + " ul.draggable li").draggable({
        iframeFix: true,
        iframeId: 'builder-template-content',
        helper: function() {
            return mQuery('<div><i class="fa fa-lg fa-crosshairs"></i></div>');
        },
        appendTo: '.page-builder',
        zIndex: 8000,
        scroll: true,
        scrollSensitivity: 100,
        scrollSpeed: 100,
        cursorAt: {top: 15, left: 15}
    });
};//PointBundle
Mautic.pointOnLoad = function (container) {
    if (mQuery(container + ' #list-search').length) {
        Mautic.activateSearchAutocomplete('list-search', 'point');
    }

    if (mQuery(container + ' form[name="point"]').length) {
        Mautic.activateCategoryLookup('point', 'point');
    }
};

Mautic.pointTriggerOnLoad = function (container) {
    if (mQuery(container + ' #list-search').length) {
        Mautic.activateSearchAutocomplete('list-search', 'point.trigger');
    }

    if (mQuery(container + ' form[name="pointtrigger"]').length) {
        Mautic.activateCategoryLookup('pointtrigger', 'point');
    }

    if (mQuery('#triggerEvents')) {
        //make the fields sortable
        mQuery('#triggerEvents').sortable({
            items: '.trigger-event-row',
            handle: '.reorder-handle',
            stop: function(i) {
                MauticVars.showLoadingBar = false;
                mQuery.ajax({
                    type: "POST",
                    url: mauticAjaxUrl + "?action=point:reorderTriggerEvents",
                    data: mQuery('#triggerEvents').sortable("serialize")});
            }
        });

        mQuery('#triggerEvents .trigger-event-row').on('mouseover.triggerevents', function() {
            mQuery(this).find('.form-buttons').removeClass('hide');
        }).on('mouseout.triggerevents', function() {
            mQuery(this).find('.form-buttons').addClass('hide');
        });
    }
};

Mautic.pointTriggerEventLoad = function (container, response) {
    //new action created so append it to the form
    if (response.actionHtml) {
        var newHtml = response.actionHtml;
        var actionId = '#triggerEvent' + response.actionId;
        if (mQuery(actionId).length) {
            //replace content
            mQuery(actionId).replaceWith(newHtml);
            var newField = false;
        } else {
            //append content
            mQuery(newHtml).appendTo('#triggerEvents');
            var newField = true;
        }
        //activate new stuff
        mQuery(actionId + " a[data-toggle='ajax']").click(function (event) {
            event.preventDefault();
            return Mautic.ajaxifyLink(this, event);
        });
        //initialize tooltips
        mQuery(actionId + " *[data-toggle='tooltip']").tooltip({html: true});

        mQuery('#triggerEvents .trigger-event-row').off(".triggerevents");
        mQuery('#triggerEvents .trigger-event-row').on('mouseover.triggerevents', function() {
            mQuery(this).find('.form-buttons').removeClass('hide');
        }).on('mouseout.triggerevents', function() {
            mQuery(this).find('.form-buttons').addClass('hide');
        });

        //show events panel
        if (!mQuery('#events-panel').hasClass('in')) {
            mQuery('a[href="#events-panel"]').trigger('click');
        }

        if (mQuery('#trigger-event-placeholder').length) {
            mQuery('#trigger-event-placeholder').remove();
        }
    }
};

Mautic.getPointActionPropertiesForm = function(actionType) {
    var labelSpinner = mQuery("label[for='point_type']");
    var spinner = mQuery('<i class="fa fa-fw fa-spinner fa-spin"></i>');
    labelSpinner.append(spinner);
    var query = "action=point:getActionForm&actionType=" + actionType;
    mQuery.ajax({
        url: mauticAjaxUrl,
        type: "POST",
        data: query,
        dataType: "json",
        success: function (response) {
            if (typeof response.html != 'undefined') {
                mQuery('#pointActionProperties').html(response.html);
                Mautic.onPageLoad('#pointActionProperties', response);
            }
            spinner.remove();
        },
        error: function (request, textStatus, errorThrown) {
            if (mauticEnv == 'dev') {
                alert(errorThrown);
            }
            spinner.remove();
        }
    });
};//ReportBundle
Mautic.reportOnLoad = function (container) {
	// Activate search if the container exists
	if (mQuery(container + ' #list-search').length) {
		Mautic.activateSearchAutocomplete('list-search', 'reportOnLoad');
	}

	// Append an index of the number of filters on the edit form
	if (mQuery('div[id=report_filters]').length) {
		mQuery('div[id=report_filters]').data('index', mQuery('#report_filters > div').length);
	}
};

Mautic.preprocessSaveReportForm = function(form) {
	var selectedColumns = mQuery(form + ' #report_columns');

	mQuery(selectedColumns).find('option').each(function($this) {
		mQuery(this).attr('selected', 'selected');
	});
};

Mautic.moveReportColumns = function(fromSelect, toSelect) {
	mQuery('#' + fromSelect + ' option:selected').remove().appendTo('#' + toSelect);

	mQuery('#' + toSelect).find('option').each(function($this) {
		mQuery(this).prop('selected', false);
	});
};

Mautic.reorderColumns = function(select, direction) {
	var options = mQuery('#' + select + ' option:selected');

	if (options.length) {
		(direction == 'up') ? options.first().prev().before(options) : options.last().next().after(options);
	}
};

/**
 * Written with inspiration from http://symfony.com/doc/current/cookbook/form/form_collections.html#allowing-new-tags-with-the-prototype
 */
Mautic.addFilterRow = function() {
	// Container with the prototype markup
	var prototypeHolder = mQuery('div[id=report_filters]');

	// Fetch the index
	var index = prototypeHolder.data('index');

	// Fetch the prototype markup
	var prototype = prototypeHolder.data('prototype');

	// Replace the placeholder with our index
	var output = prototype.replace(/__name__/g, index);

	// Increase the index for the next row
	prototypeHolder.data('index', index + 1);

	// Render the new row
	prototypeHolder.append(output);
};

Mautic.removeFilterRow = function(container) {
	mQuery('#' + container).remove();
};

Mautic.updateColumnList = function () {
	var table = mQuery('select[id=report_source] option:selected').val();

	mQuery.ajax({
		type: "POST",
		url: mauticAjaxUrl + "?action=report:updateColumns",
		data: {table: table},
		dataType: "json",
		success: function (response) {
			// Remove the existing options in the column display section
			mQuery('#report_columns_available').find('option').remove().end();
			mQuery('#report_columns').find('option').remove().end();

			// Append the new options into the select list
			mQuery.each(response.columns, function(key, value) {
				mQuery('#report_columns_available')
					.append(mQuery('<option>')
						.attr('value', key)
						.text(value));
			});

			// Remove any filters, they're no longer valid with different column lists
			mQuery('#report_filters').find('div').remove().end();

			// TODO - Need to parse the prototype and replace the options in the column list for filters too
		},
		error: function (request, textStatus, errorThrown) {
			if (mauticEnv == 'dev') {
				alert(errorThrown);
			}
		}
	});
};

Mautic.checkReportCondition = function(selector) {
	var option = mQuery('#' + selector + ' option:selected').val();
	var valueInput = selector.replace('condition', 'value');

	// Disable the value input if the condition is empty or notEmpty
	if (option == 'empty' || option == 'notEmpty') {
		mQuery('#' + valueInput).prop('disabled', true);
	} else {
		mQuery('#' + valueInput).prop('disabled', false);
	}
};
/* SocialBundle */

Mautic.loadAuthModal = function(url, keyType, network, popupMsg) {
    //get the key needed if required
    var base = '#socialmedia_config_services_'+network+'_apiKeys_';

    if (keyType) {
        var apiKey = mQuery(base+keyType).val();

        //replace the placeholder
        url = url.replace('{'+keyType+'}', apiKey);
    }

    var generator = window.open(url, 'socialauth','height=400,width=500');

    if(!generator || generator.closed || typeof generator.closed=='undefined') {
        alert(popupMsg);
    }
};

/**
 *
 * @param network
 * @param token
 * @param code
 * @param callbackUrl
 */
Mautic.handleCallback = function (network, token, code, callbackUrl) {
    //get the keys
    var base = '#socialmedia_config_services_' + network + '_apiKeys_';

    var clientId = window.opener.mQuery(base + 'clientId').val();
    var clientSecret = window.opener.mQuery(base + 'clientSecret').val();

    //perform callback
    var query = 'clientId=' + clientId +
        '&clientSecret=' + clientSecret +
        '&code=' + code +
        '&state=' + token +
        '&' + network + '_csrf_token=' + token;

    mQuery.ajax({
        url: callbackUrl,
        type: "POST",
        data: query,
        dataType: "json",
        success: function (response) {
            window.location = response.url;
        },
        error: function (request, textStatus, errorThrown) {
            if (mauticEnv == 'dev') {
                alert(errorThrown);
            }
        }
    });
}//UserBundle
Mautic.userOnLoad = function (container) {
    if (mQuery(container + ' form[name="user"]').length) {
        if (mQuery('#user_role_lookup').length) {
            var roles = new Bloodhound({
                datumTokenizer: Bloodhound.tokenizers.obj.whitespace('label'),
                queryTokenizer: Bloodhound.tokenizers.whitespace,
                prefetch: {
                    url: mauticAjaxUrl + "?action=user:roleList",
                    ajax: {
                        beforeSend: function () {
                            MauticVars.showLoadingBar = false;
                        }
                    }
                },
                remote: {
                    url: mauticAjaxUrl + "?action=user:roleList&filter=%QUERY",
                    ajax: {
                        beforeSend: function () {
                            MauticVars.showLoadingBar = false;
                        }
                    }
                },
                dupDetector: function (remoteMatch, localMatch) {
                    return (remoteMatch.label == localMatch.label);
                },
                ttl: 1800000,
                limit: 5
            });
            roles.initialize();

            mQuery("#user_role_lookup").typeahead(
                {
                    hint: true,
                    highlight: true,
                    minLength: 2
                },
                {
                    name: 'user_role',
                    displayKey: 'label',
                    source: roles.ttAdapter()
                }).on('typeahead:selected', function (event, datum) {
                    mQuery("#user_role").val(datum["value"]);
                }).on('typeahead:autocompleted', function (event, datum) {
                    mQuery("#user_role").val(datum["value"]);
                }).on('keypress', function (event) {
                    if ((event.keyCode || event.which) == 13) {
                        mQuery('#user_role_lookup').typeahead('close');
                    }
                });
        }
        if (mQuery('#user_position').length) {
            var positions = new Bloodhound({
                datumTokenizer: Bloodhound.tokenizers.obj.whitespace('value'),
                queryTokenizer: Bloodhound.tokenizers.whitespace,
                prefetch: {
                    url: mauticAjaxUrl + "?action=user:positionList"
                },
                remote: {
                    url: mauticAjaxUrl + "?action=user:positionList&filter=%QUERY"
                },
                dupDetector: function (remoteMatch, localMatch) {
                    return (remoteMatch.label == localMatch.label);
                },
                ttl: 1800000,
                limit: 5
            });
            positions.initialize();
            mQuery("#user_position").typeahead(
                {
                    hint: true,
                    highlight: true,
                    minLength: 2
                },
                {
                    name: 'user_position',
                    displayKey: 'value',
                    source: positions.ttAdapter()
                }
            );
        }
    } else {
        if (mQuery(container + ' #list-search').length) {
            Mautic.activateSearchAutocomplete('list-search', 'user.user');
        }
    }
};

Mautic.roleOnLoad = function (container, response) {
    if (mQuery(container + ' #list-search').length) {
        Mautic.activateSearchAutocomplete('list-search', 'user.role');
    }

    if (response && response.permissionList) {
        MauticVars.permissionList = response.permissionList;
    }
};

/**
 * Toggles permission panel visibility for roles
 */
Mautic.togglePermissionVisibility = function () {
    //add a very slight delay in order for the clicked on checkbox to be selected since the onclick action
    //is set to the parent div
    setTimeout(function () {
        if (mQuery('#role_isAdmin_0').prop('checked')) {
            mQuery('#permissions-container').removeClass('hide');
        } else {
            mQuery('#permissions-container').addClass('hide');
        }
    }, 10);
};

/**
 * Toggle permissions, update ratio, etc
 *
 * @param container
 * @param event
 * @param bundle
 */
Mautic.onPermissionChange = function (container, event, bundle) {
    //add a very slight delay in order for the clicked on checkbox to be selected since the onclick action
    //is set to the parent div
    setTimeout(function () {
        var granted = 0;
        var clickedBox = mQuery(event.target).find('input:checkbox').first();
        if (mQuery(clickedBox).prop('checked')) {
            if (mQuery(clickedBox).val() == 'full') {
                //uncheck all of the others
                mQuery(container).find("label input:checkbox:checked").map(function () {
                    if (mQuery(this).val() != 'full') {
                        mQuery(this).prop('checked', false);
                        mQuery(this).parent().toggleClass('active');
                    }
                })
            } else {
                //uncheck full
                mQuery(container).find("label input:checkbox:checked").map(function () {
                    if (mQuery(this).val() == 'full') {
                        granted = granted - 1;
                        mQuery(this).prop('checked', false);
                        mQuery(this).parent().toggleClass('active');
                    }
                })
            }
        }

        //update granted numbers
        if (mQuery('.' + bundle + '_granted').length) {
            var granted = 0;
            var levelPerms = MauticVars.permissionList[bundle];
            mQuery.each(levelPerms, function(level, perms) {
                mQuery.each(perms, function(index, perm) {
                    if (perm == 'full') {
                        if (mQuery('#role_permissions_' + bundle + '\\:' + level + '_' + perm).prop('checked')) {
                            if (perms.length === 1)
                                granted++;
                            else
                                granted += perms.length - 1;
                        }
                    } else {
                        if (mQuery('#role_permissions_' + bundle + '\\:' + level + '_' + perm).prop('checked'))
                            granted++;
                    }
                });
            });
            mQuery('.' + bundle + '_granted').html(granted);
        }
    }, 10);
};