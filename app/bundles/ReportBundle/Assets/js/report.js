//ReportBundle
Mautic.reportOnLoad = function (container) {
    // Activate search if the container exists
    if (mQuery(container + ' #list-search').length) {
        Mautic.activateSearchAutocomplete('list-search', 'report');
    }

    // Append an index of the number of filters on the edit form
    if (mQuery('div[id=report_filters]').length) {
        mQuery('div[id=report_filters]').data('index', mQuery('#report_filters > div').length);

        mQuery('div[id=report_tableOrder]').data('index', mQuery('#report_tableOrder > div').length);

        if (mQuery('.filter-columns').length) {
            mQuery('.filter-columns').each(function () {
                Mautic.updateReportFilterValueInput(this, true);
                mQuery(this).on('change', function () {
                    Mautic.updateReportFilterValueInput(this);
                });
            });
        }
    } else {
        mQuery('#report-shelves .collapse').on('show.bs.collapse', function (e) {
            var actives = mQuery('#report-shelves').find('.in, .collapsing');
            actives.each(function (index, element) {
                mQuery(element).collapse('hide');
                var id = mQuery(element).attr('id');
                mQuery('a[aria-controls="' + id + '"]').addClass('collapsed');
            })
        })
    }

    Mautic.initDateRangePicker();
};

/**
 * Written with inspiration from http://symfony.com/doc/current/cookbook/form/form_collections.html#allowing-new-tags-with-the-prototype
 */
Mautic.addReportRow = function (elId) {
    // Container with the prototype markup
    var prototypeHolder = mQuery('div[id="' + elId + '"]');

    // Fetch the index
    var index = parseInt(prototypeHolder.attr('data-index'));
    if (!index) {
        index = 1;
    }

    // Fetch the prototype markup
    var prototype = prototypeHolder.data('prototype');

    // Replace the placeholder with our index
    var output = prototype.replace(/__name__/g, index);

    // Increase the index for the next row
    prototypeHolder.attr('data-index', index + 1);

    // Render the new row
    prototypeHolder.append(output);

    var newColumnId = '#' + elId + '_' + index + '_column';

    if (elId == 'report_filters') {
        if (typeof Mautic.reportPrototypeFilterOptions != 'undefined') {
            // Update the column options if applicable
            mQuery(newColumnId).html(Mautic.reportPrototypeFilterOptions);
        }

        mQuery(newColumnId).on('change', function () {
            Mautic.updateReportFilterValueInput(this);
        });
        Mautic.updateReportFilterValueInput(newColumnId);
    } else if (typeof Mautic.reportPrototypeColumnOptions != 'undefined') {
        // Update the column options if applicable
        mQuery(newColumnId).html(Mautic.reportPrototypeColumnOptions);
    }

    Mautic.activateChosenSelect(mQuery('#' + elId + '_' + index + '_column'));
    mQuery("#" + elId + " *[data-toggle='tooltip']").tooltip({html: true, container: 'body'});

};

