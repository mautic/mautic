var MauticVars = {};
//window.localStorage.clear();
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
MauticVars.liveCache = new Array();
MauticVars.lastSearchStr = "";
MauticVars.globalLivecache = new Array();
MauticVars.lastGlobalSearchStr  = "";

//register the loading bar for ajax page loads
MauticVars.showLoadingBar = true;
$.ajaxSetup({
    beforeSend: function () {
        if (MauticVars.showLoadingBar) {
            $("body").addClass("loading-content");
        }
    },
    cache: false,
    xhr: function () {
        var xhr = new window.XMLHttpRequest();
        if (MauticVars.showLoadingBar) {
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
        if (MauticVars.showLoadingBar) {
            setTimeout(function () {
                $("body").removeClass("loading-content");
                $(".loading-bar .progress-bar").attr('aria-valuenow', 0);
                $(".loading-bar .progress-bar").css('width', "0%");
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
        $(container + " a[data-toggle='ajax']").click(function (event) {
            event.preventDefault();

            return Mautic.ajaxifyLink(this, event);
        });

        //initialize forms
        $(container + " form[data-toggle='ajax']").each(function (index) {
            Mautic.ajaxifyForm($(this).attr('name'));
        });

        $(container + " *[data-toggle='livesearch']").each(function (index) {
            Mautic.activateLiveSearch($(this), "lastSearchStr", "liveCache");
        });

        //initialize tooltips
        $(container + " *[data-toggle='tooltip']").tooltip({html: true, container: 'body'});

        //initialize sortable lists
        $(container + " *[data-toggle='sortablelist']").each(function (index) {
            var prefix = $(this).attr('data-prefix');

            if ($('#' + prefix + '_additem').length) {
                $('#' + prefix + '_additem').click(function () {
                    var count = $('#' + prefix + '_itemcount').val();
                    var prototype = $('#' + prefix + '_additem').attr('data-prototype');
                    prototype = prototype.replace(/__name__/g, count);
                    $(prototype).appendTo($('#' + prefix + '_list div.list-sortable'));
                    $('#' + prefix + '_list_' + count).focus();
                    count++;
                    $('#' + prefix + '_itemcount').val(count);
                    return false;
                });
            }

            $('#' + prefix + '_list div.list-sortable').sortable({
                items: 'div.sortable',
                handle: 'span.postaddon',
                stop: function (i) {
                    var order = 0;
                    $('#' + prefix + '_list div.list-sortable div.input-group input').each(function () {
                        var name = $(this).attr('name');
                        name = name.replace(/\[list\]\[(.+)\]$/g, '') + '[list][' + order + ']';
                        $(this).attr('name', name);
                        order++;
                    });
                }
            });
        });

        $(container + " a[data-toggle='download']").click(function (event) {
            event.preventDefault();

            var link = $(event.target).attr('href');

            //initialize download links
            var iframe = $("<iframe/>").attr({
                src: link,
                style: "visibility:hidden;display:none"
            }).appendTo($('body'));
        });

        //little hack to move modal windows outside of positioned divs
        $(container + " *[data-toggle='modal']").each(function (index) {
            var target = $(this).attr('data-target');
            $(target).on('show.bs.modal', function () {
                $(target).appendTo("body");
            });
        });

        //initialize date/time
        $(container + " *[data-toggle='datetime']").datetimepicker({
            format: 'Y-m-d H:i',
            lazyInit: true,
            validateOnBlur: false,
            allowBlank: true,
            scrollInput: false
        });

        $(container + " *[data-toggle='date']").datetimepicker({
            timepicker: false,
            format: 'Y-m-d',
            lazyInit: true,
            validateOnBlur: false,
            allowBlank: true,
            scrollInput: false,
            closeOnDateSelect: true
        });

        $(container + " *[data-toggle='time']").datetimepicker({
            datepicker: false,
            format: 'H:i',
            lazyInit: true,
            validateOnBlur: false,
            allowBlank: true,
            scrollInput: false
        });

        //Set the height of containers
        var windowHeight = $(window).height() - 175;
        $(container + ' .auto-height').each(function (index) {
            //set height of divs
            $(this).css('height', windowHeight + 'px');
        });

        //Activate editors
        $(container + " textarea[data-toggle='editor']").each(function (index) {
            $(this).tinymce({
                theme: "modern",
                height: 300,
                editor_deselector: "mceNoEditor",
                plugins: [
                    "advlist autolink link image lists charmap print preview hr anchor pagebreak spellchecker",
                    "searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime media nonbreaking",
                    "save table contextmenu directionality emoticons template paste textcolor"
                ],
                toolbar: "insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image | print preview media fullpage | forecolor backcolor emoticons"
            });
        });

        //Copy form buttons to the toolbar
        if ($(container + " .bottom-form-buttons").length) {
            //hide the toolbar actions if applicable
            $('.toolbar-action-buttons').addClass('hide');

            var buttons = $(container + " .bottom-form-buttons").html();
            $(buttons).filter("button").each(function(i, v) {
                //get the ID
                var id = $(this).attr('id');
                $(this).attr('id', '');
                $(this).attr('name', '');
                $(this).appendTo('.toolbar-form-buttons');
                $(this).click( function(event) {
                    event.preventDefault();
                    $('#' + id).click();
                });
            });
        }

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
                    url: mauticBaseUrl + "ajax?action=globalCommandList"
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
                MauticVars.lastGlobalSearchStr = '';
                $('#global_search').keyup();
            }).on('typeahead:autocompleted', function (event, datum) {
                //force live search update
                MauticVars.lastGlobalSearchStr = '';
                $('#global_search').keyup();
            });

            Mautic.activateLiveSearch("#global_search", "lastGlobalSearchStr", "globalLivecache");
        }
    },

    /**
     * Functions to be ran on ajax page unload
     */
    onPageUnload: function (container, response) {
        //unload tooltips so they don't double show
        container = typeof container !== 'undefined' ? container : 'body';

        $(container + " *[data-toggle='tooltip']").tooltip('destroy');

        //unload tinymce editor so that it can be reloaded if needed with new ajax content
        $(container + " textarea[data-toggle='editor']").each(function (index) {
            $(this).tinymce().remove();
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
            var hasBtn = $(event.target).hasClass('btn');
            var hasIcon = $(event.target).hasClass('fa');
            if ((hasBtn && $(event.target).find('i.fa').length) || hasIcon) {
                MauticVars.iconButton = (hasIcon) ? event.target :  $(event.target).find('i.fa').first();
                MauticVars.iconClassesRemoved = $(MauticVars.iconButton).attr('class');
                $(MauticVars.iconButton).removeClass();
                $(MauticVars.iconButton).addClass('fa fa-spinner fa-spin');
            }
        }

        $.ajax({
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

                        if ($(".page-wrapper").hasClass("right-active")) {
                            $(".page-wrapper").removeClass("right-active");
                        }
                        Mautic.processPageContent(response);
                    }

                    //restore button class if applicable
                    if (typeof MauticVars.iconClassesRemoved != 'undefined') {
                        if ($(MauticVars.iconButton).hasClass('fa-spin')) {
                            $(MauticVars.iconButton).removeClass('fa fa-spinner fa-spin').addClass(MauticVars.iconClassesRemoved);
                        }
                        delete MauticVars.iconButton;
                        delete MauticVars.iconClassesRemoved;
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
    toggleSubMenu: function (link, event) {
        if ($(link).length) {
            //get the parent li element
            var parent = $(link).parent();
            var child = $(parent).find("ul").first();
            if (child.length) {
                var toggle = event.target;

                if (child.hasClass("subnav-closed")) {
                    //open the submenu
                    child.removeClass("subnav-closed").addClass("subnav-open");
                    $(toggle).removeClass("fa-toggle-left").addClass("fa-toggle-down");
                } else if (child.hasClass("subnav-open")) {
                    //close the submenu
                    child.removeClass("subnav-open").addClass("subnav-closed");
                    $(toggle).removeClass("fa-toggle-down").addClass("fa-toggle-left");
                }
            }
        }
    },

    /**
     * Posts a form and returns the output
     * @param form
     * @param callback
     */
    postForm: function (form, callback) {
        var action = form.attr('action');
       // var ajaxRoute = action + ((/\?/i.test(action)) ? "&ajax=1" : "?ajax=1");
        $.ajax({
            type: form.attr('method'),
            url: action,
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
        if (response) {
            if (!response.target) {
                response.target = '.main-panel-content';
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
                if (response.replaceContent) {
                    $(response.target).replaceWith(response.newContent);
                } else {
                    $(response.target).html(response.newContent);
                }
            }

            //update breadcrumbs
            if (response.breadcrumbs) {
                $(".main-panel-breadcrumbs").html(response.breadcrumbs);
            }

            //update latest flashes
            if (response.flashes) {
                $(".main-panel-flash-msgs").html(response.flashes);

                //ajaxify links
                $(".main-panel-flash-msgs a[data-toggle='ajax']").click(function (event) {
                    event.preventDefault();

                    return Mautic.ajaxifyLink(this, event);
                });

                window.setTimeout(function() {
                    $(".main-panel-flash-msgs .alert").fadeTo(500, 0).slideUp(500, function(){
                        $(this).remove();
                    });
                }, 10000);
            }

            if (response.activeLink) {
                //remove current classes from menu items
                $(".side-panel-nav").find(".current").removeClass("current");

                //remove ancestor classes
                $(".side-panel-nav").find(".current_ancestor").removeClass("current_ancestor");

                var link = response.activeLink;
                if (link !== undefined && link.charAt(0) != '#') {
                    link = "#" + link;
                }

                //add current class
                var parent = $(link).parent();
                $(parent).addClass("current");

                //add current_ancestor classes
                $(parent).parentsUntil(".side-panel-nav", "li").addClass("current_ancestor");
            }

            //close sidebar if necessary
            if ($(".left-side-bar-pin i").hasClass("unpinned") && !$(".page-wrapper").hasClass("hide-left")) {
                $(".page-wrapper").addClass("hide-left");
            }

            //scroll to the top
            if (response.target == '.main-panel-content') {
                $('.main-panel-wrapper').animate({
                    scrollTop: 0
                }, 0);
            } else {
                var overflow = $(response.target).css('overflow');
                var overflowY = $(response.target).css('overflowY');
                if (overflow == 'auto' || overflow == 'scroll' || overflowY == 'auto' || overflowY == 'scroll') {
                    $(response.target).animate({
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

                //give an ajaxified form the option of not displaying the global loading bar
                var loading = $(this).attr('data-hide-loadingbar');
                if (loading) {
                    MauticVars.showLoadingBar = false;
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

    ajaxifyLink: function (el, event) {
        //prevent leaving if currently in a form
        if ($(".prevent-nonsubmit-form-exit").length) {
            if ($(el).attr('data-ignore-formexit') != 'true') {
                Mautic.showConfirmation($(".prevent-nonsubmit-form-exit").val());
                return false;
            }
        }

        var route = $(el).attr('href');
        if (route.indexOf('javascript')>=0) {
            return false;
        }

        var link = $(el).attr('data-menu-link');
        if (link !== undefined && link.charAt(0) != '#') {
            link = "#" + link;
        }

        var method = $(el).attr('data-method');
        if (!method) {
            method = 'GET'
        }

        //give an ajaxified link the option of not displaying the global loading bar
        var loading = $(el).attr('data-hide-loadingbar');
        if (loading) {
            MauticVars.showLoadingBar = false;
        }

        Mautic.loadContent(route, link, method, null, event);
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
        var query = "action=togglePanel&panel=" + position;
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

        if (typeof confirmText == 'undefined') {
            confirmText   = '<i class="fa fa-fw fa-2x fa-check"></i>';
            confirmAction = 'dismissConfirmation';
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
                    window["Mautic"][confirmAction].apply('window', confirmParams);
                }
            })
            .html(confirmText);
        if (cancelText) {
            var cancelButton = $('<button type="button" />')
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
    reorderTableData: function (name, orderby, tmpl, target) {
        var query = "action=setTableOrder&name=" + name + "&orderby=" + orderby;
        $.ajax({
            url: mauticBaseUrl + 'ajax',
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
                alert(errorThrown);
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
        $.ajax({
            url: mauticBaseUrl + 'ajax',
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
                alert(errorThrown);
            }
        });
    },

    limitTableData: function (name, limit, tmpl, target) {
        var query = "action=setTableLimit&name=" + name + "&limit=" + limit;
        $.ajax({
            url: mauticBaseUrl + 'ajax',
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
                    url: mauticBaseUrl + "ajax?action=commandList&model=" + modelName
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
                    MauticVars.lastSearchStr = '';
                    $('#' + elId).keyup();
                }
            }).on('typeahead:autocompleted', function (event, datum) {
                if (livesearch) {
                    //force live search update
                    MauticVars.lastSearchStr = '';
                    $('#' + elId).keyup();
                }
            });
        }
    },

    activateLiveSearch: function(el, searchStrVar, liveCacheVar) {
        $(el).on('keyup', {}, function (event) {
            var searchStr = $(el).val().trim();
            var diff = searchStr.length - MauticVars[searchStrVar].length;
            var overlay = $('<div />', {"class": "content-overlay"}).html($(el).attr('data-overlay-text'));
            if ($(el).attr('data-overlay-background')) {
                overlay.css('background', $(el).attr('data-overlay-background'));
            }
            if ($(el).attr('data-overlay-color')) {
                overlay.css('color', $(el).attr('data-overlay-color'));
            }
            var target = $(el).attr('data-target');
            if (
                searchStr in MauticVars[liveCacheVar] ||
                diff >= 3 ||
                event.which == 32 || event.keyCode == 32 ||
                event.which == 13 || event.keyCode == 13
            ) {
                $(target + ' .content-overlay').remove();
                MauticVars[searchStrVar] = searchStr;
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
            if (value && value in MauticVars[liveCacheVar]) {
                var response = {"newContent": MauticVars[liveCacheVar][value]};
                response.target = target;
                Mautic.processPageContent(response);
            } else {
                //disable page loading bar
                MauticVars.showLoadingBar = false;

                $.ajax({
                    url: route,
                    type: "GET",
                    data: el.attr('name') + "=" + encodeURIComponent(value) + '&tmpl=content',
                    dataType: "json",
                    success: function (response) {
                        //cache the response
                        if (response.newContent) {
                            MauticVars[liveCacheVar][value] = response.newContent;
                        }
                        //note the target to be updated
                        response.target = target;
                        Mautic.processPageContent(response);
                    },
                    error: function (request, textStatus, errorThrown) {
                        alert(errorThrown);
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
        var sortableDiv = $(el).parents('div.sortable');
        var inputCount  = $(sortableDiv).parents('div.form-group').find('input.sortable-itemcount');
        var count = $(inputCount).val();
        count--;
        $(inputCount).val(count);
        $(sortableDiv).remove();
    },

    /**
     * Toggles published status of an entity
     *
     * @param el
     * @param model
     * @param id
     */
    togglePublishStatus: function (el, model, id) {
        MauticVars.showLoadingBar = false;
        $.ajax({
            url: mauticBaseUrl + "ajax",
            type: "POST",
            data: "action=togglePublishStatus&model=" + model + '&id=' + id,
            dataType: "json",
            success: function (response) {
                if (response.statusHtml) {
                    $(el).replaceWith(response.statusHtml);
                    $('.publish-icon'+id).tooltip({html: true, container: 'body'});
                }
            },
            error: function (request, textStatus, errorThrown) {
                alert(errorThrown);
            }
        });
    },

    /**
     * Adds active class to selected list item in left/right panel view
     * @param prefix
     * @param id
     */
    activateListItem: function(prefix,id) {
        $('.bundle-list-item').removeClass('active');
        $('#'+prefix+'-' + id).addClass('active');
    },

    /**
     * Expand right panel
     * @param el
     */
    expandPanel: function(el) {
        $(el).toggleClass('fullpanel');
    }

};

//prevent page navigation if in the middle of a form
window.addEventListener("beforeunload", function (e) {
    if ($(".prevent-nonsubmit-form-exit").length) {
        var msg = $(".prevent-nonsubmit-form-exit").val();

        (e || window.event).returnValue = msg;     //Gecko + IE
        return msg;                                //Webkit, Safari, Chrome etc.
    }
});