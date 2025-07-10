//ReportBundle
Mautic.reportOnLoad = function (container) {
    // Activate search if the container exists
    if (mQuery(container + ' #list-search').length) {
        Mautic.activateSearchAutocomplete('list-search', 'report');
    }

    // Append an index of the number of filters on the edit form
    if (mQuery('div[id=report_filters]').length) {
        mQuery('div[id=report_filters]').attr('data-index', Mautic.getHighestIndex('report_filters'));
        mQuery('div[id=report_tableOrder]').attr('data-index', Mautic.getHighestIndex('report_tableOrder'));
        mQuery('div[id=report_aggregators]').attr('data-index', Mautic.getHighestIndex('report_aggregators'));

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
    Mautic.updateReportGlueTriggers();
    Mautic.checkSelectedGroupBy();
    Mautic.initDateRangePicker();

    var $isScheduled = mQuery('[data-report-schedule="isScheduled"]');
    var $unitTypeId = mQuery('[data-report-schedule="scheduleUnit"]');
    var $scheduleDay = mQuery('[data-report-schedule="scheduleDay"]');
    var $scheduleMonthFrequency = mQuery('[data-report-schedule="scheduleMonthFrequency"]');

    mQuery($isScheduled).change(function () {
        Mautic.scheduleDisplay($isScheduled, $unitTypeId, $scheduleDay, $scheduleMonthFrequency);
    });
    mQuery($unitTypeId).change(function () {
        Mautic.scheduleDisplay($isScheduled, $unitTypeId, $scheduleDay, $scheduleMonthFrequency);
    });
    mQuery($scheduleDay).change(function () {
        Mautic.schedulePreview($isScheduled, $unitTypeId, $scheduleDay, $scheduleMonthFrequency);
    });
    mQuery($scheduleMonthFrequency).change(function () {
        Mautic.schedulePreview($isScheduled, $unitTypeId, $scheduleDay, $scheduleMonthFrequency);
    });
    Mautic.scheduleDisplay($isScheduled, $unitTypeId, $scheduleDay, $scheduleMonthFrequency);

    jQuery(document).ajaxComplete(function(){
        Mautic.ajaxifyForm('daterange');
    });
};

Mautic.scheduleDisplay = function ($isScheduled, $unitTypeId, $scheduleDay, $scheduleMonthFrequency) {
    Mautic.checkIsScheduled($isScheduled);

    var unitVal = mQuery($unitTypeId).val();
    mQuery('#scheduleDay, #scheduleDay label, #scheduleMonthFrequency').hide();
    if (unitVal === 'WEEKLY' || unitVal === 'MONTHLY') {
        mQuery('#scheduleDay').show();
    }
    if (unitVal === 'MONTHLY') {
        mQuery('#scheduleMonthFrequency').show();
        mQuery('#scheduleDay label').hide();
    } else {
        mQuery('#scheduleDay label').show();
    }
    if($isScheduled.length) {
        Mautic.schedulePreview($isScheduled, $unitTypeId, $scheduleDay, $scheduleMonthFrequency);
    }
};

Mautic.schedulePreview = function ($isScheduled, $unitTypeId, $scheduleDay, $scheduleMonthFrequency) {
    var previewUrl = mQuery('#schedule_preview_url').data('url');
    var $schedulePreviewData = mQuery('#schedule_preview_data');

    var isScheduledVal = 0;
    if (mQuery($isScheduled).prop("checked")) { //$isScheduled.val() does not work
        isScheduledVal = 1;
    }

    if (!isScheduledVal) {
        $schedulePreviewData.hide();

        return;
    }
    var unitVal = mQuery($unitTypeId).val();
    var scheduleDayVal = mQuery($scheduleDay).val();
    var scheduleMonthFrequencyVal = mQuery($scheduleMonthFrequency).val();

    mQuery.get(
        previewUrl + '/' + isScheduledVal + '/' + unitVal + '/' + scheduleDayVal + '/' + scheduleMonthFrequencyVal,
        function( data ) {
            if (!data.html) {
                return;
            }

            mQuery("#schedule_preview_data_content").html(data.html);
            $schedulePreviewData.show();
        }
    );
};

Mautic.checkIsScheduled = function ($isScheduled) {
    var $scheduleForm = mQuery('#schedule-container .schedule_form');
    if (mQuery($isScheduled).prop("checked")) {
        $scheduleForm.show();
        return;
    }
    $scheduleForm.hide();
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
        index = 0;
    }

    index++;

    // Fetch the prototype markup
    var prototype = prototypeHolder.data('prototype');

    // Replace the placeholder with our index
    var output = prototype.replace(/__name__/g, index);

    // Increase the index for the next row
    prototypeHolder.attr('data-index', index);

    // Render the new row
    prototypeHolder.append(output);

    var newColumnId = '#' + elId + '_' + index + '_column';
    if (elId == 'report_filters') {
        if (typeof Mautic.reportPrototypeFilterOptions != 'undefined') {
            // Update the column options if applicable
            mQuery(newColumnId).html(Mautic.reportPrototypeFilterOptions);
        }

        // Add `in-group` class by default
        mQuery('#report_filters_' + index + '_container').addClass('in-group');

        mQuery(newColumnId).on('change', function () {
            Mautic.updateReportFilterValueInput(this);
        });
        Mautic.updateReportFilterValueInput(newColumnId);
        Mautic.updateReportGlueTriggers();
    } else if (typeof Mautic.reportPrototypeColumnOptions != 'undefined') {
        // Update the column options if applicable
        mQuery(newColumnId).html(Mautic.reportPrototypeColumnOptions.clone());
    }

    Mautic.activateChosenSelect(mQuery('#' + elId + '_' + index + '_column'));
    mQuery("#" + elId + " *[data-toggle='tooltip']").tooltip({html: true, container: 'body'});

};

