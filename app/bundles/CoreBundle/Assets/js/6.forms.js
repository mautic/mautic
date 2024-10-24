// Show overflow in the App Wrapper when a Chosen dropdown is shown
mQuery(document).on({
    // The order in which the handlers are registered matters
    "chosen:hiding_dropdown": function() {
        mQuery('#app-wrapper').css('overflow', 'hidden');
    },
    "chosen:showing_dropdown": function() {
        mQuery('#app-wrapper').css('overflow', 'visible');
    }
});

/**
 * Replace id and name of form elements within given container
 *
 * @param container
 * @param oldIdPrefix
 * @param oldNamePrefix
 * @param newIdPrefix
 * @param newNamePrefix
 */
Mautic.renameFormElements = function(container, oldIdPrefix, oldNamePrefix, newIdPrefix, newNamePrefix) {
    mQuery('*[id^="'+oldIdPrefix+'"]', container).each( function() {
        var id = mQuery(this).attr('id');
        id = id.replace(oldIdPrefix, newIdPrefix);
        mQuery(this).attr('id', id);

        var name = mQuery(this).attr('name');
        if (name) {
            name = name.replace(oldNamePrefix, newNamePrefix);
            mQuery(this).attr('name', name);
        }
    });

    mQuery('label[for^="'+oldIdPrefix+'"]', container).each( function() {
        var id = mQuery(this).attr('for');
        id = id.replace(oldIdPrefix, newIdPrefix);
        mQuery(this).attr('for', id);
    });
};

/**
 * Prepares form for ajax submission
 *
 * @param form
 */
Mautic.ajaxifyForm = function (formName) {
    Mautic.initializeFormFieldStateSwitcher(formName);

    // Prevent enter from submitting form and instead jump to next line
    var form = 'form[name="' + formName + '"]';

    // Handle Command+Enter (Mac) or Control+Enter (Windows/Linux) for form submission
    Mautic.addKeyboardShortcut(['meta+enter', 'ctrl+enter'], 'Submit form', function(e) {
        if (MauticVars.formSubmitInProgress) {
            return false;
        }

        // Find save button first then apply
        var saveButton = mQuery(form).find('button.btn-save');
        var applyButton = mQuery(form).find('button.btn-apply');

        var modalParent = mQuery(form).closest('.modal');
        var inMain = mQuery(modalParent).length > 0 ? false : true;

        if (mQuery(saveButton).length) {
            if (inMain) {
                if (mQuery(form).find('button.btn-save.btn-copy').length) {
                    mQuery(mQuery(form).find('button.btn-save.btn-copy')).trigger('click');
                    return;
                }
            } else {
                if (mQuery(modalParent).find('button.btn-save.btn-copy').length) {
                    mQuery(mQuery(modalParent).find('button.btn-save.btn-copy')).trigger('click');
                    return;
                }
            }
            mQuery(saveButton).trigger('click');
        } else if (mQuery(applyButton).length) {
            if (inMain) {
                if (mQuery(form).find('button.btn-apply.btn-copy').length) {
                    mQuery(mQuery(form).find('button.btn-apply.btn-copy')).trigger('click');
                    return;
                }
            } else {
                if (mQuery(modalParent).find('button.btn-apply.btn-copy').length) {
                    mQuery(mQuery(modalParent).find('button.btn-apply.btn-copy')).trigger('click');
                    return;
                }
            }
            mQuery(applyButton).trigger('click');
        }
    });

    // Handle Enter key for jumping to the next input
    mQuery(form + ' input, ' + form + ' select').off('keydown.ajaxform');
    mQuery(form + ' input, ' + form + ' select').on('keydown.ajaxform', function (e) {
        if (e.keyCode == 13 && mQuery(e.target).is(':input')) {
            var inputs = mQuery(this).parents('form').eq(0).find(':input');
            if (inputs[inputs.index(this) + 1] != null) {
                inputs[inputs.index(this) + 1].focus();
            }
            e.preventDefault();
            return false;
        }
    });

    //activate the submit buttons so symfony knows which were clicked
    mQuery(form + ' :submit').each(function () {
        mQuery(this).off('click.ajaxform');
        mQuery(this).on('click.ajaxform', function () {
            if (mQuery(this).attr('name') && !mQuery('input[name="' + mQuery(this).attr('name') + '"]').length) {
                mQuery('input.button-clicked').remove(); // ensure the previously clicked buttons are gone
                mQuery('form[name="' + formName + '"]').append(
                    mQuery('<input type="hidden" class="button-clicked">').attr({
                        name: mQuery(this).attr('name'),
                        value: mQuery(this).attr('value')
                    })
                );
            }
        });
    });

    //activate the forms
    mQuery(form).off('submit.ajaxform');
    mQuery(form).on('submit.ajaxform', (function (e) {
        e.preventDefault();
        var form = mQuery(this);

        if (MauticVars.formSubmitInProgress) {
            return false;
        } else {
            var callbackAsync = form.data('submit-callback-async');
            if (callbackAsync && typeof Mautic[callbackAsync] == 'function') {
                Mautic[callbackAsync].apply(this, [form, function() {
                    Mautic.postMauticForm(form);
                }]);
            } else {
                var callback = form.data('submit-callback');

                // Allow a callback to do stuff before submit and abort if needed
                if (callback && typeof Mautic[callback] == 'function') {
                    if (!Mautic[callback]()) {
                        return false;
                    }
                }

                Mautic.postMauticForm(form);
            }
        }

        return false;
    }));
};

