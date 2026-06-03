Mautic.company_segmentsOnLoad = function(container, response) {
    const segmentCountElem = mQuery('a.col-count');

    if (segmentCountElem.length) {
        segmentCountElem.each(function() {
            const elem = mQuery(this);
            const id = elem.attr('data-id');

            Mautic.ajaxActionRequest(
                'lead:getCompaniesCount',
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

    if (mQuery(container + ' #list-search').length) {
        Mautic.activateSearchAutocomplete('list-search', 'lead.company_segment');
    }

    let prefix = 'company_segments';
    const parent = mQuery('.dynamic-content-filter, .dwc-filter');
    if (parent.length) {
        prefix = parent.attr('id');
    }

    if (mQuery('#' + prefix + '_filters').length) {
        mQuery('#available_' + prefix + '_filters').on('change', function() {
            if (mQuery(this).val()) {
                Mautic.addCompanySegmentsFilter(mQuery(this).val(),mQuery('option:selected',this).data('field-object'));
                mQuery(this).val('');
                mQuery(this).trigger('chosen:updated');
            }
        });

        mQuery('#' + prefix + '_filters .remove-selected').each( function (index, el) {
            mQuery(el).on('click', function () {
                mQuery(this).closest('.filter--row').animate(
                    {'opacity': 0},
                    'fast',
                    function () {
                        mQuery(this).remove();
                        Mautic.reorderCompanySegmentFilters();
                    }
                );

                if (!mQuery('#' + prefix + '_filters .filter--row:not(.placeholder)').length) {
                    mQuery('#' + prefix + '_filters .placeholder').removeClass('hide');
                } else {
                    mQuery('#' + prefix + '_filters .placeholder').addClass('hide');
                }
            });
        });

        mQuery('#' + prefix + '_filters .copy-filter-group').each(function(index, el) {
            mQuery(el).on('click', function() {
                const filterRow = mQuery(this).closest('.filter--row');
                const clonedFilter = filterRow.clone(true);

                clonedFilter.insertAfter(filterRow);

                Mautic.reorderCompanySegmentFilters();
            });
        });

        const bodyOverflow = {};
        mQuery('#' + prefix + '_filters').sortable({
            items: '.filter--row',
            handle: '.ri-draggable',
            helper: function(e, ui) {
                ui.children().each(function() {
                    if (mQuery(this).is(":visible")) {
                        mQuery(this).width(mQuery(this).width());
                    }
                });

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
                mQuery('body').css(bodyOverflow);

                ui.item.find('select.glue-select').first().val('and');

                Mautic.reorderCompanySegmentFilters();
            }
        });

    }

    jQuery(document).ajaxComplete(function(){
        Mautic.ajaxifyForm('daterange');
    });

    Mautic.attachJsUiOnCompanySegmentsFilterForms();
};

Mautic.addCompanySegmentsFilter = function (elId, elObj) {
    const filterId = '#available_' + elObj + '_' + elId;
    const filterOption = mQuery(filterId);
    const label = filterOption.text();

    const filterNum = parseInt(mQuery('.available-filters').data('index'));
    mQuery('.available-filters').data('index', filterNum + 1);

    let prototypeStr = mQuery('.available-filters').data('prototype');
    const fieldType = filterOption.data('field-type');
    const fieldObject = filterOption.data('field-object');

    prototypeStr = prototypeStr.replace(/__name__/g, filterNum);
    prototypeStr = prototypeStr.replace(/__label__/g, label);

    const prototype = mQuery(prototypeStr);

    let prefix = 'company_segments';
    const parent = mQuery(filterId).parents('.dynamic-content-filter, .dwc-filter');
    if (parent.length) {
        prefix = parent.attr('id');
    }

    const filterBase  = prefix + "[filters][" + filterNum + "]";
    const filterIdBase = prefix + "_filters_" + filterNum + "_";

    if (mQuery('#' + prefix + '_filters .filter--row').length === 0) {
        prototype.find(".filter--condition-when").removeClass('hide');
        prototype.find(".filter--condition").addClass('hide');
    }

    prototype.find("a.remove-selected").on('click', function() {
        mQuery(this).closest('.filter--row').animate(
            {'opacity': 0},
            'fast',
            function () {
                mQuery(this).remove();
                Mautic.reorderCompanySegmentFilters();
            }
        );
    });

    prototype.find("a.copy-filter-group").on('click', function() {
        const filterRow = mQuery(this).closest('.filter--row');
        const clonedFilter = filterRow.clone(true);

        clonedFilter.insertAfter(filterRow);

        Mautic.reorderCompanySegmentFilters();
    });

    const filterTypeIcon = filterOption.data('field-icon');
    prototype.find('.object-icon').addClass(filterTypeIcon);

    prototype.find("input[name='" + filterBase + "[field]']").val(elId);
    prototype.find("input[name='" + filterBase + "[type]']").val(fieldType);
    prototype.find("input[name='" + filterBase + "[object]']").val(fieldObject);
    prototype.appendTo('#' + prefix + '_filters');

    const operators = filterOption.data('field-operators');
    mQuery('#' + filterIdBase + 'operator').html('');
    mQuery.each(operators, function (label, value) {
        const newOption = mQuery('<option/>').val(value).text(label);
        newOption.appendTo(mQuery('#' + filterIdBase + 'operator'));
    });

    Mautic.convertCompanySegmentFilterInput('#' + filterIdBase + 'operator');
    Mautic.updateCompanySegmentFilterPositioning(mQuery('#' + filterIdBase + 'glue'));
};

Mautic.convertCompanySegmentFilterInput = function(el) {
    const operatorSelect = mQuery(el);

    // Extract the filter number
    const regExp = /_filters_(\d+)_operator/;
    const matches = regExp.exec(operatorSelect.attr('id'));
    const filterNum = matches[1];
    const fieldAlias = mQuery('#company_segments_filters_'+filterNum+'_field');
    const fieldObject = mQuery('#company_segments_filters_'+filterNum+'_object');
    const filterValue = mQuery('#company_segments_filters_'+filterNum+'_properties_filter').val();
    const filterId  = '#company_segments_filters_' + filterNum + '_properties_filter';

    Mautic.loadCompanyFilterForm(filterNum, fieldObject.val(), fieldAlias.val(), operatorSelect.val(), function(propertiesFields) {
        const selector = '#company_segments_filters_'+filterNum;
        mQuery(selector+'_properties').html(propertiesFields);

        Mautic.triggerOnCompanySegmentPropertiesFormLoadedEvent(selector, filterValue);
    });

    Mautic.setProcessorForFilterValue(filterId, operatorSelect.val());
};

Mautic.loadCompanyFilterForm = function(filterNum, fieldObject, fieldAlias, operator, resultHtml) {
    mQuery.ajax({
        showLoadingBar: true,
        url: mauticAjaxUrl,
        type: 'POST',
        data: {
            action: 'lead:loadCompanySegmentFilterForm',
            fieldAlias: fieldAlias,
            fieldObject: fieldObject,
            operator: operator,
            filterNum: filterNum,
        },
        dataType: 'json',
        success: function (response) {
            Mautic.stopPageLoadingBar();
            resultHtml(response.viewParameters.form);
        },
        error: function (request, textStatus, errorThrown) {
            Mautic.processAjaxError(request, textStatus, errorThrown);
        }
    });
}

Mautic.triggerOnCompanySegmentPropertiesFormLoadedEvent = function(selector, filterValue) {
    mQuery('#company_segments_filters').trigger('filter.properties.form.loaded', [selector, filterValue]);
};

Mautic.attachJsUiOnCompanySegmentsFilterForms = function() {
    mQuery('#company_segments_filters').on('filter.properties.form.loaded', function(event, selector, filterValue) {
        Mautic.activateChosenSelect(selector + '_properties select');
        const fieldType = mQuery(selector + '_type').val();
        const fieldAlias = mQuery(selector + '_field').val();
        const filterFieldEl = mQuery(selector + '_properties_filter');

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
            const displayFieldEl = mQuery(selector + '_properties_display');
            const fieldCallback = displayFieldEl.attr('data-field-callback');
            if (fieldCallback && typeof Mautic[fieldCallback] === 'function') {
                const fieldOptions = displayFieldEl.attr('data-field-list');
                Mautic[fieldCallback](selector.replace('#', '') + '_properties_display', fieldAlias, fieldOptions);
            }
        }
    });

    // Trigger event so plugins could attach other JS magic to the form.
    mQuery('#company_segments_filters .filter--row').each(function() {
        Mautic.triggerOnCompanySegmentPropertiesFormLoadedEvent('#' + mQuery(this).attr('id'));
    });
};

Mautic.reorderCompanySegmentFilters = function() {
    let counter = 0;

    let prefix = 'company_segments';
    const parent = mQuery('.dynamic-content-filter, .dwc-filter');
    if (parent.length) {
        prefix = parent.attr('id');
    }

    mQuery('#' + prefix + '_filters .filter--row').each(function() {
        Mautic.updateCompanySegmentFilterPositioning(mQuery(this).find('select.glue-select').first());
        mQuery(this).find('[id^="' + prefix + '_filters_"]').each(function() {
            const id     = mQuery(this).attr('id');
            const name   = mQuery(this).attr('name');
            const suffix = id.split(/[_]+/).pop();

            const isProperties = id.includes("_properties_");

            if (prefix + '_filters___name___filter' === id) {
                return true;
            }

            if (name) {
                let newName, properties;
                if (isProperties){
                    newName    = prefix + '[filters][' + counter + '][properties][' + suffix + ']';
                    properties = 'properties_';
                }
                else {
                    newName = prefix + '[filters][' + counter + '][' + suffix + ']';
                    properties = '';
                }
                if (name.slice(-2) === '[]') {
                    newName += '[]';
                }

                mQuery(this).attr('name', newName);
                mQuery(this).attr('id', prefix + '_filters_' + counter + '_' + properties + suffix);
            }

            // Destroy the chosen and recreate
            if (mQuery(this).is('select') && suffix == "filter") {
                Mautic.destroyChosen(mQuery(this));
                Mautic.activateChosenSelect(mQuery(this));
            }
        });

        ++counter;
    });

    // Update visibility of "when" vs "and/or" selectors
    mQuery('#' + prefix + '_filters .filter--condition').removeClass('hide');
    mQuery('#' + prefix + '_filters .filter--condition-when').addClass('hide');
    mQuery('#' + prefix + '_filters .filter--row').first().find('.filter--condition-when').removeClass('hide');
    mQuery('#' + prefix + '_filters .filter--row').first().find('.filter--condition').addClass('hide');
};

Mautic.updateCompanySegmentFilterPositioning = function (el) {
    const $el       = mQuery(el);
    const $parentEl = $el.closest('.filter--row');
    const list      = $parentEl.parent().children('.filter--row');
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

Mautic.companyBatchSubmit = function() {
    if (Mautic.batchActionPrecheck()) {
        if (mQuery('#company_batch_remove').val() || mQuery('#company_batch_add').val()) {
            const ids = Mautic.getCheckedListIds(false, true);

            if (mQuery('#company_batch_ids').length) {
                mQuery('#company_batch_ids').val(ids);
            }

            return true;
        }
    }

    mQuery('#MauticSharedModal').modal('hide');

    return false;
};
