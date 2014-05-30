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
    } else {
        //set height of divs
        var windowHeight = $(window).height() - 175;
        $('.lead-list').css('height', windowHeight+'px');
        $('.lead-details').css('height', windowHeight+'px');
    }

    if ($(container + ' #list-search').length) {
        Mautic.activateSearchAutocomplete('list-search', 'lead');
    }
};

Mautic.activateLeadFieldTypeahead = function(field, target, options) {
    if (options) {
        options = options.split('|');
        var substringMatcher = function(strs) {
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
                        matches.push({ value: str });
                    }
                });

                cb(matches);
            };
        };

        var source = substringMatcher(options);
    } else {
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
            minLength: 2
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
    );
};

Mautic.activateLead = function(leadId) {
    $('.lead-profile').removeClass('active');
    $('#lead-' + leadId).addClass('active');
};

Mautic.leadlistOnLoad = function(container) {
    if ($(container + ' #list-search').length) {
        Mautic.activateSearchAutocomplete('list-search', 'leadlist');
    }

    $('#leadlist_filters_left li').draggable({
        appendTo: "body",
        helper: "clone"
    });

    $('#leadlist_filters_right').droppable({
        activeClass: "droppable-active",
        hoverClass: "dropper-hover",
        accept: ":not(.ui-sortable-helper)",
        drop: function(event, ui) {
            if ($(this).find(".placeholder").length) {
                if (typeof mauticVars.droppablePlaceholder == "undefined") {
                    mauticVars.droppablePlaceholder = new Array();
                }
                mauticVars.droppablePlaceholder['#leadlist_filters_right'] = $(this).find(".placeholder").html();
                $('#leadlist_filters_right li.placeholder').addClass('hide');
            }

            //create a new filter
            var li = $("<li />").addClass("padding-sm").text(ui.draggable.text()).appendTo(this);

            //add a delete button
            $("<i />").addClass("fa fa-fw fa-trash-o remove-selected").prependTo(li).on('click', function() {
                $(this).parent().remove();
                if (!$('#leadlist_filters_right li:not(.placeholder)').length) {
                    $('#leadlist_filters_right li.placeholder').removeClass('hide');
                } else {
                    $('#leadlist_filters_right li.placeholder').addClass('hide');
                }
            });

            //add a sortable handle
            $("<i />").addClass("fa fa-fw fa-arrows sortable-handle").prependTo(li);

            var alias = $(ui.draggable).find("input.field_alias").val();

            //add wrapping div and add the template html
            var container = $('<div />')
                .addClass('filter-container')
                .html($('#filter-template').html())
                .appendTo(li);
            $(container).find("input[type='hidden']").val(alias);

            //give the value element a unique id
            var uniqid = "id_" + Date.now();
            $(container).find("input[name='leadlist[filters][filter][]']").attr('id', uniqid);

            //activate fields
            var fieldType = $(ui.draggable).find("input.field_type").val();
            if (fieldType == 'lookup') {
                var fieldCallback = $(ui.draggable).find("input.field_callback").val();
                Mautic[fieldCallback](uniqid, alias);
            } else if (fieldType == 'lookup_id') {
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

                var fieldCallback = $(ui.draggable).find("input.field_callback").val();
                Mautic[fieldCallback](uniqid, alias);
            }
        }
    }).sortable({
        items: "li:not(.placeholder)",
        handle: '.sortable-handle',
        sort: function() {
            $( this ).removeClass( "droppable-active" );
        }
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
}