/**
 * Post a form
 *
 * @param form
 */
Mautic.postMauticForm = function(form) {
    MauticVars.formSubmitInProgress = true;
    Mautic.postForm(form, function (response) {
        if (response.inMain) {
            Mautic.processPageContent(response);
        } else {
            Mautic.processModalContent(response, '#' + response.modalId);
        }
    });
};

/**
 * Reset form fields
 *
 * @param form
 */
Mautic.resetForm = function(form) {
    mQuery(':input', form)
        .not(':button, :submit, :reset, :hidden')
        .val('')
        .removeAttr('checked')
        .prop('checked', false)
        .removeAttr('selected')
        .prop('selected', false);

    mQuery(form).find('select:not(.not-chosen):not(.multiselect)').each(function() {
        mQuery(this).find('option:selected').prop('selected', false)
        mQuery(this).trigger('chosen:updated');
    });
};


/**
 * Posts a form and returns the output.
 * Uses jQuery form plugin so it handles files as well.
 *
 * @param form
 * @param callback
 */
Mautic.postForm = function (form, callback) {
    form = mQuery(form);

    var modalParent = form.closest('.modal');
    var inMain = mQuery(modalParent).length === 0;

    var action = form.attr('action');

    if (!inMain) {
        var modalTarget = '#' + mQuery(modalParent).attr('id');
        Mautic.startModalLoadingBar(modalTarget);
    }
    var showLoading = (!inMain || form.attr('data-hide-loadingbar')) ? false : true;

    form.ajaxSubmit({
        showLoadingBar: showLoading,
        success: function (data) {
            form.trigger('submit:success', [action, data, inMain]);
            if (!inMain) {
                Mautic.stopModalLoadingBar(modalTarget);
            }

            if (data.redirect) {
                Mautic.redirectWithBackdrop(data.redirect);
            } else {
                MauticVars.formSubmitInProgress = false;
                if (!inMain) {
                    var modalId = mQuery(modalParent).attr('id');
                }

                if (data.sessionExpired) {
                    if (!inMain) {
                        mQuery('#' + modalId).modal('hide');
                        mQuery('.modal-backdrop').remove();
                    }
                    Mautic.processPageContent(data);
                } else if (callback) {
                    data.inMain = inMain;

                    if (!inMain) {
                        data.modalId = modalId;
                    }

                    if (typeof callback == 'function') {
                        callback(data);
                    } else if (typeof Mautic[callback] == 'function') {
                        Mautic[callback](data);
                    }
                }
            }
        },
        error: function (request, textStatus, errorThrown) {
            MauticVars.formSubmitInProgress = false;

            Mautic.processAjaxError(request, textStatus, errorThrown, inMain);
        }
    });
};


