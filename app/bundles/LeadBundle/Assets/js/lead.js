//LeadBundle
Mautic.leadOnLoad = function (container) {
    if (mQuery(container + ' form[name="lead"]').length) {
        Mautic.activateLeadOwnerTypeahead('lead_owner_lookup');

        mQuery("*[data-toggle='field-lookup']").each(function (index) {
            var target = mQuery(this).attr('data-target');
            var field  = mQuery(this).attr('id');
            var options = mQuery(this).attr('data-options');
            Mautic.activateLeadFieldTypeahead(field, target, options);
        });
    }

    if (mQuery(container + ' #list-search').length) {
        Mautic.activateSearchAutocomplete('list-search', 'lead.lead');
    }
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
    var li = mQuery("<li />").addClass("padding-sm").text(label).appendTo(mQuery('#leadlist_filters_right'));

    //add a delete button
    mQuery("<i />").addClass("fa fa-fw fa-trash-o remove-selected").prependTo(li).on('click', function() {
        mQuery(this).parent().remove();
    });

    //add a sortable handle
    mQuery("<i />").addClass("fa fa-fw fa-ellipsis-v sortable-handle").prependTo(li);

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

Mautic.leadlistOnLoad = function(container) {
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