//LeadBundle
Mautic.leadOnLoad = function (container) {
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
        mQuery("*[data-toggle='field-lookup']").each(function (index) {
            var target = mQuery(this).attr('data-target');
            var field  = mQuery(this).attr('id');
            var options = mQuery(this).attr('data-options');
            Mautic.activateLeadFieldTypeahead(field, target, options);
        });

        Mautic.updateLeadFieldProperties(mQuery('#leadfield_type').val());
    }

    // Timeline filters
    var timelineForm = mQuery(container + ' #timeline-filters');
    if (timelineForm.length) {
        timelineForm.on('change', function() {
            timelineForm.submit();
        }).on('keyup', function() {
            timelineForm.delay(200).submit();
        }).on('submit', function(e) {
            e.preventDefault();
            Mautic.refreshLeadTimeline(timelineForm);
        });
    }

    //Note type filters
    var noteForm = mQuery(container + ' #note-filters');
    if (noteForm.length) {
        noteForm.on('change', function() {
            noteForm.submit();
        }).on('keyup', function() {
            noteForm.delay(200).submit();
        }).on('submit', function(e) {
            e.preventDefault();
            Mautic.refreshLeadNotes(noteForm);
        });
    }

    if (mQuery(container + ' #list-search').length) {
        Mautic.activateSearchAutocomplete('list-search', 'lead.lead');
    }

    if (mQuery(container + ' #notes-container').length) {
        Mautic.activateSearchAutocomplete('NoteFilter', 'lead.note');
    }

    if (typeof Mautic.leadEngagementChart === 'undefined') {
        Mautic.renderEngagementChart();
    }

    if (mQuery('#lead_preferred_profile_image').length) {
        mQuery('#lead_preferred_profile_image').on('change', function() {
            if (mQuery(this).val() == 'custom') {
                mQuery('#customAvatarContainer').slideDown('fast');
            } else {
                mQuery('#customAvatarContainer').slideUp('fast');
            }
        })
    }

    if (mQuery('.lead-avatar-panel').length) {
        mQuery('.lead-avatar-panel .avatar-collapser a.arrow').on('click', function() {
            setTimeout(function() {
                var status = (mQuery('#lead-avatar-block').hasClass('in') ? 'expanded' : 'collapsed');
                Cookies.set('mautic_lead_avatar_panel', status, {expires: 30});
            }, 500);
        });
    }
};

Mautic.leadOnUnload = function(id) {
    if (id === '#app-content') {
        delete Mautic.leadEngagementChart;
    }

    if (typeof MauticVars.moderatedIntervals['leadListLiveUpdate'] != 'undefined') {
        Mautic.clearModeratedInterval('leadListLiveUpdate');
    }
};

Mautic.getLeadId = function() {
    return mQuery('input#leadId').val();
}

Mautic.activateLeadFieldTypeahead = function(field, target, options) {
    if (options) {
        var keys = values = [];
        //check to see if there is a key/value split
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
            action: "lead:fieldList&field=" + target
        });
    }

    mQuery(fieldTypeahead).on('typeahead:selected', function (event, datum) {
        if (mQuery("#" + field + "_id").length && datum["id"]) {
            mQuery("#" + field + "_id").val(datum["id"]);
        }
    }).on('typeahead:autocompleted', function (event, datum) {
        if (mQuery("#" + field + "_id").length && datum["id"]) {
            mQuery("#" + field + "_id").val(datum["id"]);
        }
    });
};