/**
 * Initialize form field state switcher
 *
 * @param formName
 */
Mautic.initializeFormFieldStateSwitcher = function (formName)
{
    Mautic.switchFormFieldState(formName);

    mQuery('form[name="'+formName+'"]').on('change', function() {
        Mautic.switchFormFieldState(formName);
    });
};

/**
 * Switch form field state based on selected values
 *
 * Possible state: visible, disabled
 */
Mautic.switchFormFieldState = function (formName) {
    var form   = mQuery('form[name="'+formName+'"]');
    var visibleFields = {};
    var disabledFields = {};
    var fieldsPriority = {};

    var getFieldParts = function(fieldName) {
        var returnObject = {"name": fieldName, "attribute": ''};
        if (fieldName.search(':') !== -1) {
            var returnArray = fieldName.split(':');
            returnObject.name = returnArray[0];
            returnObject.attribute = returnArray[1];
        }

        return returnObject;
    };

    var checkValueCondition = function (sourceFieldVal, condition) {
        var state = true;
        if (typeof condition == 'object') {
            state = mQuery.inArray(sourceFieldVal, condition) !== -1;
        } else if (condition == 'empty' || (condition == 'notEmpty')) {
            var isEmpty = (sourceFieldVal == '' || sourceFieldVal == null || sourceFieldVal == 'undefined');
            state = (condition == 'empty') ? isEmpty : !isEmpty;
        } else if (condition !== sourceFieldVal) {
            state = false;
        }

        return state;
    };

    var checkFieldCondition = function (fieldId, attribute, condition) {
        var state = true;

        if (attribute) {
            // Compare the attribute value
            if (typeof mQuery('#' + fieldId).attr(attribute) !== 'undefined') {
                var field = '#' + fieldId;
            } else if (mQuery('#' + fieldId).is('select')) {
                // Check the value option
                var field = mQuery('#' + fieldId +' option[value="' + mQuery('#' + fieldId).val() + '"]');
            } else {
                return state;
            }

            var attributeValue = (typeof mQuery(field).attr(attribute) !== 'undefined') ? mQuery(field).attr(attribute) : null;

            return checkValueCondition(attributeValue, condition);
        } else if (mQuery('#' + fieldId).is(':checkbox') || mQuery('#' + fieldId).is(':radio')) {
            return (condition == 'checked' && mQuery('#' + fieldId).is(':checked')) || (condition == '' && !mQuery('#' + fieldId).is(':checked'));
        }

        return checkValueCondition(mQuery('#' + fieldId).val(), condition);
    }

    var processConditions = function (attribute, checkState, shouldInvert) {
        form.find('[' + attribute + ']').each(function(_, el) {
            var field = mQuery(el);
            var conditions = JSON.parse(field.attr(attribute));

            mQuery.each(conditions, function(fieldId, condition) {
                var fieldParts = getFieldParts(fieldId);
                var stateId = field.attr('id');

                // Evaluate the condition and apply inversion if needed
                var conditionResult = checkFieldCondition(fieldParts.name, fieldParts.attribute, condition);
                if (shouldInvert) {
                    conditionResult = !conditionResult;
                }

                // Set the state only if it's undefined or follows OR logic
                if (typeof checkState[stateId] === 'undefined' || !checkState[stateId]) {
                    checkState[stateId] = conditionResult;
                }
            });
        });
    };

    var resetField = function (field) {
        // Set Yes/No toggle to No.
        if ('Mautic.toggleYesNo(this);' === field.attr('onchange')
            && field.val() === "1"
            && field.is(':checked')
        ) {
            label = field.siblings('.toggle__label')
            if (label.attr('aria-checked') === "true") {
                label.trigger('click');
            }
        }
    }

    // find all fields to show
    processConditions('data-show-on', visibleFields, false);

    // find all fields to hide
    form.find('[data-hide-on]').each(function(index, el) {
        var field  = mQuery(el);
        var hideOn = JSON.parse(field.attr('data-hide-on'));

        if (typeof hideOn.display_priority !== 'undefined') {
            fieldsPriority[field.attr('id')] = 'hide';
            delete hideOn.display_priority;
        }

        mQuery.each(hideOn, function(fieldId, condition) {
            var fieldParts = getFieldParts(fieldId);

            // Treat multiple fields as OR statements
            if (typeof visibleFields[field.attr('id')] === 'undefined' || visibleFields[field.attr('id')]) {
                visibleFields[field.attr('id')] = !checkFieldCondition(fieldParts.name, fieldParts.attribute, condition);
            }
        });
    });

    // show/hide according to conditions
    mQuery.each(visibleFields, function(fieldId, show) {
        var field = mQuery('#' + fieldId);
        var fieldContainer = field.closest('[class*="col-"]');
        if (show) {
            fieldContainer.fadeIn();
        } else {
            resetField(field);
            fieldContainer.fadeOut();
        }
    });

    // find all fields to enable
    processConditions('data-enable-on', disabledFields, true);
    // find all fields to disable
    processConditions('data-disable-on', disabledFields, false);

    // disable according to conditions
    mQuery.each(disabledFields, function(fieldId, disable) {
        var field = mQuery('#' + fieldId)
        if (disable) {
            resetField(field);
            field.addClass('disabled', disable);
        } else {
            field.removeClass('disabled', disable);
        }

    });
};

