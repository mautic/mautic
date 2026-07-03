Mautic.loadSegmentFilterForm = function(ajaxAction, filterNum, fieldObject, fieldAlias, operator, resultHtml) {
    mQuery.ajax({
        showLoadingBar: true,
        url: mauticAjaxUrl,
        type: 'POST',
        data: { action: ajaxAction, fieldAlias, fieldObject, operator, filterNum },
        dataType: 'json',
        success: function(response) {
            Mautic.stopPageLoadingBar();
            resultHtml(response.viewParameters.form);
        },
        error: function(request, textStatus, errorThrown) {
            Mautic.processAjaxError(request, textStatus, errorThrown);
        }
    });
};

Mautic.convertSegmentFilterInput = function(el, prefix, loadFormFn, triggerFn, afterLoadFn) {
    const operatorSelect = mQuery(el);
    const filterNum = /_filters_(\d+)_operator/.exec(operatorSelect.attr('id'))[1];
    const fieldAlias = mQuery('#' + prefix + '_filters_' + filterNum + '_field');
    const fieldObject = mQuery('#' + prefix + '_filters_' + filterNum + '_object');
    const filterValue = mQuery('#' + prefix + '_filters_' + filterNum + '_properties_filter').val();
    const filterId = '#' + prefix + '_filters_' + filterNum + '_properties_filter';

    loadFormFn(filterNum, fieldObject.val(), fieldAlias.val(), operatorSelect.val(), function(propertiesFields) {
        const selector = '#' + prefix + '_filters_' + filterNum;
        mQuery(selector + '_properties').html(propertiesFields);
        if (afterLoadFn) afterLoadFn();
        triggerFn(selector, filterValue);
    });

    Mautic.setProcessorForFilterValue(filterId, operatorSelect.val());
};

Mautic.applySegmentFilterFieldUi = function(selector, filterValue) {
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
};
