var mauticVars = {};

//Fix for back/forward buttons not loading ajax content with History.pushState()
mauticVars.manualStateChange = true;
History.Adapter.bind(window, 'statechange', function () {
    if (mauticVars.manualStateChange == true) {
        //back/forward button pressed
        window.location.reload();
    }
    mauticVars.manualStateChange = true;
});

//live search vars
mauticVars.liveCache = new Array();
mauticVars.lastSearchStr = "";
mauticVars.globalLivecache = new Array();
mauticVars.lastGlobalSearchStr  = "";

//register the loading bar for ajax page loads
mauticVars.showLoadingBar = true;
$.ajaxSetup({
    beforeSend: function () {
        if (mauticVars.showLoadingBar) {
            $("body").addClass("loading-content");
        }
    },
    xhr: function () {
        var xhr = new window.XMLHttpRequest();
        if (mauticVars.showLoadingBar) {
            xhr.upload.addEventListener("progress", function (evt) {
                if (evt.lengthComputable) {
                    var percentComplete = Math.round((evt.loaded / evt.total) * 100);
                    $(".loading-bar .progress-bar").attr('aria-valuenow', percentComplete);
                    $(".loading-bar .progress-bar").css('width', percentComplete + "%");
                }
            }, false);
            xhr.addEventListener("progress", function (evt) {
                if (evt.lengthComputable) {
                    var percentComplete = Math.round((evt.loaded / evt.total) * 100);
                    $(".loading-bar .progress-bar").attr('aria-valuenow', percentComplete);
                    $(".loading-bar .progress-bar").css('width', percentComplete + "%");
                }
            }, false);
        }
        return xhr;
    },
    complete: function () {
        if (mauticVars.showLoadingBar) {
            setTimeout(function () {
                $("body").removeClass("loading-content");
                $(".loading-bar .progress-bar").attr('aria-valuenow', 0);
                $(".loading-bar .progress-bar").css('width', "0%");
            }, 500);
        } else {
            //change default back to show
            mauticVars.showLoadingBar = true;
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
        $(container + " a[data-toggle='ajax']").click(function (event) {
            event.preventDefault();

            var route = $(this).attr('href');
            if (route.contains('javascript')) {
                return false;
            }

            var link = $(this).attr('data-menu-link');
            if (link !== undefined && link.charAt(0) != '#') {
                link = "#" + link;
            }
            var toggleMenu = false;
            if ($(this).attr('data-toggle-submenu')) {
                toggleMenu = ($(this).attr('data-toggle-submenu') == 'true') ? true : false;
            }

            Mautic.loadContent(route, link, toggleMenu);

        });

        //initialize forms
        $(container + " form[data-toggle='ajax']").each(function (index) {
            Mautic.ajaxifyForm($(this).attr('name'));
        });

        //initialize tooltips
        $(container + " *[data-toggle='tooltip']").tooltip({html: true});

        $(container + " *[data-toggle='livesearch']").each(function (index) {
            Mautic.activateLiveSearch($(this), "lastSearchStr", "liveCache");
        });

        //run specific on loads
        if (typeof Mautic[mauticContent + "OnLoad"] == 'function') {
            Mautic[mauticContent + "OnLoad"](container, response);
        }

        if (container == 'body') {
            //activate global live search
            var engine = new Bloodhound({
                datumTokenizer: Bloodhound.tokenizers.obj.whitespace('value'),
                queryTokenizer: Bloodhound.tokenizers.whitespace,
                prefetch: {
                    url: mauticBaseUrl + "ajax?ajaxAction=globalcommandlist"
                }
            });
            engine.initialize();

            $('#global_search').typeahead({
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
                mauticVars.lastGlobalSearchStr = '';
                $('#global_search').keyup();
            }).on('typeahead:autocompleted', function (event, datum) {
                //force live search update
                mauticVars.lastGlobalSearchStr = '';
                $('#global_search').keyup();
            });

            Mautic.activateLiveSearch("#global_search", "lastGlobalSearchStr", "globalLivecache");
        }
    },

    /**
     * Functions to be ran on ajax page unload
     */
    onPageUnload: function (container, response) {
        container = typeof container !== 'undefined' ? container : 'body';
        $(container + " *[data-toggle='tooltip']").tooltip('destroy');


        //run specific unloads
        if (typeof Mautic[mauticContent + "OnUnload"] == 'function') {
            Mautic[mauticContent + "OnUnload"](container, response);
        }
    },

    /**
     * Takes a given route, retrieves the HTML, and then updates the content
     * @param route
     * @param link
     * @param toggleMenu
     */
    loadContent: function (route, link, toggleMenu, mainContentOnly) {
        //keep browser backbutton from loading cached ajax response
        var ajaxRoute = route + ((/\?/i.test(route)) ? "&ajax=1" : "?ajax=1");
        $.ajax({
            url: ajaxRoute,
            type: "GET",
            dataType: "json",
            success: function (response) {
                //clear the live cache
                mauticVars.liveCache = new Array();
                mauticVars.lastSearchStr = '';

                if (response) {
                    if (mainContentOnly) {
                        if (response.newContent) {
                            Mautic.onPageUnload('.main-panel-content', response);
                            $(".main-panel-content").html(response.newContent);
                            Mautic.onPageLoad('.main-panel-content', response);
                        }
                    } else {
                        //set route and activeLink if the response didn't override
                        if (!response.route) {
                            response.route = route;
                        }
                        if (!response.activeLink) {
                            response.activeLink = link;
                        }
                        response.toggleMenu = toggleMenu;

                        if ($(".page-wrapper").hasClass("right-active")) {
                            $(".page-wrapper").removeClass("right-active");
                        }

                        Mautic.processPageContent(response);
                    }
                }
            },
            error: function (request, textStatus, errorThrown) {
                alert(errorThrown);
            }
        });

        //prevent firing of href link
        //$(link).attr("href", "javascript: void(0)");
        return false;
    },

    /**
     * Opens or closes submenus in main navigation
     * @param link
     */
    toggleSubMenu: function (link) {
        if ($(link).length) {
            //get the parent li element
            var parent = $(link).parent();
            var child = $(parent).find("ul").first();
            if (child.length) {
                var toggle = $(link).find(".subnav-toggle i");

                if (child.hasClass("subnav-closed")) {
                    //open the submenu
                    child.removeClass("subnav-closed").addClass("subnav-open");
                    toggle.removeClass("fa-toggle-left").addClass("fa-toggle-down");
                } else if (child.hasClass("subnav-open")) {
                    //close the submenu
                    child.removeClass("subnav-open").addClass("subnav-closed");
                    toggle.removeClass("fa-toggle-down").addClass("fa-toggle-left");
                }
            }

            //prevent firing of href link
            $(link).attr("href", "javascript: void(0)");
        }
    },

    /**
     * Posts a form and returns the output
     * @param form
     * @param callback
     */
    postForm: function (form, callback) {
        var action = form.attr('action');
        var ajaxRoute = action + ((/\?/i.test(action)) ? "&ajax=1" : "?ajax=1");
        $.ajax({
            type: form.attr('method'),
            url: ajaxRoute,
            data: form.serialize(),
            dataType: "json",
            success: function (data) {
                callback(data);
            },
            error: function (request, textStatus, errorThrown) {
                alert(errorThrown);
            }
        });
    },

    /**
     * Updates new content
     * @param response
     */
    processPageContent: function (response) {
        if (response && response.newContent) {
            //inactive tooltips, etc
            Mautic.onPageUnload('.main-panel-wrapper');

            if (response.route) {
                //update URL in address bar
                mauticVars.manualStateChange = false;
                History.pushState(null, "Mautic", response.route);
            }

            if (!response.target) {
                response.target = '.main-panel-content';
            }

            //set content
            $(response.target).html(response.newContent);

            //update breadcrumbs
            if (response.breadcrumbs) {
                $(".main-panel-breadcrumbs").html(response.breadcrumbs);
            }

            //update latest flashes
            if (response.flashes) {
                $(".main-panel-flash-msgs").html(response.flashes);
            }

            if (response.activeLink) {
                //remove current classes from menu items
                $(".side-panel-nav").find(".current").removeClass("current");

                //remove ancestor classes
                $(".side-panel-nav").find(".current_ancestor").removeClass("current_ancestor");

                var link = response.activeLink;
                //add current class
                var parent = $(link).parent();
                $(parent).addClass("current");

                //add current_ancestor classes
                $(parent).parentsUntil(".side-panel-nav", "li").addClass("current_ancestor");

                //toggle submenu if applicable
                if (response.toggleMenu) {
                    Mautic.toggleSubMenu(link);
                } else {
                    //close any submenus not part of the current tree
                    $(".side-panel-nav").find(".subnav-open:not(:has(li.current) )").each(
                        function (index, element) {
                            var link = $(this).parent().find('a').first();
                            Mautic.toggleSubMenu($(link));
                        }
                    );
                }
            }

            //close sidebar if necessary
            if ($(".left-side-bar-pin i").hasClass("unpinned") && !$(".page-wrapper").hasClass("hide-left")) {
                $(".page-wrapper").addClass("hide-left");
            }

            //scroll to the top of the main panel
            $('.main-panel-wrapper').animate({
                scrollTop: 0
            }, 0);

            //update type of content displayed
            if (response.mauticContent) {
                mauticContent = response.mauticContent;
            }

            //active tooltips, etc
            Mautic.onPageLoad(response.target, response);
        }
    },

    /**
     * Processes a response from an ajax call to update a specific element (not the entire page)
     *
     * @param response
     */
    processContentSection: function (response) {
        if (response.target && response.newContent) {
            Mautic.onPageUnload(response.target, response);
            $(response.target).html(response.newContent);
            Mautic.onPageLoad(response.target, response);
        }
    },

    /**
     * Prepares form for ajax submission
     * @param form
     */
    ajaxifyForm: function (formName) {
        //activate the submit buttons so symfony knows which were clicked
        $('form[name="' + formName + '"] :submit').each(function () {
            $(this).click(function () {
                if ($(this).attr('name')) {
                    $('form[name="' + formName + '"]').append(
                        $("<input type='hidden'>").attr({
                            name: $(this).attr('name'),
                            value: $(this).attr('value') })
                    );
                }
            });
        });
        //activate the forms
        $('form[name="' + formName + '"]').submit(function (e) {
            e.preventDefault();

            Mautic.postForm($(this), function (response) {
                Mautic.processPageContent(response);
            });

            return false;
        });
    },

    /**
     * Show/hide side panels
     * @param position
     */
    toggleSidePanel: function (position) {
        //spring the right panel back into place after clicking elsewhere
        if (position == "right") {
            //toggle active state
            $(".page-wrapper").toggleClass("right-active");
            //prevent firing event multiple times if directly toggling the panel
            $(".main-panel-wrapper").off("click");
            $(".main-panel-wrapper").click(function (e) {
                e.preventDefault();
                if ($(".page-wrapper").hasClass("right-active")) {
                    $(".page-wrapper").removeClass("right-active");
                }
                //prevent firing event multiple times
                $(".main-panel-wrapper").off("click");
            });

            $(".top-panel").off("click");
            $(".top-panel").click(function (e) {
                if (!$(e.target).parents('.panel-toggle').length) {
                    //dismiss the panel if clickng anywhere in the top panel except the toggle button
                    e.preventDefault();
                    if ($(".page-wrapper").hasClass("right-active")) {
                        $(".page-wrapper").removeClass("right-active");
                    }
                    //prevent firing event multiple times
                    $(".top-panel").off("click");
                }
            });

        } else {
            //toggle hidden state
            $(".page-wrapper").toggleClass("hide-left");
        }
    },

    /**
     * Stick a side panel
     * @param position
     */
    stickSidePanel: function (position) {
        var query = "ajaxAction=togglepanel&panel=" + position;
        $.ajax({
            url: mauticBaseUrl + "ajax",
            type: "POST",
            data: query,
            dataType: "json"
        });

        if (position == "left") {
            $(".left-side-bar-pin i").toggleClass("unpinned");

            //auto collapse the left side panel
            if ($(".left-side-bar-pin i").hasClass("unpinned")) {
                //prevent firing event multiple times if directly toggling the panel
                $(".main-panel-wrapper").off("click");
                $(".main-panel-wrapper").click(function (e) {
                    e.preventDefault();
                    if (!$(".page-wrapper").hasClass("hide-left")) {
                        $(".page-wrapper").addClass("hide-left");
                    }
                    //prevent firing event multiple times
                    $(".main-panel-wrapper").off("click");
                });

                $(".top-panel").off("click");
                $(".top-panel").click(function (e) {
                    if (!$(e.target).parents('.panel-toggle').length) {
                        //dismiss the panel if clickng anywhere in the top panel except the toggle button
                        e.preventDefault();
                        if (!$(".page-wrapper").hasClass("hide-left")) {
                            $(".page-wrapper").addClass("hide-left");
                        }
                        //prevent firing event multiple times
                        $(".top-panel").off("click");
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
        var confirmContainer = $("<div />").attr({ "class": "confirmation-modal" });
        var confirmInnerDiv = $("<div />").attr({ "class": "confirmation-inner-wrapper"});
        var confirmMsgSpan = $("<span />").css("display", "block").html(msg);
        var confirmButton = $('<button type="button" />')
            .addClass("btn btn-danger btn-xs")
            .css("marginRight", "5px")
            .css("marginLeft", "5px")
            .click(function () {
                if (typeof Mautic[confirmAction] === "function") {
                    window["Mautic"][confirmAction].apply('widnow', confirmParams);
                }
            })
            .html(confirmText);
        var cancelButton = $('<button type="button" />')
            .addClass("btn btn-primary btn-xs")
            .click(function () {
                if (typeof Mautic[cancelAction] === "function") {
                    window["Mautic"][cancelAction].apply('widnow', cancelParams);
                }
            })
            .html(cancelText);

        confirmInnerDiv.append(confirmMsgSpan);
        confirmInnerDiv.append(confirmButton);
        confirmInnerDiv.append(cancelButton);
        confirmContainer.append(confirmInnerDiv);
        $('body').append(confirmContainer)
    },

    /**
     * Dismiss confirmation modal
     */
    dismissConfirmation: function () {
        if ($('.confirmation-modal').length) {
            $('.confirmation-modal').remove();
        }
    },

    /**
     * Reorder table data
     * @param name
     * @param orderby
     */
    reorderTableData: function (name, orderby) {
        var query = "ajaxAction=setorderby&name=" + name + "&orderby=" + orderby;
        $.ajax({
            url: mauticBaseUrl + 'ajax',
            type: "POST",
            data: query,
            dataType: "json",
            success: function (response) {
                if (response.success) {
                    var route = window.location.pathname;
                    Mautic.loadContent(route, '', false, true);
                }
            },
            error: function (request, textStatus, errorThrown) {
                alert(errorThrown);
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
        $.ajax({
            url: action,
            type: "POST",
            dataType: "json",
            success: function (response) {
                Mautic.processPageContent(response);
            },
            error: function (request, textStatus, errorThrown) {
                alert(errorThrown);
            }
        });
    },

    /**
     * Shows the search search input in an search list
     */
    showSearchInput: function () {
        if ($('.toolbar').length) {
            $('.toolbar').addClass('show-search').removeClass('hide-search');
        }
    },

    /**
     * Hides the search search input in an search list
     */
    hideSearchInput: function (elId) {
        if ($('.toolbar').length && $('#' + elId).length && !$('#' + elId).val() && !$('#' + elId).is(":focus")) {
            $('.toolbar').addClass('hide-search').removeClass('show-search');
        }
    },

    /**
     * Activates Typeahead.js command lists for search boxes
     * @param elId
     * @param modelName
     */
    activateSearchAutocomplete: function (elId, modelName) {
        if ($('#' + elId).length) {
            var livesearch = ($('#' + elId).attr("data-toggle=['livesearch']")) ? true : false;

            var engine = new Bloodhound({
                datumTokenizer: Bloodhound.tokenizers.obj.whitespace('value'),
                queryTokenizer: Bloodhound.tokenizers.whitespace,
                prefetch: {
                    url: mauticBaseUrl + "ajax?ajaxAction=commandlist&model=" + modelName
                }
            });
            engine.initialize();

            $('#' + elId).typeahead({
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
                    mauticVars.lastSearchStr = '';
                    $('#' + elId).keyup();
                }
            }).on('typeahead:autocompleted', function (event, datum) {
                if (livesearch) {
                    //force live search update
                    mauticVars.lastSearchStr = '';
                    $('#' + elId).keyup();
                }
            });
        }
    },

    activateLiveSearch: function(el, searchStrVar, liveCacheVar) {
        $(el).on('keyup', {}, function (event) {
            var searchStr = $(el).val().trim();
            var diff = searchStr.length - mauticVars[searchStrVar].length;
            var overlay = $('<div />', {"class": "content-overlay"}).html($(el).attr('data-overlay-text'));
            if ($(el).attr('data-overlay-background')) {
                overlay.css('background', $(el).attr('data-overlay-background'));
            }
            if ($(el).attr('data-overlay-color')) {
                overlay.css('color', $(el).attr('data-overlay-color'));
            }
            var target = $(el).attr('data-target');
            if (
                searchStr in mauticVars[liveCacheVar] ||
                diff >= 3 ||
                event.which == 32 || event.keyCode == 32 ||
                event.which == 13 || event.keyCode == 13
            ) {
                $(target + ' .content-overlay').remove();
                mauticVars[searchStrVar] = searchStr;
                event.data.livesearch = true;
                Mautic.filterList(event, $(el).attr('id'), $(el).attr('data-action'), target, liveCacheVar);
            } else {
                if (!$(target + ' .content-overlay').length) {
                    $(target).prepend(overlay);
                }
            }
        });
        //find associated button
        var btn = "button[data-livesearch-parent='" + $(el).attr('id') + "']";
        if ($(btn).length) {
            if ($(el).val()) {
                $(btn).attr('data-livesearch-action', 'clear');
                $(btn + ' i').removeClass('fa-search').addClass('fa-eraser');
            } else {
                $(btn).attr('data-livesearch-action', 'search');
                $(btn + ' i').removeClass('fa-eraser').addClass('fa-search');
            }
            $(btn).on('click', {'parent': $(el).attr('id')}, function (event) {
                Mautic.filterList(event,
                    event.data.parent,
                    $('#' + event.data.parent).attr('data-action'),
                    $('#' + event.data.parent).attr('data-target'),
                    'liveCache',
                    $(this).attr('data-livesearch-action')
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

        var el = $('#' + elId);
        //only submit if the element exists, its a livesearch, or on button click
        if (el.length && (e.data.livesearch || $(e.target).prop("tagName") == 'BUTTON')) {
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
            if ($(btn).length) {
                if (action == 'clear') {
                    $(btn).attr('data-livesearch-action', 'search');
                    $(btn).children('i').first().removeClass('fa-eraser').addClass('fa-search');
                } else {
                    $(btn).attr('data-livesearch-action', 'clear');
                    $(btn).children('i').first().removeClass('fa-search').addClass('fa-eraser');
                }
            }

            //make the request
            if (value && value in mauticVars[liveCacheVar]) {
                var response = {"newContent": mauticVars[liveCacheVar][value]};
                response.target = target;
                Mautic.processContentSection(response);
            } else {
                //disable page loading bar
                mauticVars.showLoadingBar = false;

                $.ajax({
                    url: route,
                    type: "POST",
                    data: el.attr('name') + "=" + encodeURIComponent(value) + '&tmpl=content',
                    dataType: "json",
                    success: function (response) {
                        //cache the response
                        if (response.newContent) {
                            mauticVars[liveCacheVar][value] = response.newContent;
                        }
                        //note the target to be updated
                        response.target = target;
                        Mautic.processContentSection(response);
                    },
                    error: function (request, textStatus, errorThrown) {
                        alert(errorThrown);
                    }
                });
            }
        }
    }
};