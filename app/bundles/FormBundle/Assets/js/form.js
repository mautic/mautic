//FormBundle
Mautic.formOnLoad = function (container) {
    if (mQuery(container + ' #list-search').length) {
        Mautic.activateSearchAutocomplete('list-search', 'form.form');
    }

    if (mQuery('#mauticforms_fields')) {
        //make the fields sortable
        mQuery('#mauticforms_fields').sortable({
            items: '.mauticform-row',
            handle: '.reorder-handle',
            stop: function(i) {
                mQuery.ajax({
                    type: "POST",
                    url: mauticAjaxUrl + "?action=form:reorderFields",
                    data: mQuery('#mauticforms_fields').sortable("serialize") + "&formId=" + mQuery('#mauticform_sessionId').val()
                })
            }
        });

        mQuery('#mauticforms_fields .mauticform-row').on('mouseover.mauticformfields', function() {
           mQuery(this).find('.form-buttons').removeClass('hide');
        }).on('mouseout.mauticformfields', function() {
            mQuery(this).find('.form-buttons').addClass('hide');
        }).on('dblclick.mauticformfields', function(event) {
            event.preventDefault();
            mQuery(this).find('.btn-edit').first().click();
        });
    }

    if (mQuery('#mauticforms_actions')) {
        //make the fields sortable
        mQuery('#mauticforms_actions').sortable({
            items: '.mauticform-row',
            handle: '.reorder-handle',
            stop: function(i) {
                mQuery.ajax({
                    type: "POST",
                    url: mauticAjaxUrl + "?action=form:reorderActions",
                    data: mQuery('#mauticforms_actions').sortable("serialize") + "&formId=" + mQuery('#mauticform_sessionId').val()
                });
            }
        });

        mQuery('#mauticforms_actions .mauticform-row').on('mouseover.mauticformactions', function() {
            mQuery(this).find('.form-buttons').removeClass('hide');
        }).on('mouseout.mauticformactions', function() {
            mQuery(this).find('.form-buttons').addClass('hide');
        }).on('dblclick.mauticformactions', function(event) {
            event.preventDefault();
            mQuery(this).find('.btn-edit').first().click();
        });
    }

    if (typeof Mautic.formSubmissionChart === 'undefined') {
        Mautic.renderSubmissionChart();
    }
};

Mautic.getFormId = function() {
    return mQuery('input#formId').val();
}

Mautic.formOnUnload = function(id) {
    if (id === '#app-content') {
        delete Mautic.formSubmissionChart;
    }
};

Mautic.formFieldOnLoad = function (container, response) {
    //new field created so append it to the form
    if (response.fieldHtml) {
        var newHtml = response.fieldHtml;
        var fieldId = '#mauticform_' + response.fieldId;
        if (mQuery(fieldId).length) {
            //replace content
            mQuery(fieldId).replaceWith(newHtml);
            var newField = false;
        } else {
            //append content
            mQuery(newHtml).appendTo('#mauticforms_fields');
            var newField = true;
        }
        //activate new stuff
        mQuery(fieldId + " a[data-toggle='ajax']").click(function (event) {
            event.preventDefault();
            return Mautic.ajaxifyLink(this, event);
        });
        //initialize tooltips
        mQuery(fieldId + " *[data-toggle='tooltip']").tooltip({html: true});

        //initialize ajax'd modals
        mQuery(fieldId + " a[data-toggle='ajaxmodal']").on('click.ajaxmodal', function (event) {
            event.preventDefault();

            Mautic.ajaxifyModal(this, event);
        });

        mQuery('#mauticforms_fields .mauticform-row').off(".mauticform");
        mQuery('#mauticforms_fields .mauticform-row').on('mouseover.mauticformfields', function() {
            mQuery(this).find('.form-buttons').removeClass('hide');
        }).on('mouseout.mauticformfields', function() {
            mQuery(this).find('.form-buttons').addClass('hide');
        }).on('dblclick.mauticformfields', function(event) {
            event.preventDefault();
            mQuery(this).find('.btn-edit').first().click();
        });

        //show fields panel
        if (!mQuery('#fields-panel').hasClass('in')) {
            mQuery('a[href="#fields-panel"]').trigger('click');
        }

        if (newField) {
            mQuery('.bundle-main-inner-wrapper').scrollTop(mQuery('.bundle-main-inner-wrapper').height());
        }

        if (mQuery('#form-field-placeholder').length) {
            mQuery('#form-field-placeholder').remove();
        }
    }
};