Mautic.updateReportFilterValueInput = function (filterColumn, setup) {
    var definitions = (typeof Mautic.reportPrototypeFilterDefinitions != 'undefined') ? Mautic.reportPrototypeFilterDefinitions : mQuery('#report_filters').data('filter-definitions');
    var operators = (typeof Mautic.reportPrototypeFilterOperators != 'undefined') ? Mautic.reportPrototypeFilterOperators : mQuery('#report_filters').data('filter-operators');

    var newValue = mQuery(filterColumn).val();
    if (!newValue) {

        return;
    }

    var filterId = mQuery(filterColumn).attr('id');
    var filterType = definitions[newValue].type;

    // Get the value element
    var valueEl = mQuery(filterColumn).parent().parent().find('.filter-value');
    var valueVal = valueEl.val();

    var idParts = filterId.split("_");
    var valueId = 'report_filters_' + idParts[2] + '_value';
    var valueName = 'report[filters][' + idParts[2] + '][value]';

    // Replace the condition list with operators
    var currentOperator = mQuery('#report_filters_' + idParts[2] + '_condition').val();
    mQuery('#report_filters_' + idParts[2] + '_condition').html(operators[newValue]);
    if (mQuery('#report_filters_' + idParts[2] + '_condition option[value="' + currentOperator + '"]').length > 0) {
        mQuery('#report_filters_' + idParts[2] + '_condition').val(currentOperator);
    }

    // Replace the value field appropriately
    if (mQuery('#' + valueId + '_chosen').length) {
        mQuery('#' + valueId).chosen('destroy');
    }

    if (filterType == 'bool' || filterType == 'boolean') {
        if (mQuery(valueEl).attr('type') != 'radio') {
            var template = mQuery('#filterValueYesNoTemplate .btn-group').clone(true);
            mQuery(template).find('input[type="radio"]').each(function () {
                mQuery(this).attr('name', valueName);
                var radioVal = mQuery(this).val();
                mQuery(this).attr('id', valueId + '_' + radioVal);
            });
            mQuery(valueEl).replaceWith(template);
        }

        if (setup) {
            mQuery('#' + valueId + '_' + valueVal).click();
        }
    } else if (mQuery(valueEl).attr('type') != 'text') {
        var newValueEl = mQuery('<input type="text" />').attr({
            id: valueId,
            name: valueName,
            'class': "form-control filter-value"
        });

        var replaceMe = (mQuery(valueEl).attr('type') == 'radio') ? mQuery(valueEl).parent().parent() : mQuery(valueEl);
        replaceMe.replaceWith(newValueEl);
    }

    if ((filterType == 'multiselect' || filterType == 'select') && typeof definitions[newValue].list != 'undefined') {
        // Activate a chosen
        var currentValue = mQuery(valueEl).val();

        var attr = {
            id: valueId,
            name: valueName,
            "class": 'form-control filter-value',
        };

        if (filterType == 'multiselect') {
            attr.multiple = true;
        }

        var newSelect = mQuery('<select />', attr);

        mQuery.each(definitions[newValue].list, function (value, label) {
            var newOption = mQuery('<option />')
                .val(value)
                .html(label);

            if (value == currentValue) {
                newOption.prop('selected', true);
            }

            newOption.appendTo(newSelect);
        });
        mQuery(valueEl).replaceWith(newSelect);

        Mautic.activateChosenSelect(newSelect);
    }

    // Activate datetime
    if (filterType == 'datetime' || filterType == 'date' || filterType == 'time') {
        Mautic.activateDateTimeInputs('#' + valueId, filterType);
    } else if (mQuery('#' + valueId).hasClass('calendar-activated')) {
        mQuery('#' + valueId).datetimepicker('destroy');
    }
};

Mautic.removeReportRow = function (container) {
    mQuery("#" + container + " *[data-toggle='tooltip']").tooltip('destroy');
    mQuery('#' + container).remove();
};

Mautic.updateReportSourceData = function (context) {
    Mautic.activateLabelLoadingIndicator('report_source');
    mQuery.ajax({
        url: mauticAjaxUrl,
        type: 'post',
        data: "action=report:getSourceData&context=" + context,
        success: function (response) {
            mQuery('#report_columns').html(response.columns);
            mQuery('#report_columns').multiSelect('refresh');

            // Remove any filters, they're no longer valid with different column lists
            mQuery('#report_filters').find('div').remove().end();

            // Reset index
            mQuery('#report_filters').data('index', 0);

            // Update columns
            Mautic.reportPrototypeColumnOptions = mQuery(response.columns);

            // Remove order
            mQuery('#report_tableOrder').find('div').remove().end();

            // Reset index
            mQuery('#report_tableOrder').data('index', 0);

            // Update filter list
            Mautic.reportPrototypeFilterDefinitions = response.filterDefinitions;
            Mautic.reportPrototypeFilterOptions = response.filters;
            Mautic.reportPrototypeFilterOperators = response.filterOperators;

            mQuery('#report_graphs').html(response.graphs);
            mQuery('#report_graphs').multiSelect('refresh');

            if (!response.graphs) {
                mQuery('#graphs-container').addClass('hide');
                mQuery('#graphs-tab').addClass('hide');
            } else {
                mQuery('#graphs-container').removeClass('hide');
                mQuery('#graphs-tab').removeClass('hide');
            }
        },
        error: function (request, textStatus, errorThrown) {
            Mautic.processAjaxError(request, textStatus, errorThrown);
        },
        complete: function () {
            Mautic.removeLabelLoadingIndicator();
        }
    });
};

Mautic.checkReportCondition = function (selector) {
    var option = mQuery('#' + selector + ' option:selected').val();
    var valueInput = selector.replace('condition', 'value');

    // Disable the value input if the condition is empty or notEmpty
    if (option == 'empty' || option == 'notEmpty') {
        mQuery('#' + valueInput).prop('disabled', true);
    } else {
        mQuery('#' + valueInput).prop('disabled', false);
    }
};
