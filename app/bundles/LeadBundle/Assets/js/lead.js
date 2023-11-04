//LeadBundle
Mautic.companyOnLoad = function (container, response) {

    if (mQuery(container + ' #list-search').length) {
        Mautic.activateSearchAutocomplete('list-search', 'lead.company');
    }
}
Mautic.leadOnLoad = function (container, response) {
    Mautic.addKeyboardShortcut('a', 'Quick add a New Contact', function(e) {
        if(mQuery('a.quickadd').length) {
            mQuery('a.quickadd').click();
        } else if (mQuery('a.btn-leadnote-add').length) {
            mQuery('a.btn-leadnote-add').click();
        }
    }, 'contact pages');

    Mautic.addKeyboardShortcut('t', 'Activate Table View', function(e) {
        mQuery('#table-view').click();
    }, 'contact pages');

    Mautic.addKeyboardShortcut('c', 'Activate Card View', function(e) {
        mQuery('#card-view').click();
    }, 'contact pages');

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

        var toggleTimelineDetails = function (el) {
            var activateDetailsState = mQuery(el).hasClass('active');

            if (activateDetailsState) {
                mQuery('#timeline-details-'+detailsId).addClass('hide');
                mQuery(el).removeClass('active');
            } else {
                mQuery('#timeline-details-'+detailsId).removeClass('hide');
                mQuery(el).addClass('active');
            }
        };

        Mautic.leadTimelineOnLoad(container, response);
        Mautic.leadAuditlogOnLoad(container, response);
    }

    // Auditlog filters
    var auditlogForm = mQuery(container + ' #auditlog-filters');
    if (auditlogForm.length) {
        auditlogForm.on('change', function() {
            auditlogForm.submit();
        }).on('keyup', function() {
            auditlogForm.delay(200).submit();
        }).on('submit', function(e) {
            e.preventDefault();
            Mautic.refreshLeadAuditLog(auditlogForm);
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

    if (mQuery('#anonymousLeadButton').length) {
        var searchValue = mQuery('#list-search').typeahead('val').toLowerCase();
        var string      = mQuery('#anonymousLeadButton').data('anonymous').toLowerCase();

        if (searchValue.indexOf(string) >= 0 && searchValue.indexOf('!' + string) == -1) {
            mQuery('#anonymousLeadButton').addClass('btn-primary');
        } else {
            mQuery('#anonymousLeadButton').removeClass('btn-primary');
        }
    }

    var leadMap = [];

    mQuery(document).on('shown.bs.tab', 'a#load-lead-map', function (e) {
        leadMap = Mautic.renderMap(mQuery('#place-container .vector-map'));
    });

    mQuery('a[data-toggle="tab"]').not('a#load-lead-map').on('shown.bs.tab', function (e) {
        if (leadMap.length) {
            Mautic.destroyMap(leadMap);
            leadMap = [];
        }
    });

    Mautic.initUniqueIdentifierFields();

    if (mQuery(container + ' .panel-companies').length) {
        mQuery(container + ' .panel-companies .fa-check').tooltip({html: true});
    }

    // Adding behavior to be able to create new tags by pressing the `Escape` key
    // when the search field is active (ie: the tag name we are typing is a substring of an existing tag)
    mQuery('#lead_tags_chosen input').keyup(function(el) {
        const newTag = mQuery('#lead_tags_chosen input').val();
        if (el.key === "Escape" && newTag !== '') {
            const selectElement = mQuery('#lead_tags').get();
            const selectedValues = mQuery('#lead_tags').val();
            const payload = [...selectedValues, newTag];

            Mautic.activateLabelLoadingIndicator(mQuery(selectElement).attr('id'));
            Mautic.ajaxActionRequest('lead:addLeadTags', {tags: JSON.stringify(payload)}, function(response) {
                if (response.tags) {
                    mQuery('#' + mQuery(selectElement).attr('id')).html(response.tags);
                    mQuery('#' + mQuery(selectElement).attr('id')).trigger('chosen:updated');
                }

                Mautic.removeLabelLoadingIndicator();
            });
        }
    });

    Mautic.lazyLoadContactStatsOnLeadLoad();
};

Mautic.leadTimelineOnLoad = function (container, response) {
    mQuery("#contact-timeline a[data-activate-details='all']").on('click', function() {
        if (mQuery(this).find('span').first().hasClass('fa-level-down')) {
            mQuery("#contact-timeline a[data-activate-details!='all']").each(function () {
                var detailsId = mQuery(this).data('activate-details');
                if (detailsId && mQuery('#timeline-details-'+detailsId).length) {
                    mQuery('#timeline-details-' + detailsId).removeClass('hide');
                    mQuery(this).addClass('active');
                }
            });
            mQuery(this).find('span').first().removeClass('fa-level-down').addClass('fa-level-up');
        } else {
            mQuery("#contact-timeline a[data-activate-details!='all']").each(function () {
                var detailsId = mQuery(this).data('activate-details');
                if (detailsId && mQuery('#timeline-details-'+detailsId).length) {
                    mQuery('#timeline-details-' + detailsId).addClass('hide');
                    mQuery(this).removeClass('active');
                }
            });
            mQuery(this).find('span').first().removeClass('fa-level-up').addClass('fa-level-down');
        }
    });
    mQuery("#contact-timeline a[data-activate-details!='all']").on('click', function() {
        var detailsId = mQuery(this).data('activate-details');
        if (detailsId && mQuery('#timeline-details-'+detailsId).length) {
            var activateDetailsState = mQuery(this).hasClass('active');

            if (activateDetailsState) {
                mQuery('#timeline-details-'+detailsId).addClass('hide');
                mQuery(this).removeClass('active');
            } else {
                mQuery('#timeline-details-'+detailsId).removeClass('hide');
                mQuery(this).addClass('active');
            }
        }
    });

    if (response && typeof response.timelineCount != 'undefined') {
        mQuery('#TimelineCount').html(response.timelineCount);
    }
};

Mautic.leadAuditlogOnLoad = function (container, response) {
    mQuery("#contact-auditlog a[data-activate-details='all']").on('click', function() {
        if (mQuery(this).find('span').first().hasClass('fa-level-down')) {
            mQuery("#contact-auditlog a[data-activate-details!='all']").each(function () {
                var detailsId = mQuery(this).data('activate-details');
                if (detailsId && mQuery('#auditlog-details-'+detailsId).length) {
                    mQuery('#auditlog-details-' + detailsId).removeClass('hide');
                    mQuery(this).addClass('active');
                }
            });
            mQuery(this).find('span').first().removeClass('fa-level-down').addClass('fa-level-up');
        } else {
            mQuery("#contact-auditlog a[data-activate-details!='all']").each(function () {
                var detailsId = mQuery(this).data('activate-details');
                if (detailsId && mQuery('#auditlog-details-'+detailsId).length) {
                    mQuery('#auditlog-details-' + detailsId).addClass('hide');
                    mQuery(this).removeClass('active');
                }
            });
            mQuery(this).find('span').first().removeClass('fa-level-up').addClass('fa-level-down');
        }
    });
    mQuery("#contact-auditlog a[data-activate-details!='all']").on('click', function() {
        var detailsId = mQuery(this).data('activate-details');
        if (detailsId && mQuery('#auditlog-details-'+detailsId).length) {
            var activateDetailsState = mQuery(this).hasClass('active');

            if (activateDetailsState) {
                mQuery('#auditlog-details-'+detailsId).addClass('hide');
                mQuery(this).removeClass('active');
            } else {
                mQuery('#auditlog-details-'+detailsId).removeClass('hide');
                mQuery(this).addClass('active');
            }
        }
    });
};

Mautic.leadOnUnload = function(id) {
    if (typeof MauticVars.moderatedIntervals['leadListLiveUpdate'] != 'undefined') {
        Mautic.clearModeratedInterval('leadListLiveUpdate');
    }

    if (typeof Mautic.mapObjects !== 'undefined') {
        delete Mautic.mapObjects;
    }
};

Mautic.getLeadId = function() {
    return mQuery('input#leadId').val();
}

Mautic.leadlistOnLoad = function(container, response) {
    const segmentCountElem = mQuery('a.col-count');

    if (segmentCountElem.length) {
        segmentCountElem.each(function() {
            const elem = mQuery(this);
            const id = elem.attr('data-id');

            Mautic.ajaxActionRequest(
                'lead:getLeadCount',
                {id: id},
                function (response) {
                    elem.html(response.html);
                },
                false,
                true,
                "GET"
            );
        });
    }

    mQuery('#campaign-share-tab').hover(function () {
        if (Mautic.shareTableLoaded != true) {
            Mautic.loadAjaxColumn('campaign-share-stat', 'lead:getCampaignShareStats', 'afterStatsLoad');
            Mautic.shareTableLoaded = true;
        }
    })

    Mautic.afterStatsLoad = function () {
        Mautic.sortTableByColumn('#campaign-share-table', '.campaign-share-stat', true)
    }


    if (mQuery(container + ' #list-search').length) {
        Mautic.activateSearchAutocomplete('list-search', 'lead.list');
    }

    var prefix = 'leadlist';
    var parent = mQuery('.dynamic-content-filter, .dwc-filter');
    if (parent.length) {
        prefix = parent.attr('id');
    }

    if (mQuery('#' + prefix + '_filters').length) {
        mQuery('#available_segment_filters').on('change', function() {
            if (mQuery(this).val()) {
                Mautic.addLeadListFilter(mQuery(this).val(),mQuery('option:selected',this).data('field-object'));
                mQuery(this).val('');
                mQuery(this).trigger('chosen:updated');
            }
        });

        mQuery('#' + prefix + '_filters .remove-selected').each( function (index, el) {
            mQuery(el).on('click', function () {
                mQuery(this).closest('.panel').animate(
                    {'opacity': 0},
                    'fast',
                    function () {
                        mQuery(this).remove();
                        Mautic.reorderSegmentFilters();
                    }
                );

                if (!mQuery('#' + prefix + '_filters li:not(.placeholder)').length) {
                    mQuery('#' + prefix + '_filters li.placeholder').removeClass('hide');
                } else {
                    mQuery('#' + prefix + '_filters li.placeholder').addClass('hide');
                }
            });
        });

        var bodyOverflow = {};
        mQuery('#' + prefix + '_filters').sortable({
            items: '.panel',
            helper: function(e, ui) {
                ui.children().each(function() {
                    if (mQuery(this).is(":visible")) {
                        mQuery(this).width(mQuery(this).width());
                    }
                });

                // Fix body overflow that messes sortable up
                bodyOverflow.overflowX = mQuery('body').css('overflow-x');
                bodyOverflow.overflowY = mQuery('body').css('overflow-y');
                mQuery('body').css({
                    overflowX: 'visible',
                    overflowY: 'visible'
                });

                return ui;
            },
            scroll: true,
            axis: 'y',
            stop: function(e, ui) {
                // Restore original overflow
                mQuery('body').css(bodyOverflow);

                // First in the list should be an "and"
                ui.item.find('select.glue-select').first().val('and');

                Mautic.reorderSegmentFilters();
            }
        });

    }

    // segment contact filters
    var segmentContactForm = mQuery('#segment-contact-filters');

    if (segmentContactForm.length) {
        segmentContactForm.on('change', function() {
            segmentContactForm.submit();
        }).on('keyup', function() {
            segmentContactForm.delay(200).submit();
        }).on('submit', function(e) {
            e.preventDefault();
            Mautic.refreshSegmentContacts(segmentContactForm);
        });
    }

    jQuery(document).ajaxComplete(function(){
        Mautic.ajaxifyForm('daterange');
    });

    Mautic.attachJsUiOnFilterForms();
};

/**
 * Trigger event so plugins could attach other JS magic to the form.
 */
Mautic.triggerOnPropertiesFormLoadedEvent = function(selector, filterValue) {
    mQuery('#leadlist_filters').trigger('filter.properties.form.loaded', [selector, filterValue]);
};

Mautic.attachJsUiOnFilterForms = function() {
    mQuery('#leadlist_filters').on('filter.properties.form.loaded', function(event, selector, filterValue) {
        Mautic.activateChosenSelect(selector + '_properties select');
        var fieldType = mQuery(selector + '_type').val();
        var fieldAlias = mQuery(selector + '_field').val();
        var filterFieldEl = mQuery(selector + '_properties_filter');

        if (filterValue) {
            filterFieldEl.val(filterValue);
            if (filterFieldEl.is('select')) {
                filterFieldEl.trigger('chosen:updated');
            }
        }

        if (fieldType === 'lookup') {
            Mautic.activateLookupTypeahead(filterFieldEl.parent());
        } else if (fieldType === 'datetime') {
            filterFieldEl.datetimepicker({
                format: 'Y-m-d H:i',
                lazyInit: true,
                validateOnBlur: false,
                allowBlank: true,
                scrollMonth: false,
                scrollInput: false
            });
        } else if (fieldType === 'date') {
            filterFieldEl.datetimepicker({
                timepicker: false,
                format: 'Y-m-d',
                lazyInit: true,
                validateOnBlur: false,
                allowBlank: true,
                scrollMonth: false,
                scrollInput: false,
                closeOnDateSelect: true
            });
        } else if (fieldType === 'time') {
            filterFieldEl.datetimepicker({
                datepicker: false,
                format: 'H:i',
                lazyInit: true,
                validateOnBlur: false,
                allowBlank: true,
                scrollMonth: false,
                scrollInput: false
            });
        } else if (fieldType === 'lookup_id') {
            var displayFieldEl = mQuery(selector + '_properties_display');
            var fieldCallback = displayFieldEl.attr('data-field-callback');
            if (fieldCallback && typeof Mautic[fieldCallback] === 'function') {
                var fieldOptions = displayFieldEl.attr('data-field-list');
                Mautic[fieldCallback](selector.replace('#', '') + '_properties_display', fieldAlias, fieldOptions);
            }
        }
    });

    // Trigger event so plugins could attach other JS magic to the form.
    mQuery('#leadlist_filters .panel').each(function() {
        Mautic.triggerOnPropertiesFormLoadedEvent('#' + mQuery(this).attr('id'));
    });
};

Mautic.reorderSegmentFilters = function() {
    // Update the filter numbers sot that they are ordered correctly when processed and grouped server side
    var counter = 0;

    var prefix = 'leadlist';
    var parent = mQuery('.dynamic-content-filter, .dwc-filter');
    if (parent.length) {
        prefix = parent.attr('id');
    }

    mQuery('#' + prefix + '_filters .panel').each(function() {
        Mautic.updateFilterPositioning(mQuery(this).find('select.glue-select').first());
        mQuery(this).find('[id^="' + prefix + '_filters_"]').each(function() {
            var id     = mQuery(this).attr('id');
            var name   = mQuery(this).attr('name');
            var suffix = id.split(/[_]+/).pop();

            var isProperties = id.includes("_properties_");

            if (prefix + '_filters___name___filter' === id) {
                return true;
            }

            if (name) {
                if (isProperties){
                    var newName    = prefix + '[filters][' + counter + '][properties][' + suffix + ']';
                    var properties = 'properties_';
                }
                else {
                    var newName = prefix + '[filters][' + counter + '][' + suffix + ']';
                    var properties = '';
                }
                if (name.slice(-2) === '[]') {
                    newName += '[]';
                }

                mQuery(this).attr('name', newName);
                mQuery(this).attr('id', prefix + '_filters_' + counter + '_' + properties + suffix);
            }

            mQuery(this).attr('name', newName);
            mQuery(this).attr('id', prefix + '_filters_'+counter+'_'+suffix);

            // Destroy the chosen and recreate
            if (mQuery(this).is('select') && suffix == "filter") {
                Mautic.destroyChosen(mQuery(this));
                Mautic.activateChosenSelect(mQuery(this));
            }
        });

        ++counter;
    });

    mQuery('#' + prefix + '_filters .panel-heading').removeClass('hide');
    mQuery('#' + prefix + '_filters .panel-heading').first().addClass('hide');
};

Mautic.convertLeadFilterInput = function(el) {
    var operatorSelect = mQuery(el);

    // Extract the filter number
    var regExp = /_filters_(\d+)_operator/;
    var matches = regExp.exec(operatorSelect.attr('id'));
    var filterNum = matches[1];
    var fieldAlias = mQuery('#leadlist_filters_'+filterNum+'_field');
    var fieldObject = mQuery('#leadlist_filters_'+filterNum+'_object');
    var filterValue = mQuery('#leadlist_filters_'+filterNum+'_properties_filter').val();
    var filterId  = '#leadlist_filters_' + filterNum + '_properties_filter';

    Mautic.loadFilterForm(filterNum, fieldObject.val(), fieldAlias.val(), operatorSelect.val(), function(propertiesFields) {
        var selector = '#leadlist_filters_'+filterNum;
        mQuery(selector+'_properties').html(propertiesFields);

        Mautic.triggerOnPropertiesFormLoadedEvent(selector, filterValue);
    });

    Mautic.setProcessorForFilterValue(filterId, operatorSelect.val());
};

Mautic.setFilterValuesProcessor = function () {
    mQuery('.filter-operator').each(function (index) {
        let filterId = "#" + mQuery('.filter-value').eq(index).attr('id');
        Mautic.setProcessorForFilterValue(filterId, mQuery(this).val())
    });
};

Mautic.setProcessorForFilterValue = function (filterId, operator) {
    let isInOperator = (operator == 'in' || operator == '!in');
    if (isInOperator && mQuery(filterId).attr('type') === 'text') {
        mQuery(filterId).on('paste', function (e) {
            let value  = e.originalEvent.clipboardData.getData('text');
            value = value.replace(/\r?\n/g, '|');
            if (value.slice(-1) === '|') {
                value = value.slice(0, -1);
            }
            mQuery(filterId).val(value);
            e.preventDefault();
        });
    } else {
        mQuery(filterId).off('paste');
    }
};

/**
 * Adds values to the lookup_id form after user selects a typeahead option.
 */
Mautic.updateLookupListFilter = function(field, item) {
    if (item && item.id) {
        var filterField = '#'+field.replace('_display', '_filter');
        mQuery(filterField).val(item.id);
        mQuery(field).val(item.name);
    }
};

Mautic.activateSegmentFilterTypeahead = function(displayId, filterId, fieldOptions, mQueryObject) {

    var mQueryBackup = mQuery;

    if (typeof mQueryObject === 'function') {
        mQuery = mQueryObject;
    }

    mQuery('#' + displayId).attr('data-lookup-callback', 'updateLookupListFilter');

    Mautic.activateFieldTypeahead(displayId, filterId, [], mQuery('#' + displayId).data('action') || 'lead:fieldList');

    mQuery = mQueryBackup;
};

Mautic.loadFilterForm = function(filterNum, fieldObject, fieldAlias, operator, resultHtml, search = null) {
    mQuery.ajax({
        showLoadingBar: true,
        url: mauticAjaxUrl,
        type: 'POST',
        data: {
            action: 'lead:loadSegmentFilterForm',
            fieldAlias: fieldAlias,
            fieldObject: fieldObject,
            operator: operator,
            filterNum: filterNum,
            search: search,
        },
        dataType: 'json',
        success: function (response) {
            Mautic.stopPageLoadingBar();
            resultHtml(response.viewParameters.form);
            if (fieldAlias == 'lead_asset_download') {
                Mautic.handleAssetDownloadSearch(filterNum, fieldObject, fieldAlias, operator, resultHtml, search);
            }
        },
        error: function (request, textStatus, errorThrown) {
            Mautic.processAjaxError(request, textStatus, errorThrown);
        }
    });
}

Mautic.addLeadListFilter = function (elId, elObj) {
    var filterId = '#available_' + elObj + '_' + elId;
    var filterOption = mQuery(filterId);
    var label = filterOption.text();

    // Create a new filter

    var filterNum = parseInt(mQuery('.available-filters').data('index'));
    mQuery('.available-filters').data('index', filterNum + 1);

    var prototypeStr = mQuery('.available-filters').data('prototype');
    var fieldType = filterOption.data('field-type');
    var fieldObject = filterOption.data('field-object');

    prototypeStr = prototypeStr.replace(/__name__/g, filterNum);
    prototypeStr = prototypeStr.replace(/__label__/g, label);

    // Convert to DOM
    prototype = mQuery(prototypeStr);

    var prefix = 'leadlist';
    var parent = mQuery(filterId).parents('.dynamic-content-filter, .dwc-filter');
    if (parent.length) {
        prefix = parent.attr('id');
    }

    var filterBase  = prefix + "[filters][" + filterNum + "]";
    var filterIdBase = prefix + "_filters_" + filterNum + "_";

    if (mQuery('#' + prefix + '_filters div.panel').length == 0) {
        // First filter so hide the glue footer
        prototype.find(".panel-heading").addClass('hide');
    }

    if (fieldObject == 'company') {
        prototype.find(".object-icon").removeClass('fa-user').addClass('fa-building');
    } else {
        prototype.find(".object-icon").removeClass('fa-building').addClass('fa-user');
    }
    prototype.find(".inline-spacer").append(fieldObject);

    prototype.find("a.remove-selected").on('click', function() {
        mQuery(this).closest('.panel').animate(
            {'opacity': 0},
            'fast',
            function () {
                mQuery(this).remove();
                Mautic.reorderSegmentFilters();
            }
        );
    });

    prototype.find("input[name='" + filterBase + "[field]']").val(elId);
    prototype.find("input[name='" + filterBase + "[type]']").val(fieldType);
    prototype.find("input[name='" + filterBase + "[object]']").val(fieldObject);
    prototype.appendTo('#' + prefix + '_filters');

    var operators = filterOption.data('field-operators');
    mQuery('#' + filterIdBase + 'operator').html('');
    mQuery.each(operators, function (label, value) {
        var newOption = mQuery('<option/>').val(value).text(label);
        newOption.appendTo(mQuery('#' + filterIdBase + 'operator'));
    });

    // Convert based on first option in list
    Mautic.convertLeadFilterInput('#' + filterIdBase + 'operator');

    // Reposition if applicable
    Mautic.updateFilterPositioning(mQuery('#' + filterIdBase + 'glue'));
};

Mautic.leadfieldOnLoad = function (container) {
    if (mQuery(container + ' .leadfield-list').length) {
        var bodyOverflow = {};
        mQuery(container + ' .leadfield-list tbody').sortable({
            handle: '.fa-ellipsis-v',
            helper: function(e, ui) {
                ui.children().each(function() {
                    mQuery(this).width(mQuery(this).width());
                });

                // Fix body overflow that messes sortable up
                bodyOverflow.overflowX = mQuery('body').css('overflow-x');
                bodyOverflow.overflowY = mQuery('body').css('overflow-y');
                mQuery('body').css({
                    overflowX: 'visible',
                    overflowY: 'visible'
                });

                return ui;
            },
            scroll: false,
            axis: 'y',
            containment: container + ' .leadfield-list',
            stop: function(e, ui) {
                // Restore original overflow
                mQuery('body').css(bodyOverflow);

                // Get the page and limit
                mQuery.ajax({
                    type: "POST",
                    url: mauticAjaxUrl + "?action=lead:reorder&limit=" + mQuery('.pagination-limit').val() + '&page=' + mQuery('.pagination li.active a span').first().text(),
                    data: mQuery(container + ' .leadfield-list tbody').sortable("serialize")
                });
            }
        });
    }

    if (mQuery(container + ' form[name="leadfield"]').length) {
        Mautic.updateLeadFieldProperties(mQuery('#leadfield_type').val(), true);
    }

};

Mautic.updateLeadFieldProperties = function(selectedVal, onload) {
    let isMultiselect = selectedVal === 'multiselect' ? true : false;
    if (selectedVal === 'multiselect') {
        // Use select
        selectedVal = 'select';
    }

    if (mQuery('#field-templates .' + selectedVal).length) {
        mQuery('#leadfield_properties').html(
            mQuery('#field-templates .' + selectedVal).html()
                .replace(/leadfield_properties_template/g, 'leadfield_properties')
        );
        mQuery("#leadfield_properties *[data-toggle='sortablelist']").each(function (index) {
            var sortableList = mQuery(this);
            Mautic.activateSortable(this);
            // Using an interval so removing, adding, updating and reordering are accounted for
            var contactFieldListOptions = mQuery('#leadfield_properties').find('input').map(function() {
                return mQuery(this).val();
            }).get().join();
            var updateDefaultValuesetInterval = setInterval(function() {
                var evalListOptions = mQuery('#leadfield_properties').find('input').map(function() {
                    return mQuery(this).val();
                }).get().join();
                if (mQuery('#leadfield_properties_itemcount').length) {
                    if (contactFieldListOptions != evalListOptions) {
                        contactFieldListOptions = evalListOptions;
                        var selected = mQuery('#leadfield_defaultValue').val();
                        mQuery('#leadfield_defaultValue').html('<option value=""></option>');
                        var labels = mQuery('#leadfield_properties').find('input.sortable-label');
                        if (labels.length) {
                            labels.each(function () {
                                // label/value pairs
                                var label = mQuery(this).val();
                                var val = mQuery(this).closest('.row').find('input.sortable-value').first().val();
                                mQuery('<option value="' + val + '">' + label + '</option>').appendTo(mQuery('#leadfield_defaultValue'));
                            });
                        } else {
                            mQuery('#leadfield_properties .list-sortable').find('input').each(function () {
                                var val = mQuery(this).val();
                                mQuery('<option value="' + val + '">' + val + '</option>').appendTo(mQuery('#leadfield_defaultValue'));
                            });
                        }

                        mQuery('#leadfield_defaultValue').val(selected);
                        mQuery('#leadfield_defaultValue').trigger('chosen:updated');
                    }
                } else {
                    clearInterval(updateDefaultValuesetInterval);
                    delete contactFieldListOptions;
                }
            }, 500);
        });
    } else if (!mQuery('#leadfield_properties .' + selectedVal).length) {
        mQuery('#leadfield_properties').html('');
    }

    if (selectedVal == 'time') {
        mQuery('#leadfield_isListable').closest('.row').addClass('hide');
    } else {
        mQuery('#leadfield_isListable').closest('.row').removeClass('hide');
    }

    // Switch default field if applicable
    var defaultValueField = mQuery('#leadfield_defaultValue');
    if (defaultValueField.hasClass('calendar-activated')) {
        defaultValueField.datetimepicker('destroy').removeClass('calendar-activated');
    } else if (mQuery('#leadfield_defaultValue_chosen').length) {
        Mautic.destroyChosen(defaultValueField);
    }

    var defaultFieldType = mQuery('input[name="leadfield[defaultValue]"]').attr('type');
    var tempType = selectedVal;
    var html = '';
    var isSelect = false;
    var defaultVal = defaultValueField.val();
    switch (selectedVal) {
        case 'boolean':
            if (defaultFieldType != 'radio') {
                // Convert to a boolean type
                html = '<div id="leadfield_default_template_boolean">' + mQuery('#field-templates .default_template_boolean').html() + '</div>';
            }
            break;
        case 'country':
        case 'region':
        case 'locale':
        case 'timezone':
            html = mQuery('#field-templates .default_template_' + selectedVal).html();
            isSelect = true;
            break;
        case 'select':
        case 'lookup':
            html = mQuery('#field-templates .default_template_select').html();
            tempType = 'select';
            isSelect = true;
            break;
        case 'textarea':
            html = mQuery('#field-templates .default_template_textarea').html();
            break;
        default:
            html = mQuery('#field-templates .default_template_text').html();
            tempType = 'text';

            if (selectedVal == 'number' || selectedVal == 'tel' || selectedVal == 'url' || selectedVal == 'email') {
                var replace = 'type="text"';
                var regex = new RegExp(replace, "g");
                html = html.replace(regex, 'type="' + selectedVal + '"');
            }

            break;
    }

    if (html && !onload) {
        var replace = 'default_template_' + tempType;
        var regex = new RegExp(replace, "g");
        html = html.replace(regex, 'defaultValue')
        defaultValueField.replaceWith(mQuery(html));
        mQuery('#leadfield_defaultValue').val(defaultVal);
        if (isMultiselect) {
            mQuery('#leadfield_defaultValue').attr('multiple', 'multiple');
            mQuery('#leadfield_defaultValue').attr('name', mQuery('#leadfield_defaultValue').attr('name')+'[]');
        }
    }

    if (selectedVal === 'datetime' || selectedVal === 'date' || selectedVal === 'time') {
        Mautic.activateDateTimeInputs('#leadfield_defaultValue', selectedVal);
    } else if (isSelect) {
       Mautic.activateChosenSelect('#leadfield_defaultValue');
    }
};

Mautic.updateLeadFieldBooleanLabels = function(el, label) {
    mQuery('#leadfield_defaultValue_' + label).parent().find('span').text(
        mQuery(el).val()
    );
};

Mautic.refreshLeadSocialProfile = function(network, leadId, event) {
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

    Mautic.setFilterValuesProcessor();
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

Mautic.refreshLeadAuditLog = function(form) {
    Mautic.postForm(mQuery(form), function (response) {
        response.target = '#auditlog-table';
        mQuery('#AuditLogCount').html(response.auditLogCount);
        Mautic.processPageContent(response);
    });
};

Mautic.refreshLeadTimeline = function(form) {
    Mautic.postForm(mQuery(form), function (response) {
        response.target = '#timeline-table';
        mQuery('#TimelineCount').html(response.timelineCount);
        Mautic.processPageContent(response);
    });
};

Mautic.refreshLeadNotes = function(form) {
    Mautic.postForm(mQuery(form), function (response) {
        response.target = '#NoteList';
        mQuery('#NoteCount').html(response.noteCount);
        Mautic.processPageContent(response);
    });
};

Mautic.refreshSegmentContacts = function(form) {
    Mautic.postForm(mQuery(form), function (response) {
        response.target = '#contacts-container';
        Mautic.processPageContent(response);
    });
};

Mautic.toggleLeadList = function(toggleId, leadId, listId) {
    var action = mQuery('#' + toggleId).hasClass('fa-toggle-on') ? 'remove' : 'add';
    var query = "action=lead:toggleLeadList&leadId=" + leadId + "&listId=" + listId + "&listAction=" + action;

    Mautic.toggleLeadSwitch(toggleId, query, action);
};

Mautic.togglePreferredChannel = function(channel) {
    if (channel === 'all') {
        var channelsForm = mQuery('form[name="contact_channels"]');
        var status = channelsForm.find('#contact_channels_subscribed_channels_0:checked').length;
        channelsForm.find('tbody input:checkbox').each(function() {
            if (this.checked != status) {
                this.checked = status;
                Mautic.setPreferredChannel(this.value);
            }
        });
    } else {
        Mautic.setPreferredChannel(channel);
    }
};

Mautic.setPreferredChannel = function(channel) {
    mQuery( '#frequency_' + channel ).slideToggle();
    mQuery( '#frequency_' + channel ).removeClass('hide');
    if (mQuery('#' + channel)[0].checked) {
        mQuery('#is-contactable-' + channel).removeClass('text-muted');
        mQuery('#lead_contact_frequency_rules_frequency_number_' + channel).prop("disabled" , false).trigger("chosen:updated");
        mQuery('#preferred_' + channel).prop("disabled" , false);
        mQuery('#lead_contact_frequency_rules_frequency_time_' + channel).prop("disabled" , false).trigger("chosen:updated");
        mQuery('#lead_contact_frequency_rules_contact_pause_start_date_' + channel).prop("disabled" , false);
        mQuery('#lead_contact_frequency_rules_contact_pause_end_date_' + channel).prop("disabled" , false);
    } else {
        mQuery('#is-contactable-' + channel).addClass('text-muted');
        mQuery('#lead_contact_frequency_rules_frequency_number_' + channel).prop("disabled" , true).trigger("chosen:updated");
        mQuery('#preferred_' + channel).prop("disabled" , true);
        mQuery('#lead_contact_frequency_rules_frequency_time_' + channel).prop("disabled" , true).trigger("chosen:updated");
        mQuery('#lead_contact_frequency_rules_contact_pause_start_date_' + channel).prop("disabled" , true);
        mQuery('#lead_contact_frequency_rules_contact_pause_end_date_' + channel).prop("disabled" , true);
    }
};

Mautic.toggleCompanyLead = function(toggleId, leadId, companyId) {
    var action = mQuery('#' + toggleId).hasClass('fa-toggle-on') ? 'remove' : 'add';
    var query = "action=lead:toggleCompanyLead&leadId=" + leadId + "&companyId=" + companyId + "&companyAction=" + action;
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

        Mautic.makeModalsAlive(mQuery(el + " *[data-toggle='ajaxmodal']"));
        Mautic.makeConfirmationsAlive(mQuery(el+' a[data-toggle="confirmation"]'));
        Mautic.makeLinksAlive(mQuery(el + " a[data-toggle='ajax']"));
    } else if (response.deleteId && mQuery('#LeadNote' + response.deleteId).length) {
        mQuery('#LeadNote' + response.deleteId).remove();
    }

    if (response.upNoteCount || response.noteCount || response.downNoteCount) {
        var noteCountWrapper = mQuery('#NoteCount');
        var count = parseInt(noteCountWrapper.text().trim());

        if (response.upNoteCount) {
            count++;
        } else if (response.downNoteCount) {
            count--;
        } else {
            count = parseInt(response.noteCount);
        }

        noteCountWrapper.text(count);
    }
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
        }, false, false, "GET");

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

Mautic.removeBounceStatus = function (el, dncId, channel) {
    mQuery(el).removeClass('fa-times').addClass('fa-spinner fa-spin');

    Mautic.ajaxActionRequest('lead:removeBounceStatus', {'id': dncId, 'channel': channel}, function() {
        mQuery('#bounceLabel' + dncId).tooltip('destroy');
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
                    if (mQuery('.shuffle-grid').length) {
                        //give a slight delay in order for images to load so that shuffle starts out with correct dimensions
                        var Shuffle = window.Shuffle,
                            element = document.querySelector('.shuffle-grid'),
                            shuffleOptions = {
                                itemSelector: '.shuffle-item'
                            };

                        // Using global variable to make it available outside of the scope of this function
                        window.leadsShuffleInstance = new Shuffle(element, shuffleOptions);
                        var items = mQuery(response.leads);
                        mQuery('.shuffle-grid').prepend(items);
                        window.leadsShuffleInstance.shuffle('appended', items.children(shuffleOptions.itemSelector).toArray());
                        window.leadsShuffleInstance.shuffle('update');
                    }

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

Mautic.toggleAnonymousLeads = function() {
    var searchValue = mQuery('#list-search').typeahead('val');
    var string      = mQuery('#anonymousLeadButton').data('anonymous').toLowerCase();

    if (searchValue.toLowerCase().indexOf('!' + string) == 0) {
        searchValue = searchValue.replace('!' + string, string);
        mQuery('#anonymousLeadButton').addClass('btn-primary');
    } else if (searchValue.toLowerCase().indexOf(string) == -1) {
        if (searchValue) {
            searchValue = searchValue + ' ' + string;
        } else {
            searchValue = string;
        }
        mQuery('#anonymousLeadButton').addClass('btn-primary');
    } else {
        searchValue = mQuery.trim(searchValue.replace(string, ''));
        mQuery('#anonymousLeadButton').removeClass('btn-primary');
    }
    searchValue = searchValue.replace("  ", " ");
    Mautic.setSearchFilter(null, 'list-search', searchValue);
};

Mautic.getLeadEmailContent = function (el) {
    var id = (mQuery.type( el ) === "string") ? el : mQuery(el).attr('id');
    Mautic.activateLabelLoadingIndicator(id);

    var inModal = mQuery('#'+id).closest('modal').length;
    if (inModal) {
        mQuery('#MauticSharedModal .btn-primary').prop('disabled', true);
    }

    Mautic.ajaxActionRequest('lead:getEmailTemplate', {'template': mQuery(el).val()}, function(response) {
        if (inModal) {
            mQuery('#MauticSharedModal .btn-primary').prop('disabled', false);
        }
        var idPrefix = id.replace('templates', '');
        var bodyEl = (mQuery('#'+idPrefix+'message').length) ? '#'+idPrefix+'message' : '#'+idPrefix+'body';

        if (mauticFroalaEnabled && Mautic.getActiveBuilderName() === 'legacy') {
            mQuery(bodyEl).froalaEditor('html.set', response.body);
        } else {
            ckEditors.get( mQuery(bodyEl)[0] ).setData(response.body);
        }

        mQuery(bodyEl).val(response.body);
        mQuery('#'+idPrefix+'subject').val(response.subject);

        Mautic.removeLabelLoadingIndicator();
    }, false, false, "GET");
};

Mautic.updateLeadTags = function () {
    Mautic.activateLabelLoadingIndicator('lead_tags_tags');
    var formData = mQuery('form[name="lead_tags"]').serialize();
    Mautic.ajaxActionRequest('lead:updateLeadTags', formData, function(response) {
        if (response.tags) {
            mQuery('#lead_tags_tags').html(response.tags);
            mQuery('#lead_tags_tags').trigger('chosen:updated');
        }
        Mautic.removeLabelLoadingIndicator();
    });
};

Mautic.createLeadTag = function (el) {
    var newFound = false;
    mQuery('#' + mQuery(el).attr('id') + ' :selected').each(function(i, selected) {
        if (!mQuery.isNumeric(mQuery(selected).val())) {
            newFound = true;
        }
    });

    if (!newFound) {
        return;
    }

    Mautic.activateLabelLoadingIndicator(mQuery(el).attr('id'));

    var tags = JSON.stringify(mQuery(el).val());

    Mautic.ajaxActionRequest('lead:addLeadTags', {tags: tags}, function(response) {
        if (response.tags) {
            mQuery('#' + mQuery(el).attr('id')).html(response.tags);
            mQuery('#' + mQuery(el).attr('id')).trigger('chosen:updated');
        }

        Mautic.removeLabelLoadingIndicator();
    });
};

Mautic.createLeadUtmTag = function (el) {
    var newFound = false;
    mQuery('#' + mQuery(el).attr('id') + ' :selected').each(function(i, selected) {
        if (!mQuery.isNumeric(mQuery(selected).val())) {
            newFound = true;
        }
    });

    if (!newFound) {
        return;
    }

    Mautic.activateLabelLoadingIndicator(mQuery(el).attr('id'));

    var utmtags = JSON.stringify(mQuery(el).val());

    Mautic.ajaxActionRequest('lead:addLeadUtmTags', {utmtags: utmtags}, function(response) {
        if (response.tags) {
            mQuery('#' + mQuery(el).attr('id')).html(response.utmtags);
            mQuery('#' + mQuery(el).attr('id')).trigger('chosen:updated');
        }

        Mautic.removeLabelLoadingIndicator();
    });
};

Mautic.leadBatchSubmit = function() {
    if (Mautic.batchActionPrecheck()) {

        if (mQuery('#lead_batch_remove').val() || mQuery('#lead_batch_add').val() || mQuery('#lead_batch_dnc_reason').length || mQuery('#lead_batch_stage_addstage').length || mQuery('#lead_batch_owner_addowner').length || mQuery('#contact_channels_ids').length) {
            var ids = Mautic.getCheckedListIds(false, true);

            if (mQuery('#lead_batch_ids').length) {
                mQuery('#lead_batch_ids').val(ids);
            } else if (mQuery('#lead_batch_dnc_reason').length) {
                mQuery('#lead_batch_dnc_ids').val(ids);
            } else if (mQuery('#lead_batch_stage_addstage').length) {
                mQuery('#lead_batch_stage_ids').val(ids);
            } else if (mQuery('#lead_batch_owner_addowner').length) {
                mQuery('#lead_batch_owner_ids').val(ids);
            } else if (mQuery('#contact_channels_ids').length) {
                mQuery('#contact_channels_ids').val(ids);
            }

            return true;
        }

    }

    mQuery('#MauticSharedModal').modal('hide');

    return false;
};

Mautic.updateLeadFieldValues = function (field) {
    mQuery('.condition-custom-date-row').hide();
    Mautic.updateFieldOperatorValue(field, 'lead:updateLeadFieldValues', Mautic.updateLeadFieldValueOptions, [true]);
};

Mautic.updateLeadFieldValueOptions = function (field, updating) {
    var fieldId = mQuery(field).attr('id');
    var fieldPrefix = fieldId.slice(0, -5);

    if ('date' === mQuery('#'+fieldPrefix + 'operator').val()) {
        var customOption = mQuery(field).find('option[data-custom=1]');
        var value        = mQuery(field).val();

        var customSelected = mQuery(customOption).prop('selected');
        if (customSelected) {
            if (!updating) {
                // -/+ P/PT number unit
                var regex = /(\+|-)(PT?)([0-9]*)([DMHY])$/g;
                var match = regex.exec(value);
                if (match) {
                    var interval = ('-' === match[1]) ? match[1] + match[3] : match[3];
                    var unit = ('PT' === match[2] && 'M' === match[4]) ? 'i' : match[4];

                    mQuery('#lead-field-custom-date-interval').val(interval);
                    mQuery('#lead-field-custom-date-unit').val(unit.toLowerCase());
                }
            } else {
                var interval = mQuery('#lead-field-custom-date-interval').val();
                var unit = mQuery('#lead-field-custom-date-unit').val();

                // Convert interval/unit into PHP a DateInterval format
                var prefix = ("i" == unit || "h" == unit) ? "PT" : "P";
                // DateInterval uses M for minutes instead of i
                if ("i" === unit) {
                    unit = "m";
                }

                unit = unit.toUpperCase();

                var operator = "+";
                if (parseInt(interval) < 0) {
                    operator = "-";
                    interval = -1 * parseInt(interval);
                }
                var newValue = operator + prefix + interval + unit;
                customOption.attr('value', newValue);
            }
            mQuery('.condition-custom-date-row').show();
        } else {
            mQuery('.condition-custom-date-row').hide();
        }
    } else {
        mQuery('.condition-custom-date-row').hide();
    }
};

Mautic.toggleTimelineMoreVisiblity = function (el) {
    if (mQuery(el).is(':visible')) {
        mQuery(el).slideUp('fast');
        mQuery(el).next().text(mauticLang['showMore']);
    } else {
        mQuery(el).slideDown('fast');
        mQuery(el).next().text(mauticLang['hideMore']);
    }
};

Mautic.displayUniqueIdentifierWarning = function (el) {
    if (mQuery(el).val() === "0") {
        mQuery('.unique-identifier-warning').fadeOut('fast');
    } else {
        mQuery('.unique-identifier-warning').fadeIn('fast');
    }
};

Mautic.initUniqueIdentifierFields = function() {
    var uniqueFields = mQuery('[data-unique-identifier]');
    if (uniqueFields.length) {
        uniqueFields.on('change', function() {
            var input = mQuery(this);
            var request = {
                field: input.data('unique-identifier'),
                value: input.val(),
                ignore: mQuery('#lead_unlockId').val()
            };
            Mautic.ajaxActionRequest('lead:getLeadIdsByFieldValue', request, function(response) {
                if (response.items !== 'undefined' && response.items.length) {
                    var warning = mQuery('<div class="exists-warning" />').text(response.existsMessage);
                    mQuery.each(response.items, function(i, item) {
                        if (i > 0) {
                            warning.append(mQuery('<span>, </span>'));
                        }

                        var link = mQuery('<a/>')
                            .attr('href', item.link)
                            .attr('target', '_blank')
                            .text(item.name+' ('+item.id+')');
                        warning.append(link);
                    });
                    warning.appendTo(input.parent());
                } else {
                    input.parent().find('div.exists-warning').remove();
                }
            }, false, false, "GET");
        });
    }
};

Mautic.updateFilterPositioning = function (el) {
    var $el       = mQuery(el);
    var $parentEl = $el.closest('.panel');
    var list      = $parentEl.parent().children('.panel');
    const isFirst = list.index($parentEl) === 0;

    if (isFirst) {
        $el.val('and');
    }

    if ($el.val() === 'and' && !isFirst) {
        $parentEl.addClass('in-group');
    } else {
        $parentEl.removeClass('in-group');
    }
};

Mautic.setAsPrimaryCompany = function (companyId,leadId){
    Mautic.ajaxActionRequest('lead:setAsPrimaryCompany', {'companyId': companyId, 'leadId': leadId}, function(response) {
        if (response.success) {
            if (response.oldPrimary == response.newPrimary && mQuery('#company-' + response.oldPrimary).hasClass('primary')) {
                mQuery('#company-' + response.oldPrimary).removeClass('primary');
            } else {
                mQuery('#company-' + response.oldPrimary).removeClass('primary');
                mQuery('#company-' + response.newPrimary).addClass('primary');
            }

        }
    });
};

Mautic.handleAssetDownloadSearch = function(filterNum, fieldObject, fieldAlias, operator, resultHtml, search) {
    var assetDownloadFilter = mQuery('#leadlist_filters_' + filterNum + '_properties_filter');
    var assetDownloadInput = mQuery('#leadlist_filters_' + filterNum + '_properties input');
    var assetDownloadProperties = mQuery('#leadlist_filters_' + filterNum + '_properties');
    assetDownloadFilter.on('chosen:no_results', function () {
        var search = assetDownloadInput.val();
        mQuery('#leadlist_filters_' + filterNum + '_properties .chosen-drop').remove();
        clearTimeout(mQuery.data(this, 'timer'));
        var existingOptions = mQuery('#leadlist_filters_' + filterNum + '_properties_filter option');
        mQuery(assetDownloadProperties).data('existing-options', existingOptions);
        mQuery(this).data('timer', setTimeout(function () {
            assetDownloadInput.width('auto').prop('disabled', true).val(Mautic.translate('mautic.core.lookup.loading_data'));
            Mautic.loadFilterForm(filterNum, fieldObject, fieldAlias, operator, resultHtml, search)
        }, 1000, search))
    });
    var existingOptions = mQuery(assetDownloadProperties).data('existing-options');
    assetDownloadFilter.append(existingOptions);
    assetDownloadFilter.trigger('chosen:updated');
    if (mQuery('#leadlist_filters_' + filterNum + '_properties_filter option').length === 0 ) {
        assetDownloadInput.val(mauticLang['chosenNoResults']);
    }
    else if (search !== null) {
        assetDownloadFilter.trigger('chosen:open.chosen')
    }
};

Mautic.listOnLoad = function(container, response) {
    Mautic.lazyLoadContactListOnSegmentDetail();

    const segmentDependenciesTab = mQuery('a#segment-dependencies');
    let segmentDependenciesLoaded = false;
    let jsPlumbData = null;
    
    if (segmentDependenciesTab.length) {
        mQuery(document).on('shown.bs.tab', 'a[data-toggle="tab"]', function (e) {
            if (!mQuery(e.target).attr('id') === 'segment-dependencies') {
                return;
            }

            if (!segmentDependenciesLoaded) {
                segmentDependenciesLoaded = true;
                mQuery.ajax({
                    showLoadingBar: true,
                    url: mauticAjaxUrl,
                    type: 'GET',
                    data: {
                        action: 'lead:getSegmentDependencyTree',
                        id: mQuery('input#entityId').val()
                    },
                    dataType: 'json',
                    success: function (response) {
                        Mautic.stopPageLoadingBar();
                        Mautic.renderSegmentTree('#segment-dependencies-container', response);
                        jsPlumbData = response;
                    },
                    error: function (request, textStatus, errorThrown) {
                        Mautic.processAjaxError(request, textStatus, errorThrown);
                    }
                });
            } else if (jsPlumbData) {
                Mautic.renderSegmentTree('#segment-dependencies-container', jsPlumbData);
            }
        });

        mQuery(document).on('hide.bs.tab', 'a[data-toggle="tab"]', function (e) {
            if (!mQuery(e.target).attr('id') !== 'segment-dependencies') {
                Mautic.cleanSegmentDependencies();
            }
        });
    }
};

Mautic.listOnUnload = function() {
    Mautic.cleanSegmentDependencies();
}

/**
 *  JsPlumb has a problem with z-index when using tabs or change content by ajax so we need to re-initialize it.
 */
Mautic.cleanSegmentDependencies = function() {
    mQuery('.jtk-connector').remove();
    mQuery('#segment-dependencies-container').empty();
}

Mautic.renderSegmentTree = function(containerId, data) {
    Mautic.cleanSegmentDependencies(); // Make sure there is no tree rendered already

    const plumbInstance = jsPlumb.getInstance({
        elementsDraggable:false,
        container: document.querySelector(containerId)
    });

    const wrapper = mQuery(containerId);
    const nodes = {};

    for (let level = 0; level < data.levels.length; level++) {
        const row = mQuery('<div class="segment-level" id="segment-level-'+level+'"></div>');
        wrapper.append(row);
        for (let index = 0; index < data.levels[level].nodes.length; index++) {
            const nodeData = data.levels[level].nodes[index];
            const node = Mautic.buildSegmentDependencyNode(nodeData);
            row.append(node);
            nodes[nodeData['id']] = node;
        }
    }

    for (let index = 0; index < data.edges.length; index++) {
        const edge = data.edges[index];
        plumbInstance.connect({
            source:nodes[edge.source],
            target:nodes[edge.target],
            connector: 'Flowchart',
            anchor: ['Top', 'Bottom'],
            endpoint:"Blank",
        });
    }

    return plumbInstance;
}

Mautic.buildSegmentDependencyNode = function(nodeData) {
    let message = '';
    let hasMessageClass = '';

    if (nodeData['message']) {
        message = '<span class="segment-dependency-message text-danger">'+nodeData['message']+'</span>';
        hasMessageClass = ' has-message';
    }

    const link = '<a href="'+nodeData['link']+'" data-toggle="ajax">'+nodeData['name']+'</a>';

    const node = mQuery('<div class="segment-node'+hasMessageClass+'" id="segment-node'+nodeData['id']+'">'+link+message+'</div>');

    return node;
}

Mautic.lazyLoadContactListOnSegmentDetail = function() {
    const containerId = '#contacts-container';
    const container = mQuery(containerId);

    // Load the contacts only if the container exists.
    if (!container.length) {
        return;
    }

    const segmentContactUrl = container.data('target-url');
    mQuery.get(segmentContactUrl, function(response) {
        response.target = containerId;
        Mautic.processPageContent(response);
    });
};

Mautic.lazyLoadContactStatsOnLeadLoad = function() {
    const containerId = '#lead-stats';
    const container = mQuery(containerId);

    // Load the contact stats only if the container exists.
    if (!container.length) {
        return;
    }

    const contactStatsUrl = container.data('target-url');
    mQuery.get(contactStatsUrl, function(response) {
        response.target = containerId;
        Mautic.processPageContent(response);
    });
};