Mautic.updateReportGlueTriggers = function () {
    var filterContainer = mQuery('#report_filters');
    var glueEl = filterContainer.find('.filter-glue');

    glueEl.off('change');
    glueEl.on('change', function () {
        var $this = mQuery(this);

        if ($this.val() === 'and') {
            $this.parents('.panel').addClass('in-group');
        } else {
            $this.parents('.panel').removeClass('in-group');
        }
    });
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
    Mautic.destroyChosen(mQuery('#' + valueId));

    if (filterType == 'bool' || filterType == 'boolean') {
        const yesId = valueId + '_1';
        const noId = valueId + '_0';
        const isYes = valueVal == '1';
        const $template = mQuery('#filterValueYesNoTemplate').clone();
        const $label = $template.find('#report_value_template_yesno_label');
        const $yesOption = $template.find('#report_value_template_yesno_1');
        const $noOption = $template.find('#report_value_template_yesno_0');

        $template.removeAttr('id').addClass('toggle-container');
        $yesOption.attr('name', valueName)
            .attr('id', yesId);
        $noOption.attr('name', valueName)
            .attr('id', noId);
        $label.attr('id', valueId + '_bool-label')
            .attr('data-yes-id', yesId)
            .attr('data-no-id', noId);

        if (mQuery(valueEl).is(':radio')) {
            mQuery(valueEl).closest('.toggle-container').replaceWith($template);
        } else {
            mQuery(valueEl).replaceWith($template);
        }

        if (!isYes) {
            Mautic.toggleYesNo($label);
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
            attr.name += '[]';
            attr.multiple = true;
            currentValue = (typeof currentValue !== 'undefined') ? currentValue.split(",") : null;
        }

        var newSelect = mQuery('<select />', attr);

        mQuery.each(definitions[newValue].list, function (value, label) {
            var newOption = mQuery('<option />')
                .val(value)
                .html(label);

            if (value == currentValue && filterType != 'multiselect') {
                newOption.prop('selected', true);
            }

            newOption.appendTo(newSelect);
        });
        if (filterType == 'multiselect') {
            newSelect.val(currentValue);
        }
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

            mQuery('#report_groupBy').html(response.columns);
            mQuery('#report_groupBy').multiSelect('refresh');

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
            mQuery('#report_aggregators').find('div').remove().end();
            // Reset index
            mQuery('#report_aggregators').data('index', 0);

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
        mQuery('#' + valueInput).prop('disabled', true).trigger('chosen:updated');
    } else {
        mQuery('#' + valueInput).prop('disabled', false).trigger('chosen:updated');
    }
};

Mautic.checkSelectedGroupBy = function () {
    var selectedOption = mQuery("select[name='report[groupBy][]'] option:selected").length;
    var existingAggregators = mQuery("select[name*='report[aggregators]']");
    if (selectedOption > 0) {
        mQuery('#aggregators-button').prop('disabled', false);
    } else {
        existingAggregators.each(function() {
            var containerId = mQuery(this).attr('id').replace('_column', '');
            Mautic.removeReportRow(containerId + '_container');
        });
        mQuery('#aggregators-button').prop('disabled', true);
    }
};

Mautic.getHighestIndex = function (selector) {
    var highestIndex = 1;
    var selectorChildren = mQuery('#' + selector + ' > div');

    selectorChildren.each(function() {
        var index = parseInt(mQuery(this).attr('id').split('_')[2]);
        highestIndex = (index > highestIndex) ? index : highestIndex;
    });

    return parseInt(highestIndex);
};

Mautic.cloneReportRow = function (containerId) {
    // Get the existing filter container
    const container = mQuery(`#${containerId}`);

    // Extract values from the existing filter
    const glue = container.find('.filter-glue').val();
    const column = container.find('.filter-columns').val();
    const value = container.find('.filter-value:checked, .filter-value').val();
    const dynamic = container.find('[name*="[dynamic]"]:checked').val();

    // Add a new filter row using the existing add function
    Mautic.addReportRow('report_filters');

    // Get the new container by finding the last filter container
    const newContainer = mQuery('#report_filters').find('> .panel.in-group').last();

    // Set the 'glue' and 'column' values
    newContainer.find('.filter-glue').val(glue);
    const columnSelect = newContainer.find('.filter-columns').val(column).trigger('change');

    // Initialize Chosen when the select field is ready
    const initializeChosenWhenReady = (selectElement) => {
        if (selectElement.find('option').length > 0) {
            Mautic.destroyChosen(selectElement);
            Mautic.activateChosenSelect(selectElement);
        } else {
            setTimeout(() => initializeChosenWhenReady(selectElement), 200);
        }
    };
    initializeChosenWhenReady(columnSelect);

    // Set the 'value' field
    const newValueInput = newContainer.find('.filter-value');
    if (newValueInput.is('select')) {
        newValueInput.val(value);
        initializeChosenWhenReady(newValueInput);
    } else {
        newValueInput.val(value).prop('checked', true).parent().addClass('active');
    }

    // Set the dynamic option correctly using the toggle element
    const dynamicLabel = newContainer.find('.toggle__label');
    if ((dynamic === '1' && dynamicLabel.attr('aria-checked') !== 'true') || (dynamic === '0' && dynamicLabel.attr('aria-checked') !== 'false')) {
        dynamicLabel.trigger('click');
    }

    // Reinitialize tooltips
    newContainer.find("*[data-toggle='tooltip']").tooltip({ html: true, container: 'body' });
};
