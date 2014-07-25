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
                MauticVars.showLoadingBar = false;
                mQuery.ajax({
                    type: "POST",
                    url: mauticAjaxUrl + "?action=form:reorderFields",
                    data: mQuery('#mauticforms_fields').sortable("serialize")});
            }
        });

        mQuery('#mauticforms_fields .mauticform-row').on('mouseover.mauticformfields', function() {
           mQuery(this).find('.form-buttons').removeClass('hide');
        }).on('mouseout.mauticformfields', function() {
            mQuery(this).find('.form-buttons').addClass('hide');
        });
    }

    if (mQuery('#mauticforms_actions')) {
        //make the fields sortable
        mQuery('#mauticforms_actions').sortable({
            items: '.mauticform-row',
            handle: '.reorder-handle',
            stop: function(i) {
                MauticVars.showLoadingBar = false;
                mQuery.ajax({
                    type: "POST",
                    url: mauticAjaxUrl + "?action=form:reorderActions",
                    data: mQuery('#mauticforms_actions').sortable("serialize")});
            }
        });

        mQuery('#mauticforms_actions .mauticform-row').on('mouseover.mauticformactions', function() {
            mQuery(this).find('.form-buttons').removeClass('hide');
        }).on('mouseout.mauticformactions', function() {
            mQuery(this).find('.form-buttons').addClass('hide');
        });
    }
};

Mautic.formfieldOnLoad = function (container, response) {
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

        mQuery('#mauticforms_fields .mauticform-row').off(".mauticform");
        mQuery('#mauticforms_fields .mauticform-row').on('mouseover.mauticformfields', function() {
            mQuery(this).find('.form-buttons').removeClass('hide');
        }).on('mouseout.mauticformfields', function() {
            mQuery(this).find('.form-buttons').addClass('hide');
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

Mautic.formactionOnLoad = function (container, response) {
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

        mQuery('#mauticforms_actions .mauticform-row').off(".mauticform");
        mQuery('#mauticforms_actions .mauticform-row').on('mouseover.mauticformactions', function() {
            mQuery(this).find('.form-buttons').removeClass('hide');
        }).on('mouseout.mauticformactions', function() {
            mQuery(this).find('.form-buttons').addClass('hide');
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