/**
 * Inserts a new row into a chosen select box
 *
 * @param response
 */
Mautic.updateEntitySelect = function (response) {
    var mQueryParent = (window.opener) ? window.opener.mQuery : mQuery;

    if (response.id) {
        // New entity added through a popup so update the chosen
        var newOption = mQuery('<option />').val(response.id);
        newOption.html(response.name);
        var el = '#' + response.updateSelect;

        var sortOptions = function (options) {
            return options.sort(function (a, b) {
                var alc = a.text ? a.text.toLowerCase() : mQuery(a).attr("label").toLowerCase();
                var blc = b.text ? b.text.toLowerCase() : mQuery(b).attr("label").toLowerCase();
                return alc > blc ? 1 : alc < blc ? -1 : 0;
            });
        }

        var emptyOption = false,
            createNewOption = false;

        if (mQueryParent(el).prop('disabled')) {
            mQueryParent(el).prop('disabled', false);
            var emptyOption = mQuery('<option value="">' + mauticLang.chosenChooseOne + '</option>');
        } else {
            if (mQueryParent(el + ' option[value=""]').length) {
                emptyOption = mQueryParent(el + ' option[value=""]').clone();
                // Remove the empty option and add it back after sorting
                mQueryParent(el + ' option[value=""]').remove();
            }

            if (mQueryParent(el + ' option[value="new"]').length) {
                createNewOption = mQueryParent(el + ' option[value="new"]').clone();
                // Remove the new option and add it back after sorting
                mQueryParent(el + ' option[value="new"]').remove();
            }
        }

        if (response.group) {
            var optgroup = el + ' optgroup[label="'+response.group+'"]';
            if (mQueryParent(optgroup).length) {
                // update option when new option equal with option item in group.
                var firstOptionGroups = mQueryParent(optgroup);
                var isUpdateOption = false;
                firstOptionGroups.each(function () {
                    var firstOptions = mQuery(this).children();
                    for (var i = 0; i < firstOptions.length; i++) {
                        if (firstOptions[i].value === response.id.toString()) {
                            firstOptions[i].text = response.name;
                            isUpdateOption = true;
                            break;
                        }
                    }
                });

                if (!isUpdateOption) {
                    //the optgroup exist so append to it
                    mQueryParent(optgroup).append(newOption);
                }
            } else {
                //create the optgroup
                var newOptgroup = mQuery('<optgroup label= />');
                newOption.appendTo(newOptgroup);
                mQueryParent(newOptgroup).appendTo(mQueryParent(el));
            }

            var optionGroups = sortOptions(mQueryParent(el + ' optgroup'));

            optionGroups.each(function () {
                var options = sortOptions(mQuery(this).children());
                mQuery(this).html(options);
            });

            var appendOptions = optionGroups;
        } else {
            newOption.appendTo(mQueryParent(el));

            var appendOptions = sortOptions(mQueryParent(el).children());
        }

        mQueryParent(el).html(appendOptions);

        if (createNewOption) {
            mQueryParent(el).prepend(createNewOption);
        }

        if (emptyOption) {
            mQueryParent(el).prepend(emptyOption);
        }

        newOption.prop('selected', true);
        mQueryParent(el).trigger("chosen:updated");
    }

    if (window.opener) {
        window.close();
    } else {
        mQueryParent('#MauticSharedModal').modal('hide');
    }
};

