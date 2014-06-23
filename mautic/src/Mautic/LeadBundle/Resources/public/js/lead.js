//LeadBundle
Mautic.leadOnLoad = function (container) {
    if ($(container + ' form[name="lead"]').length) {
        Mautic.activateLeadOwnerTypeahead('lead_owner_lookup');

        $("*[data-toggle='field-lookup']").each(function (index) {
            var target = $(this).attr('data-target');
            var field  = $(this).attr('id');
            var options = $(this).attr('data-options');
            Mautic.activateLeadFieldTypeahead(field, target, options);
        });
    }

    if ($(container + ' .lead-list').length) {
        //set height of divs
        var windowHeight = $(window).height() - 175;
        if (windowHeight > 450) {
            $('.lead-list').css('height', windowHeight + 'px');
            $('.lead-details').css('height', windowHeight + 'px');
        }
    }

    if ($(container + ' #list-search').length) {
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
                $.each(strs, function(i, str) {
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
                url: mauticBaseUrl + "ajax?ajaxAction=lead:lead:fieldlist&field=" + target
            },
            remote: {
                url: mauticBaseUrl + "ajax?ajaxAction=lead:lead:fieldlist&field=" + target + "&filter=%QUERY"
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

    $('#' + field).typeahead(
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
        if ($("#" + field + "_id").length && datum["id"]) {
            $("#" + field + "_id").val(datum["id"]);
        }
    }).on('typeahead:autocompleted', function (event, datum) {
        if ($("#" + field + "_id").length && datum["id"]) {
            $("#" + field + "_id").val(datum["id"]);
        }
    });
};

Mautic.activateLeadOwnerTypeahead = function(el) {
    var owners = new Bloodhound({
        datumTokenizer: Bloodhound.tokenizers.obj.whitespace('label'),
        queryTokenizer: Bloodhound.tokenizers.whitespace,
        prefetch: {
            url: mauticBaseUrl + "ajax?ajaxAction=lead:lead:userlist"
        },
        remote: {
            url: mauticBaseUrl + "ajax?ajaxAction=lead:lead:userlist&filter=%QUERY"
        },
        dupDetector: function (remoteMatch, localMatch) {
            return (remoteMatch.label == localMatch.label);
        },
        ttl: 1800000,
        limit: 5
    });
    owners.initialize();
    $("#"  + el).typeahead(
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
            if ($("#lead_owner").length) {
                $("#lead_owner").val(datum["value"]);
            }
        }).on('typeahead:autocompleted', function (event, datum) {
            if ($("#lead_owner").length) {
                $("#lead_owner").val(datum["value"]);
            }
        }
    ).on( 'focus', function() {
        $(this).typeahead( 'open');
    });
};

Mautic.activateLead = function(leadId) {
    $('.lead-profile').removeClass('active');
    $('#lead-' + leadId).addClass('active');
};

Mautic.leadlistOnLoad = function(container) {
    if ($(container + ' #list-search').length) {
        Mautic.activateSearchAutocomplete('list-search', 'lead.list');
    }

    $('#leadlist_filters_left li').draggable({
        appendTo: "body",
        helper: "clone"
    });

    $('#leadlist_filters_right').sortable({
        items: "li",
        handle: '.sortable-handle'
    });

    if ($('#leadlist_filters_right').length) {
        $('#leadlist_filters_right .remove-selected').each( function (index, el) {
            $(el).on('click', function () {
                $(this).parent().remove();
                if (!$('#leadlist_filters_right li:not(.placeholder)').length) {
                    $('#leadlist_filters_right li.placeholder').removeClass('hide');
                } else {
                    $('#leadlist_filters_right li.placeholder').addClass('hide');
                }
            });
        });
    }

    $("*[data-toggle='field-lookup']").each(function (index) {
        var target = $(this).attr('data-target');
        var options = $(this).attr('data-options');
        var field  = $(this).attr('id');
        Mautic.activateLeadFieldTypeahead(field, target, options);
    });
};

Mautic.addLeadListFilter = function (elId) {
    var filter = '#available_' + elId;
    var label  = $(filter + ' span.leadlist-filter-name').text();

    //create a new filter
    var li = $("<li />").addClass("padding-sm").text(label).appendTo($('#leadlist_filters_right'));

    //add a delete button
    $("<i />").addClass("fa fa-fw fa-trash-o remove-selected").prependTo(li).on('click', function() {
        $(this).parent().remove();
    });

    //add a sortable handle
    $("<i />").addClass("fa fa-fw fa-ellipsis-v sortable-handle").prependTo(li);

    var fieldType = $(filter).find("input.field_type").val();
    var alias     = $(filter).find("input.field_alias").val();

    //add wrapping div and add the template html

    var container = $('<div />')
        .addClass('filter-container')
        .appendTo(li);

    if (fieldType == 'country' || fieldType == 'timezone') {
        container.html($('#filter-' + fieldType + '-template').html());
    } else {
        container.html($('#filter-template').html());
    }
    $(container).find("input[name='leadlist[filters][field][]']").val(alias);
    $(container).find("input[name='leadlist[filters][type][]']").val(fieldType);

    //give the value element a unique id
    var uniqid = "id_" + Date.now();
    var filter = $(container).find("input[name='leadlist[filters][filter][]']");
    filter.attr('id', uniqid);

    //activate fields
    if (fieldType == 'lookup' || fieldType == 'select') {
        var fieldCallback = $(filter).find("input.field_callback").val();
        if (fieldCallback) {
            var fieldOptions = $(filter).find("input.field_list").val();
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
        var oldFilter = $(container).find("input[name='leadlist[filters][filter][]']");
        var newDisplay = oldFilter.clone();
        newDisplay.attr('id', uniqid);
        newDisplay.attr('name', 'leadlist[filters][display][]');

        var oldDisplay = $(container).find("input[name='leadlist[filters][display][]']");
        var newFilter = oldDisplay.clone();
        newFilter.attr('id', uniqid + "_id");
        newFilter.attr('name', 'leadlist[filters][filter][]');

        oldFilter.replaceWith(newFilter);
        oldDisplay.replaceWith(newDisplay);

        var fieldCallback = $(filter).find("input.field_callback").val();
        if (fieldCallback) {
            var fieldOptions = $(filter).find("input.field_list").val();
            Mautic[fieldCallback](uniqid, alias, fieldOptions);
        }
    } else {
        filter.attr('type', fieldType);
    }
};

Mautic.leadfieldOnLoad = function (container) {

    var fixHelper = function(e, ui) {
        ui.children().each(function() {
            $(this).width($(this).width());
        });
        return ui;
    };

    if ($(container + ' .leadfield-list').length) {
        $(container + ' .leadfield-list tbody').sortable({
            handle: '.fa-ellipsis-v',
            helper: fixHelper,
            stop: function(i) {
                $.ajax({
                    type: "POST",
                    url: mauticBaseUrl + "ajax?ajaxAction=lead:field:reorder",
                    data: $(container + ' .leadfield-list tbody').sortable("serialize")});
            }
        });
    }

};

/**
 * Filter leads by selected list
 * @param list
 */
Mautic.filterLeadsByList = function(list) {
    $('#list-search').typeahead('val', list);
    var e = $.Event( "keypress", { which: 13 } );
    e.data = {};
    e.data.livesearch = true;
    Mautic.filterList(
        e,
        'list-search',
        $('#list-search').attr('data-action'),
        $('#list-search').attr('data-target'),
        'liveCache'
    );
    $('#filterByList').val('');
};

/**
 * Update the properties for field data types
 */
Mautic.updateLeadFieldProperties = function(selectedVal) {
    if ($('#field-templates .'+selectedVal).length) {
        $('#leadfield_properties').html($('#field-templates .'+selectedVal).html());

        $("#leadfield_properties *[data-toggle='tooltip']").tooltip({html: true});
    } else {
        $('#leadfield_properties').html('');
    }

    if (selectedVal == 'time') {
        $('#leadfield_isListable').closest('.row').addClass('hide');
    } else {
        $('#leadfield_isListable').closest('.row').removeClass('hide');
    }
}