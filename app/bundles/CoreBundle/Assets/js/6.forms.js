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
    Mautic.initializeFormFieldVisibilitySwitcher(formName);

    //prevent enter submitting form and instead jump to next line
    var form = 'form[name="' + formName + '"]';
    mQuery(form + ' input, ' + form + ' select').off('keydown.ajaxform');
    mQuery(form + ' input, ' + form + ' select').on('keydown.ajaxform', function (e) {
        if(e.keyCode == 13 && (e.metaKey || e.ctrlKey)) {
            if (MauticVars.formSubmitInProgress) {
                return false;
            }

            // Find save button first then apply
            var saveButton = mQuery(form).find('button.btn-save');
            var applyButton = mQuery(form).find('button.btn-apply');

            var modalParent = mQuery(form).closest('.modal');
            var inMain      = mQuery(modalParent).length > 0 ? false : true;

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
        } else if (e.keyCode == 13 && mQuery(e.target).is(':input')) {
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
            if (mQuery(this).attr('name') && !mQuery("input[name='" + mQuery(this).attr('name') + "']").length) {
                mQuery('form[name="' + formName + '"]').append(
                    mQuery("<input type='hidden'>").attr({
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
    var form = mQuery(form);

    var modalParent = form.closest('.modal');
    var inMain = mQuery(modalParent).length > 0 ? false : true;

    var action = form.attr('action');

    if (!inMain) {
        var modalTarget = '#' + mQuery(modalParent).attr('id');
        Mautic.startModalLoadingBar(modalTarget);
    }
    var showLoading = (!inMain || form.attr('data-hide-loadingbar')) ? false : true;

    form.ajaxSubmit({
        showLoadingBar: showLoading,
        success: function (data) {
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
 * Initialize form field visibility switcher
 *
 * @param formName
 */
Mautic.initializeFormFieldVisibilitySwitcher = function (formName)
{
    Mautic.switchFormFieldVisibilty(formName);

    mQuery('form[name="'+formName+'"]').change(function() {
        Mautic.switchFormFieldVisibilty(formName);
    });
};

/**
 * Switch form field visibility based on selected values
 */
Mautic.switchFormFieldVisibilty = function (formName) {
    var form   = mQuery('form[name="'+formName+'"]');
    var fields = {};
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
        var visible = true;
        if (typeof condition == 'object') {
            visible = mQuery.inArray(sourceFieldVal, condition) !== -1;
        } else if (condition == 'empty' || (condition == 'notEmpty')) {
            var isEmpty = (sourceFieldVal == '' || sourceFieldVal == null || sourceFieldVal == 'undefined');
            visible = (condition == 'empty') ? isEmpty : !isEmpty;
        } else if (condition !== sourceFieldVal) {
            visible = false;
        }

        return visible;
    };

    var checkFieldCondition = function (fieldId, attribute, condition) {
        var visible = true;

        if (attribute) {
            // Compare the attribute value
            if (typeof mQuery('#' + fieldId).attr(attribute) !== 'undefined') {
                var field = '#' + fieldId;
            } else if (mQuery('#' + fieldId).is('select')) {
                // Check the value option
                var field = mQuery('#' + fieldId +' option[value="' + mQuery('#' + fieldId).val() + '"]');
            } else {
                return visible;
            }

            var attributeValue = (typeof mQuery(field).attr(attribute) !== 'undefined') ? mQuery(field).attr(attribute) : null;

            return checkValueCondition(attributeValue, condition);
        } else if (mQuery('#' + fieldId).is(':checkbox') || mQuery('#' + fieldId).is(':radio')) {
            return (condition == 'checked' && mQuery('#' + fieldId).is(':checked')) || (condition == '' && !mQuery('#' + fieldId).is(':checked'));
        }

        return checkValueCondition(mQuery('#' + fieldId).val(), condition);
    }

    // find all fields to show
    form.find('[data-show-on]').each(function(index, el) {
        var field = mQuery(el);
        var showOn = jQuery.parseJSON(field.attr('data-show-on'));

        mQuery.each(showOn, function(fieldId, condition) {
            var fieldParts = getFieldParts(fieldId);

            // Treat multiple fields as OR statements
            if (typeof fields[field.attr('id')] === 'undefined' || !fields[field.attr('id')]) {
                fields[field.attr('id')] = checkFieldCondition(fieldParts.name, fieldParts.attribute, condition);
            }
        });
    });

    // find all fields to hide
    form.find('[data-hide-on]').each(function(index, el) {
        var field  = mQuery(el);
        var hideOn = jQuery.parseJSON(field.attr('data-hide-on'));

        if (typeof hideOn.display_priority !== 'undefined') {
            fieldsPriority[field.attr('id')] = 'hide';
            delete hideOn.display_priority;
        }

        mQuery.each(hideOn, function(fieldId, condition) {
            var fieldParts = getFieldParts(fieldId);

            // Treat multiple fields as OR statements
            if (typeof fields[field.attr('id')] === 'undefined' || fields[field.attr('id')]) {
                fields[field.attr('id')] = !checkFieldCondition(fieldParts.name, fieldParts.attribute, condition);
            }
        });
    });

    // show/hide according to conditions
    mQuery.each(fields, function(fieldId, show) {
        var fieldContainer = mQuery('#' + fieldId).closest('[class*="col-"]');;
        if (show) {
            fieldContainer.fadeIn();
        } else {
            fieldContainer.fadeOut();
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
                var alc = a.text.toLowerCase(), blc = b.text.toLowerCase();
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
Mautic.toggleYesNoButtonClass = function (changedId) {
    changedId = '#' + changedId;

    var isYesButton   = mQuery(changedId).parent().hasClass('btn-yes');
    var isExtraButton = mQuery(changedId).parent().hasClass('btn-extra');

    if (isExtraButton) {
        mQuery(changedId).parents('.btn-group').find('.btn').removeClass('btn-success btn-danger').addClass('btn-default');
    } else {
        //change the other
        var otherButton = isYesButton ? '.btn-no' : '.btn-yes';
        var otherLabel = mQuery(changedId).parent().parent().find(otherButton);

        if (mQuery(changedId).prop('checked')) {
            var thisRemove = 'btn-default',
                otherAdd = 'btn-default';
            if (isYesButton) {
                var thisAdd = 'btn-success',
                    otherRemove = 'btn-danger';
            } else {
                var thisAdd = 'btn-danger',
                    otherRemove = 'btn-success';
            }
        } else {
            var thisAdd = 'btn-default';
            if (isYesButton) {
                var thisAdd = 'btn-success',
                    otherRemove = 'btn-danger';
            } else {
                var thisAdd = 'btn-danger',
                    otherRemove = 'btn-success';
            }
        }

        mQuery(changedId).parent().removeClass(thisRemove).addClass(thisAdd);
        mQuery(otherLabel).removeClass(otherRemove).addClass(otherAdd);
    }

    return true;
};

/**
 * Removes a list option from a list generated by ListType
 * @param el
 */
Mautic.removeFormListOption = function (el) {
    var sortableDiv = mQuery(el).parents('div.sortable');
    var inputCount = mQuery(sortableDiv).parents('div.form-group').find('input.sortable-itemcount');
    var count = mQuery(inputCount).val();
    count--;
    mQuery(inputCount).val(count);
    mQuery(sortableDiv).remove();
};

/**
 * Updates operator select and value input format based on selected field and operator
 *
 * @param field
 * @param action
 */
Mautic.updateFieldOperatorValue = function(field, action) {
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
                valueField.chosen('destroy');
            }

            if (!mQuery.isEmptyObject(response.options)) {
                var newValueField = mQuery('<select/>')
                    .attr('class', valueFieldAttrs['class'])
                    .attr('id', valueFieldAttrs['id'])
                    .attr('name', valueFieldAttrs['name'])
                    .attr('autocomplete', valueFieldAttrs['autocomplete']);
                mQuery.each(response.options, function(optionKey, optionVal) {
                    var option = mQuery("<option/>")
                        .attr('value', optionKey)
                        .text(optionVal);
                    if (fieldType != 'field' && optionKey == valueFieldAttrs['value']) {
                        option.attr('selected', 'selected');
                    }
                    newValueField.append(option);
                });
                valueField.replaceWith(newValueField);

                Mautic.activateChosenSelect(newValueField);
            } else {
                var newValueField = mQuery('<input/>')
                    .attr('type', 'text')
                    .attr('class', valueFieldAttrs['class'])
                    .attr('id', valueFieldAttrs['id'])
                    .attr('name', valueFieldAttrs['name'])
                    .attr('autocomplete', valueFieldAttrs['autocomplete']);

                if (response.disabled) {
                    valueFieldAttrs['value'] = ''
                    newValueField.prop('disabled', true);
                }
                newValueField.attr('value', valueFieldAttrs['value']);
                valueField.replaceWith(newValueField);

                if (response.fieldType == 'date' || response.fieldType == 'datetime') {
                    Mautic.activateDateTimeInputs(newValueField, response.fieldType);
                }
            }

            if (!mQuery.isEmptyObject(response.operators)) {
                if (mQuery('#'+fieldPrefix+'operator_chosen').length) {
                    mQuery('#'+fieldPrefix+'operator').chosen('destroy');
                }

                var operatorField = mQuery('#'+fieldPrefix+'operator');
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
                mQuery.each(response.operators, function(optionKey, optionVal) {
                    var option = mQuery("<option/>")
                        .attr('value', optionKey)
                        .text(optionVal);
                    if (optionKey == operatorFieldAttrs['value']) {
                        option.attr('selected', 'selected');
                    }
                    newOperatorField.append(option);
                });
                operatorField.replaceWith(newOperatorField);
                Mautic.activateChosenSelect(newOperatorField);
            }
        }
        Mautic.removeLabelLoadingIndicator();
    });
};