/**
 * Toggles the class for yes/no button groups
 * @param changedId
 */
Mautic.toggleYesNo = function(element) {
    var $label = mQuery(element);
    var $toggle = $label.closest('.toggle');
    var yesId = $label.data('yes-id');
    var noId = $label.data('no-id');
    var $yesInput = mQuery('#' + yesId);
    var $noInput = mQuery('#' + noId);
    var $switchEl = $toggle.find('.toggle__switch');
    var $textEl = $toggle.find('.toggle__text');

    var yesText = $toggle.data('yes');
    var noText = $toggle.data('no');

    if ($yesInput.is(':checked')) {
        // Switch to 'No'
        $noInput.prop('checked', true).trigger('change');
        $yesInput.prop('checked', false);
        $switchEl.removeClass('toggle__switch--checked');
        $textEl.text(noText);
        $label.attr('aria-checked', 'false');
    } else {
        // Switch to 'Yes'
        $yesInput.prop('checked', true).trigger('change');
        $noInput.prop('checked', false);
        $switchEl.addClass('toggle__switch--checked');
        $textEl.text(yesText);
        $label.attr('aria-checked', 'true');
    }
};

Mautic.handleKeyDown = function(event, element) {
    if (event.key === ' ' || event.key === 'Enter') {
        event.preventDefault();
        Mautic.toggleYesNo(element);
    }
};

/**
 * Removes a list option from a list generated by ListType
 * @param el
 */
Mautic.removeFormListOption = function (el) {
    var sortableDiv = mQuery(el).parents('div.sortable');
    mQuery(sortableDiv).remove();
};

/**
 * Creates a select option element with a name and label
 * @param value
 * @param label
 */
Mautic.createOption = function (value, label) {
    return mQuery('<option/>')
        .attr('value', value)
        .text(label);
}

/**
 * Updates operator select and value input format based on selected field and operator
 *
 * @param field
 * @param action
 * @param valueOnChange
 */
