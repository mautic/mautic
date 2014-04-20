var Mautic = {
    /**
     * Takes a given route, retrieves the HTML, and then updates the content
     * @param route
     * @param link
     * @param toggleMenu
     */
    loadContent: function (route, link, toggleMenu, mainContentOnly) {
        $("body").addClass("loading-content");

        $.ajax({
            url: route,
            type: "GET",
            dataType: "json",
            success: function(response){
                if (response) {
                    if (mainContentOnly) {
                        if (response.newContent) {
                            $(".main-panel-content").html(response.newContent);
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

                        Mautic.processContent(response);
                    }
                }
            },
            error: function(request, textStatus, errorThrown) {
                alert(errorThrown);
                $("body").removeClass("loading-content");
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
    toggleSubMenu: function(link) {
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
        $("body").addClass("loading-content");
        $.ajax({
            type: form.attr('method'),
            url: form.attr('action'),
            data: form.serialize(),
            dataType: "json",
            success: function (data) {
                callback(data);
            },
            error: function(request, textStatus, errorThrown) {
                alert(errorThrown);
                $("body").removeClass("loading-content");
            }
        });
    },

    /**
     * Updates new content
     * @param response
     */
    processContent: function (response) {
        if (response && response.newContent) {
            if (response.route) {
                //update URL in address bar
                History.pushState(null, "Mautic", response.route);
            }

            //get content
            $(".main-panel-content").html(response.newContent);
            $(".main-panel-breadcrumbs").html(response.breadcrumbs);

            //update latest flashes
            $(".main-panel-flash-msgs").html(response.flashes);

            //remove current classes from menu items
            $(".side-panel-nav").find(".current").removeClass("current");

            //remove ancestor classes
            $(".side-panel-nav").find(".current_ancestor").removeClass("current_ancestor");

            if (response.activeLink) {
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

            //ajaxify any forms noted
            if (response.ajaxForms) {
                Mautic.ajaxifyForms(response.ajaxForms);
            }

            //scroll to the top of the main panel
            $('.main-panel-wrapper').animate({
                scrollTop: 0
            }, 0);

            //initialize tooltips
            $("span[data-toggle='tooltip']").tooltip();
        }
        $("body").removeClass("loading-content");
    },

    /**
     * Prepares forms
     * @param forms
     */
    ajaxifyForms: function (forms) {
        jQuery.map( forms, function( formName, i ) {
            //activate the submit buttons so symfony knows which were clicked
            $('form[name="'+formName+'"] :submit').each(function(){
                $(this).click(function(){
                    if($(this).attr('name')) {
                        $('form[name="'+formName+'"]').append(
                            $("<input type='hidden'>").attr( {
                                name: $(this).attr('name'),
                                value: $(this).attr('value') })
                        );
                    }
                });
            });
            //activate the forms
            $('form[name="'+formName+'"]').submit( function( e ){
                e.preventDefault();

                Mautic.postForm( $(this), function( response ){
                    Mautic.processContent(response);
                });

                return false;
            });

            //active tooltips
            $("span[data-toggle='tooltip']").tooltip();
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
            $(".main-panel-wrapper").click(function(e) {
                e.preventDefault();
                if ($(".page-wrapper").hasClass("right-active")) {
                    $(".page-wrapper").removeClass("right-active");
                }
                //prevent firing event multiple times
                $(".main-panel-wrapper").off("click");
            });

            $(".top-panel").off("click");
            $(".top-panel").click(function(e) {
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
            url: mauticBaseUrl,
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
        var confirmInnerDiv  = $("<div />").attr({ "class": "confirmation-inner-wrapper"});
        var confirmMsgSpan   = $("<span />").css("display", "block").html(msg);
        var confirmButton    = $('<button type="button" />')
            .addClass("btn btn-danger btn-xs")
            .css("marginRight","5px")
            .css("marginLeft", "5px")
            .click(function() {
                if (typeof Mautic[confirmAction] === "function") {
                    window["Mautic"][confirmAction].apply('widnow', confirmParams);
                }
            })
            .html(confirmText);
        var cancelButton    = $('<button type="button" />')
            .addClass("btn btn-primary btn-xs")
            .click(function() {
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
            url: mauticBaseUrl,
            type: "POST",
            data: query,
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    var route = window.location.pathname;
                    Mautic.loadContent(route, '', false, true);
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

        $.ajax({
            url: action,
            type: "POST",
            dataType: "json",
            success: function(response) {
                Mautic.processContent(response);
            }
        });
    },

    /**
     * Toggles permission panel visibility for roles
     */
    togglePermissionVisibility: function () {
        //add a very slight delay in order for the clicked on checkbox to be selected since the onclick action
        //is set to the parent div
        setTimeout(function() {
            if ($('#role_isAdmin_0').prop('checked')) {
                $('#permissions-container').removeClass('hide');
            } else {
                $('#permissions-container').addClass('hide');
            }
        }, 10);
    },

    toggleFullPermissions: function (container, event) {
        //add a very slight delay in order for the clicked on checkbox to be selected since the onclick action
        //is set to the parent div
        setTimeout(function() {
            var clickedBox = $(event.target).find('input:checkbox').first();
            if ($(clickedBox).prop('checked')) {
                if ($(clickedBox).val() == 'full') {
                    //uncheck all of the others
                    $(container).find("label input:checkbox:checked").map(function () {
                        if ($(this).val() != 'full') {
                            $(this).prop('checked', false);
                            $(this).parent().toggleClass('active');
                        }
                    })
                } else {
                    //uncheck full
                    $(container).find("label input:checkbox:checked").map(function () {
                        if ($(this).val() == 'full') {
                            $(this).prop('checked', false);
                            $(this).parent().toggleClass('active');
                        }
                    })
                }
            }
        },10);
    },

    /**
     * Filters list based on search contents
     */
    filterList: function(e,  route) {
        if ($('#list-filter').length && (e.keyCode == 13 || e.which == 13 || $(e.target).hasClass('fa-search'))){
            e.preventDefault();
            $("body").addClass("loading-content");
            $.ajax({
                url: route,
                type: "POST",
                data: $('#list-filter').attr('name') + "=" + $('#list-filter').val(),
                dataType: "json",
                success: function (response) {
                    Mautic.processContent(response);
                }
            });
        }
    },

    /**
     * Shows the search filter input in an search list
     */
    showFilterInput: function () {
        if ($('#list-filter').length) {
            $('#list-filter').addClass('show-filter').removeClass('hide-filter');
        }
    },

    /**
     * Hides the search filter input in an search list
     */
    hideFilterInput: function () {
        if ($('#list-filter').length  && !$('#list-filter').val() && !$('#list-filter').is(":focus")) {
            $('#list-filter').addClass('hide-filter').removeClass('show-filter');
        }
    },

    loadGlobalSearchResults: function (event, searchStr) {
        if (event.keyCode == 13) {
            var query = "ajaxAction=globalsearch&searchstring=" + encodeURI(searchStr);
            $.ajax({
                url: mauticBaseUrl,
                type: "POST",
                data: query,
                dataType: "json",
                success: function (response) {
                    if (response.searchResults) {
                        $(".global-search-wrapper").html(response.searchResults);
                    }
                }
            });
        }
    }
};