Mautic.leadlistOnLoad = function(container) {
    if (mQuery(container + ' #list-search').length) {
        Mautic.activateSearchAutocomplete('list-search', 'lead.list');
    }

    /*
    mQuery('#leadlist_filters_right').sortable({
        items: "div.panel"
    });
    */

    if (mQuery('#leadlist_filters_right').length) {
        mQuery('#leadlist_filters_right .remove-selected').each( function (index, el) {
            mQuery(el).on('click', function () {
                mQuery(this).closest('.panel').remove();
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

    var numFilters = mQuery('#leadlist_filters_right > div').length;

    //create a new filter
    var li = mQuery("<div />").addClass("panel").appendTo(mQuery('#leadlist_filters_right'));

    var fieldType = mQuery(filterId).data('field-type');

    //add wrapping div and add the template html

    var container = mQuery('<div />')
        .addClass('filter-container')
        .appendTo(li);

    var isSpecial = (fieldType == 'country' || fieldType == 'timezone' || fieldType == 'region');

    if (isSpecial) {
        container.html(mQuery('#filter-' + fieldType + '-template').html());
    } else {
        container.html(mQuery('#filter-template').html());
    }

    if (numFilters == 0) {
        //keep the footer so that glue is properly populated
        mQuery(container).find(".panel-footer").addClass('hide');
    }

    mQuery(container).find("a.remove-selected").on('click', function() {
        li.remove();
    });

    mQuery(container).find("div.field-name").html(label);
    mQuery(container).find("input[name='leadlist[filters][field][]']").val(elId);
    mQuery(container).find("input[name='leadlist[filters][type][]']").val(fieldType);

    //give the value element a unique id
    var uniqid = "id_" + Date.now();
    var filterEl = (isSpecial) ? "select[name='leadlist[filters][filter][]']" : "input[name='leadlist[filters][filter][]']";
    var filter   =  mQuery(container).find(filterEl);
    filter.attr('id', uniqid);

    //activate fields
    if (isSpecial) {
        filter.attr('data-placeholder', label);
        filter.chosen({
            width: "100%",
            allow_single_deselect: true
        });
    } else if (fieldType == 'lookup' || fieldType == 'select') {
        var fieldCallback = mQuery(filterId).data("field-callback");
        if (fieldCallback) {
            var fieldOptions = mQuery(filterId).data("field-list");
            Mautic[fieldCallback](uniqid, elId, fieldOptions);
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

        var fieldCallback = mQuery(filterId).data("field-callback");
        if (fieldCallback) {
            var fieldOptions = mQuery(filterId).data("field-list");
            Mautic[fieldCallback](uniqid, elId, fieldOptions);
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
        showLoadingBar: true,
        url: mauticAjaxUrl,
        type: "POST",
        data: query,
        dataType: "json",
        success: function (response) {
            if (response.success) {
                if (response.completeProfile) {
                    mQuery('#social-container').html(response.completeProfile);
                    mQuery('#SocialCount').html(response.socialCount);
                } else {
                    //loop through each network
                    mQuery.each(response.profiles, function (index, value) {
                        if (mQuery('#' + index + 'CompleteProfile').length) {
                            mQuery('#' + index + 'CompleteProfile').html(value.newContent);
                        }
                    });
                }
            }
            Mautic.stopPageLoadingBar();
            Mautic.stopIconSpinPostEvent();
        },
        error: function (request, textStatus, errorThrown) {
            Mautic.processAjaxError(request, textStatus, errorThrown);
        }
    });
};

Mautic.clearLeadSocialProfile = function(network, leadId, event) {
    Mautic.startIconSpinOnEvent(event);
    var query = "action=lead:clearSocialProfile&network=" + network + "&lead=" + leadId;
    mQuery.ajax({
        url: mauticAjaxUrl,
        type: "POST",
        data: query,
        dataType: "json",
        success: function (response) {
            if (response.success) {
                //activate the click to remove the panel
                mQuery('.' + network + '-panelremove').click();
                if (response.completeProfile) {
                    mQuery('#social-container').html(response.completeProfile);
                }
                mQuery('#SocialCount').html(response.socialCount);
            }

            Mautic.stopIconSpinPostEvent();
        },
        error: function (request, textStatus, errorThrown) {
            Mautic.processAjaxError(request, textStatus, errorThrown);
            Mautic.stopIconSpinPostEvent();
        }
    });
};

Mautic.refreshLeadTimeline = function(form) {
    var formData = form.serialize()
    mQuery.ajax({
        showLoadingBar: true,
        url: mauticAjaxUrl,
        type: "POST",
        data: "action=lead:updateTimeline&" + formData,
        dataType: "json",
        success: function (response) {
            if (response.success) {
                Mautic.stopPageLoadingBar();
                mQuery('#timeline-container').html(response.timeline);
                mQuery('#HistoryCount').html(response.historyCount);
            }
        },
        error: function (request, textStatus, errorThrown) {
            Mautic.processAjaxError(request, textStatus, errorThrown);
        }
    });
};

Mautic.refreshLeadNotes = function(form) {
    Mautic.postForm(mQuery(form), function (response) {
        response.target = '#NoteList';
        mQuery('#NoteCount').html(response.noteCount);
        Mautic.processPageContent(response);
    });
};

Mautic.toggleLeadList = function(toggleId, leadId, listId) {
    var action = mQuery('#' + toggleId).hasClass('fa-toggle-on') ? 'remove' : 'add';
    var query = "action=lead:toggleLeadList&leadId=" + leadId + "&listId=" + listId + "&listAction=" + action;

    Mautic.toggleLeadSwitch(toggleId, query, action);
};

Mautic.toggleLeadCampaign = function(toggleId, leadId, campaignId) {
    var action = mQuery('#' + toggleId).hasClass('fa-toggle-on') ? 'remove' : 'add';
    var query  = "action=lead:toggleLeadCampaign&leadId=" + leadId + "&campaignId=" + campaignId + "&campaignAction=" + action;

    Mautic.toggleLeadSwitch(toggleId, query, action);
};

Mautic.toggleLeadSwitch = function(toggleId, query, action) {
    var toggleOn  = 'fa-toggle-on text-success';
    var toggleOff = 'fa-toggle-off text-danger';
    var spinClass = 'fa-spin fa-spinner ';

    if (action == 'remove') {
        //switch it on
        mQuery('#' + toggleId).removeClass(toggleOn).addClass(spinClass + 'text-danger');
    } else {
        mQuery('#' + toggleId).removeClass(toggleOff).addClass(spinClass + 'text-success');
    }

    mQuery.ajax({
        url: mauticAjaxUrl,
        type: "POST",
        data: query,
        dataType: "json",
        success: function (response) {
            mQuery('#' + toggleId).removeClass(spinClass);
            if (!response.success) {
                //return the icon back
                if (action == 'remove') {
                    //switch it on
                    mQuery('#' + toggleId).removeClass(toggleOff).addClass(toggleOn);
                } else {
                    mQuery('#' + toggleId).removeClass(toggleOn).addClass(toggleOff);
                }
            } else {
                if (action == 'remove') {
                    //switch it on
                    mQuery('#' + toggleId).removeClass(toggleOn).addClass(toggleOff);
                } else {
                    mQuery('#' + toggleId).removeClass(toggleOff).addClass(toggleOn);
                }
            }
        },
        error: function (request, textStatus, errorThrown) {
            //return the icon back
            mQuery('#' + toggleId).removeClass(spinClass);

            if (action == 'remove') {
                //switch it on
                mQuery('#' + toggleId).removeClass(toggleOff).addClass(toggleOn);
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
            mQuery('#LeadNotes').prepend(response.noteHtml);
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
            count = (response.upNoteCount) ? count + 1 : count - 1;
        } else {
            var count = parseInt(response.noteCount);
        }

        mQuery('#NoteCount').html(count);
    }
};

Mautic.renderEngagementChart = function() {
    if (!mQuery("#chart-engagement").length) {
        return;
    }
    var canvas = document.getElementById("chart-engagement");
    var chartData = mQuery.parseJSON(mQuery('#chart-engagement-data').text());
    Mautic.leadEngagementChart = new Chart(canvas.getContext("2d")).Line(chartData);

    var legendHolder = document.createElement('div');
    legendHolder.innerHTML = Mautic.leadEngagementChart.generateLegend();
    mQuery('#engagement-legend').html(legendHolder.firstChild);
    Mautic.leadEngagementChart.update();
};

Mautic.showSocialMediaImageModal = function(imgSrc) {
    mQuery('#socialImageModal img').attr('src', imgSrc);
    mQuery('#socialImageModal').modal('show');
};

Mautic.leadImportOnLoad = function (container, response) {
    if (!mQuery('#leadImportProgress').length) {
        Mautic.clearModeratedInterval('leadImportProgress');
    } else {
        Mautic.setModeratedInterval('leadImportProgress', 'reloadLeadImportProgress', 3000);
    }
};

Mautic.reloadLeadImportProgress = function() {
    if (!mQuery('#leadImportProgress').length) {
        Mautic.clearModeratedInterval('leadImportProgress');
    } else {
        // Get progress separate so there's no delay while the import batches
        Mautic.ajaxActionRequest('lead:getImportProgress', {}, function(response) {
            if (response.progress) {
                if (response.progress[0] > 0) {
                    mQuery('.imported-count').html(response.progress[0]);
                    mQuery('.progress-bar-import').attr('aria-valuenow', response.progress[0]).css('width', response.percent + '%');
                    mQuery('.progress-bar-import span.sr-only').html(response.percent + '%');
                }
            }
        });

        // Initiate import
        mQuery.ajax({
            showLoadingBar: false,
            url: window.location + '?importbatch=1',
            success: function(response) {
                Mautic.moderatedIntervalCallbackIsComplete('leadImportProgress');

                if (response.newContent) {
                    // It's done so pass to process page
                    Mautic.processPageContent(response);
                }
            }
        });
    }
};

Mautic.removeBounceStatus = function (el, dncId) {
    mQuery(el).removeClass('fa-times').addClass('fa-spinner fa-spin');

    Mautic.ajaxActionRequest('lead:removeBounceStatus', 'id=' + dncId, function() {
        mQuery('#bounceLabel' + dncId).fadeOut(300, function() { mQuery(this).remove(); });
    });
};

Mautic.toggleLiveLeadListUpdate = function () {
    if (typeof MauticVars.moderatedIntervals['leadListLiveUpdate'] == 'undefined') {
        Mautic.setModeratedInterval('leadListLiveUpdate', 'updateLeadList', 5000);
        mQuery('#liveModeButton').addClass('btn-primary');
    } else {
        Mautic.clearModeratedInterval('leadListLiveUpdate');
        mQuery('#liveModeButton').removeClass('btn-primary');
    }
};

Mautic.updateLeadList = function () {
    var maxLeadId = mQuery('#liveModeButton').data('max-id');
    mQuery.ajax({
        url: mauticAjaxUrl,
        type: "get",
        data: "action=lead:getNewLeads&maxId=" + maxLeadId,
        dataType: "json",
        success: function (response) {
            if (response.leads) {
                if (response.indexMode == 'list') {
                    mQuery('#leadTable tbody').prepend(response.leads);
                } else {
                    var items = mQuery(response.leads);
                    mQuery('.shuffle-grid').prepend(items);
                    mQuery('.shuffle-grid').shuffle('appended', items);
                    mQuery('.shuffle-grid').shuffle('update');

                    mQuery('#liveModeButton').data('max-id', response.maxId);
                }
            }

            if (typeof IdleTimer != 'undefined' && !IdleTimer.isIdle()) {
                // Remove highlighted classes
                if (response.indexMode == 'list') {
                    mQuery('#leadTable tr.warning').each(function() {
                        var that = this;
                        setTimeout(function() {
                            mQuery(that).removeClass('warning', 1000)
                        }, 5000);
                    });
                } else {
                    mQuery('.shuffle-grid .highlight').each(function() {
                        var that = this;
                        setTimeout(function() {
                            mQuery(that).removeClass('highlight', 1000, function() {
                                mQuery(that).css('border-top-color', mQuery(that).data('color'));
                            })
                        }, 5000);
                    });
                }
            }

            if (response.maxId) {
                mQuery('#liveModeButton').data('max-id', response.maxId);
            }

            Mautic.moderatedIntervalCallbackIsComplete('leadListLiveUpdate');
        },
        error: function (request, textStatus, errorThrown) {
            Mautic.processAjaxError(request, textStatus, errorThrown);

            Mautic.moderatedIntervalCallbackIsComplete('leadListLiveUpdate');
        }
    });
};

/**
 * Obtains the HTML for an email
 *
 * @param el
 */
Mautic.getLeadEmailContent = function (el) {
    Mautic.activateLabelLoadingIndicator('lead_quickemail_templates');
    Mautic.ajaxActionRequest('lead:getEmailTemplate', {'template': mQuery(el).val()}, function(response) {
        CKEDITOR.instances['lead_quickemail_body'].setData(response.body);
        mQuery('#lead_quickemail_subject').val(response.subject);
        Mautic.removeLabelLoadingIndicator();
    });
};