Mautic.updateFieldOperatorValue = function(field, action, valueOnChange, valueOnChangeArguments) {
    var fieldId = mQuery(field).attr('id');
    Mautic.activateLabelLoadingIndicator(fieldId);

    if (fieldId.indexOf('_operator') !== -1) {
        var fieldType = 'operator';
    } else if (fieldId.indexOf('_field') !== -1) {
        var fieldType = 'field';
    } else {
        return;
    }

    var fieldPrefix = fieldId.slice(0,-1 * fieldType.length);
    var fieldAlias = mQuery('#'+fieldPrefix+'field').val();
    var fieldOperator = mQuery('#'+fieldPrefix+'operator').val();

    Mautic.ajaxActionRequest(action, {'alias': fieldAlias, 'operator': fieldOperator, 'changed': fieldType}, function(response) {
        if (typeof response.options != 'undefined') {
            var valueField = mQuery('#'+fieldPrefix+'value');
            var valueFieldAttrs = {
                'class': valueField.attr('class'),
                'id': valueField.attr('id'),
                'name': valueField.attr('name'),
                'autocomplete': valueField.attr('autocomplete'),
                'value': valueField.val()
            };

            if (mQuery('#'+fieldPrefix+'value_chosen').length) {
                valueFieldAttrs['value'] = '';
                Mautic.destroyChosen(valueField);
            }

            if (!mQuery.isEmptyObject(response.options) && response.fieldType !== 'number') {
                var newValueField = mQuery('<select/>')
                    .attr('class', valueFieldAttrs['class'])
                    .attr('id', valueFieldAttrs['id'])
                    .attr('name', valueFieldAttrs['name'])
                    .attr('autocomplete', valueFieldAttrs['autocomplete'])
                    .attr('value', valueFieldAttrs['value']);

                var multiple = (fieldOperator === 'in' || fieldOperator === '!in');
                if (multiple) {
                    newValueField.attr('multiple', 'multiple');

                    // Update the name
                    var newName =  newValueField.attr('name') + '[]';
                    newValueField.attr('name', newName);
                    newValueField.attr('data-placeholder', mauticLang['chosenChooseMore']);
                }

                mQuery.each(response.options, function(value, optgroup) {
                    if (typeof optgroup === 'object') {
                        var optgroupEl = mQuery('<optgroup/>').attr('label', value);
                        mQuery.each(optgroup, function(optVal, label) {
                            var option = Mautic.createOption(optVal, label);

                            if (response.optionsAttr && response.optionsAttr[optVal]) {
                                mQuery.each(response.optionsAttr[optVal], function(optAttr, optVal) {
                                    option.attr(optAttr, optVal);
                                });
                            }

                            optgroupEl.append(option)
                        });
                        newValueField.append(optgroupEl);
                    } else {
                        var option = Mautic.createOption(value, optgroup);

                        if (response.optionsAttr && response.optionsAttr[value]) {
                            mQuery.each(response.optionsAttr[value], function(optAttr, optVal) {
                                option.attr(optAttr, optVal);
                            });
                        }

                        newValueField.append(option);
                    }
                });
                newValueField.val(valueFieldAttrs['value']);
                valueField.replaceWith(newValueField);

                Mautic.activateChosenSelect(newValueField);
            } else {
                var newValueField = mQuery('<input/>')
                    .attr('type', 'text')
                    .attr('class', valueFieldAttrs['class'])
                    .attr('id', valueFieldAttrs['id'])
                    .attr('name', valueFieldAttrs['name'])
                    .attr('autocomplete', valueFieldAttrs['autocomplete'])
                    .attr('value', valueFieldAttrs['value']);

                if (response.disabled) {
                    newValueField.attr('value', '');
                    newValueField.prop('disabled', true);
                }

                valueField.replaceWith(newValueField);

                if (response.fieldType == 'date' || response.fieldType == 'datetime') {
                    Mautic.activateDateTimeInputs(newValueField, response.fieldType);
                }
            }

            if (valueOnChange && typeof valueOnChange == 'function') {
                mQuery('#'+fieldPrefix+'value').on('change', function () {
                    if (typeof valueOnChangeArguments != 'object') {
                        valueOnChangeArguments = [];
                    }
                    valueOnChangeArguments.unshift(mQuery('#'+fieldPrefix+'value'));

                    valueOnChange.apply(null, valueOnChangeArguments);
                });
            }

            if (!mQuery.isEmptyObject(response.operators)) {
                var operatorField = mQuery('#'+fieldPrefix+'operator');

                Mautic.destroyChosen(operatorField);

                var operatorFieldAttrs = {
                    'class': operatorField.attr('class'),
                    'id': operatorField.attr('id'),
                    'name': operatorField.attr('name'),
                    'autocomplete': operatorField.attr('autocomplete'),
                    'value': operatorField.val()
                };

                var newOperatorField = mQuery('<select/>')
                    .attr('class', operatorFieldAttrs['class'])
                    .attr('id', operatorFieldAttrs['id'])
                    .attr('name', operatorFieldAttrs['name'])
                    .attr('autocomplete', operatorFieldAttrs['autocomplete'])
                    .attr('value', operatorFieldAttrs['value'])
                    .attr('onchange', 'Mautic.updateLeadFieldValues(this)');
                mQuery.each(response.operators, function(optionVal, optionKey) {
                    newOperatorField.append(Mautic.createOption(optionKey, optionVal));
                });
                newOperatorField.val(operatorField.val());
                operatorField.replaceWith(newOperatorField);
                Mautic.activateChosenSelect(newOperatorField);
            }
        }
        Mautic.removeLabelLoadingIndicator();
    }, false, false, "POST");
};