Mautic.formActionOnLoad = function (container, response) {
    //new action created so append it to the form
    if (response.actionHtml) {
        var newHtml = response.actionHtml;
        var actionId = '#mauticform_action_' + response.actionId;
        if (mQuery(actionId).length) {
            //replace content
            mQuery(actionId).replaceWith(newHtml);
            var newField = false;
        } else {
            //append content
            mQuery(newHtml).appendTo('#mauticforms_actions');
            var newField = true;
        }
        //activate new stuff
        mQuery(actionId + " a[data-toggle='ajax']").click(function (event) {
            event.preventDefault();
            return Mautic.ajaxifyLink(this, event);
        });
        //initialize tooltips
        mQuery(actionId + " *[data-toggle='tooltip']").tooltip({html: true});

        //initialize ajax'd modals
        mQuery(actionId + " a[data-toggle='ajaxmodal']").on('click.ajaxmodal', function (event) {
            event.preventDefault();

            Mautic.ajaxifyModal(this, event);
        });

        mQuery('#mauticforms_actions .mauticform-row').off(".mauticform");
        mQuery('#mauticforms_actions .mauticform-row').on('mouseover.mauticformactions', function() {
            mQuery(this).find('.form-buttons').removeClass('hide');
        }).on('mouseout.mauticformactions', function() {
            mQuery(this).find('.form-buttons').addClass('hide');
        }).on('dblclick.mauticformactions', function(event) {
            event.preventDefault();
            mQuery(this).find('.btn-edit').first().click();
        });

        //show actions panel
        if (!mQuery('#actions-panel').hasClass('in')) {
            mQuery('a[href="#actions-panel"]').trigger('click');
        }

        if (newField) {
            mQuery('.bundle-main-inner-wrapper').scrollTop(mQuery('.bundle-main-inner-wrapper').height());
        }

        if (mQuery('#form-action-placeholder').length) {
            mQuery('#form-action-placeholder').remove();
        }
    }
};

Mautic.onPostSubmitActionChange = function(value) {
    if (value == 'return') {
        //remove required class
        mQuery('#mauticform_postActionProperty').prev().removeClass('required');
    } else {
        mQuery('#mauticform_postActionProperty').prev().addClass('required');
    }

    mQuery('#mauticform_postActionProperty').next().html('');
    mQuery('#mauticform_postActionProperty').parent().removeClass('has-error');
};

Mautic.renderSubmissionChart = function (chartData) {
    if (!mQuery('#submission-chart').length) {
        return;
    }
    if (!chartData) {
        chartData = mQuery.parseJSON(mQuery('#submission-chart-data').text());
    }
    var ctx = document.getElementById("submission-chart").getContext("2d");
    var options = {};

    if (typeof Mautic.formSubmissionChart === 'undefined') {
        Mautic.formSubmissionChart = new Chart(ctx).Line(chartData, options);
    } else {
        Mautic.formSubmissionChart.destroy();
        Mautic.formSubmissionChart = new Chart(ctx).Line(chartData, options);
    }
};

Mautic.updateSubmissionChart = function(element, amount, unit) {
    var element = mQuery(element);
    var wrapper = element.closest('ul');
    var button  = mQuery('#time-scopes .button-label');
    var formId = Mautic.getFormId();
    wrapper.find('a').removeClass('bg-primary');
    element.addClass('bg-primary');
    button.text(element.text());
    var query = "action=form:updateSubmissionChart&amount=" + amount + "&unit=" + unit + "&formId=" + formId;
    mQuery.ajax({
        url: mauticAjaxUrl,
        type: "POST",
        data: query,
        dataType: "json",
        success: function (response) {
            if (response.success) {
                Mautic.renderSubmissionChart(response.stats);
            }
        },
        error: function (request, textStatus, errorThrown) {
            Mautic.processAjaxError(request, textStatus, errorThrown);
        }
    });
}
