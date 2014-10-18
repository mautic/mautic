//LeadBundle
Mautic.leadOnLoad = function (container) {
    Mousetrap.reset();

    Mousetrap.bind('shift+l', function(e) {
        mQuery('#menu_lead_parent_child > li:first > a').click();
    });

    Mousetrap.bind('a', function(e) {
        if(mQuery('#lead-quick-add').length) {
            mQuery('#lead-quick-add').modal();
        } else if (mQuery('#addNoteButton').length) {
            mQuery('#addNoteButton').click();
        }
    });

    Mousetrap.bind('t', function(e) {
        mQuery('#table-view').click();
    });

    Mousetrap.bind('c', function(e) {
        mQuery('#card-view').click();
    });

    Mousetrap.bind('n', function(e) {
        mQuery('#new-lead').click();
    });

    Mousetrap.bind('mod+enter', function(e) {
        if(mQuery('#leadnote_buttons_save').length) {
            mQuery('#leadnote_buttons_save').click();
        } else if (mQuery('#save-quick-add').length) {
            mQuery('#save-quick-add').click();
        }
    });

    //Prevent single combo keys from initiating within lead note
    Mousetrap.stopCallback = function(e, element, combo) {
        if (element.id == 'leadnote_text' && combo != 'mod+enter') {
            return true;
        }

        // if the element has the class "mousetrap" then no need to stop
        if ((' ' + element.className + ' ').indexOf(' mousetrap ') > -1) {
            return false;
        }

        // stop for input, select, and textarea
        return element.tagName == 'INPUT' || element.tagName == 'SELECT' || element.tagName == 'TEXTAREA' || (element.contentEditable && element.contentEditable == 'true');
    };

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

    if (mQuery(container + ' #notes-container').length) {
        Mautic.activateSearchAutocomplete('NoteFilter', 'lead.note');
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
            Mautic.processAjaxError(request, textStatus, errorThrown);
            Mautic.stopIconSpinPostEvent(event);
        }
    });
};

Mautic.refreshLeadTimeline = function(leadId, event) {
    Mautic.startIconSpinOnEvent(event);
    var filter = mQuery(event);
    var filterName = filter.attr('name');
    var filterValue = filter.val();
    var query = "action=lead:updateTimeline&filter_name=" + filterName + "&filter_value=" + filterValue + "&lead=" + leadId;
    mQuery.ajax({
        url: mauticAjaxUrl,
        type: "POST",
        data: query,
        dataType: "json",
        success: function (response) {
            if (response.success) {
                mQuery('#history-container').html(response.timeline);
            }
            Mautic.stopIconSpinPostEvent(event);
        },
        error: function (request, textStatus, errorThrown) {
            Mautic.processAjaxError(request, textStatus, errorThrown);
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

    if (response.upNoteCount || response.noteCount || response.downNoteCount) {
        if (response.upNoteCount || response.downNoteCount) {
            var count = parseInt(mQuery('#NoteCount').html());
            if (response.upNoteCount) {
                count = count + 1;
            } else {
                count = count - 1;
            }
        } else {
            var count = parseInt(response.noteCount);
        }

        mQuery('#NoteCount').html(count);
    }
};

