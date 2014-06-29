//FormBundle
Mautic.formOnLoad = function (container) {
    if ($(container + ' #list-search').length) {
        Mautic.activateSearchAutocomplete('list-search', 'form.form');
    }

    if ($('#mauticforms_fields')) {
        //make the fields sortable
        $('#mauticforms_fields').sortable({
            items: '.mauticform-row',
            handle: '.reorder-handle',
            stop: function(i) {
                MauticVars.showLoadingBar = false;
                $.ajax({
                    type: "POST",
                    url: mauticBaseUrl + "ajax?action=form:reorderFields",
                    data: $('#mauticforms_fields').sortable("serialize")});
            }
        });

        $('#mauticforms_fields .mauticform-row').on('mouseover.mauticformfields', function() {
           $(this).find('.form-buttons').removeClass('hide');
        }).on('mouseout.mauticformfields', function() {
            $(this).find('.form-buttons').addClass('hide');
        });
    }

    if ($('#mauticforms_actions')) {
        //make the fields sortable
        $('#mauticforms_actions').sortable({
            items: '.mauticform-row',
            handle: '.reorder-handle',
            stop: function(i) {
                MauticVars.showLoadingBar = false;
                $.ajax({
                    type: "POST",
                    url: mauticBaseUrl + "ajax?action=form:reorderActions",
                    data: $('#mauticforms_actions').sortable("serialize")});
            }
        });

        $('#mauticforms_actions .mauticform-row').on('mouseover.mauticformactions', function() {
            $(this).find('.form-buttons').removeClass('hide');
        }).on('mouseout.mauticformactions', function() {
            $(this).find('.form-buttons').addClass('hide');
        });
    }
};

Mautic.formfieldOnLoad = function (container, response) {
    //new field created so append it to the form
    if (response.fieldHtml) {
        var newHtml = response.fieldHtml;
        var fieldId = '#mauticform_' + response.fieldId;
        if ($(fieldId).length) {
            //replace content
            $(fieldId).replaceWith(newHtml);
            var newField = false;
        } else {
            //append content
            $(newHtml).appendTo('#mauticforms_fields');
            var newField = true;
        }
        //activate new stuff
        $(fieldId + " a[data-toggle='ajax']").click(function (event) {
            event.preventDefault();
            return Mautic.ajaxifyLink(this, event);
        });
        //initialize tooltips
        $(fieldId + " *[data-toggle='tooltip']").tooltip({html: true});

        $('#mauticforms_fields .mauticform-row').off(".mauticform");
        $('#mauticforms_fields .mauticform-row').on('mouseover.mauticformfields', function() {
            $(this).find('.form-buttons').removeClass('hide');
        }).on('mouseout.mauticformfields', function() {
            $(this).find('.form-buttons').addClass('hide');
        });

        //show fields panel
        if (!$('#fields-panel').hasClass('in')) {
            $('a[href="#fields-panel"]').trigger('click');
        }

        if (newField) {
            $('.bundle-main-inner-wrapper').scrollTop($('.bundle-main-inner-wrapper').height());
        }

        if ($('#form-field-placeholder').length) {
            $('#form-field-placeholder').remove();
        }
    }
};

Mautic.formactionOnLoad = function (container, response) {
    //new action created so append it to the form
    if (response.actionHtml) {
        var newHtml = response.actionHtml;
        var actionId = '#mauticform_action_' + response.actionId;
        if ($(actionId).length) {
            //replace content
            $(actionId).replaceWith(newHtml);
            var newField = false;
        } else {
            //append content
            $(newHtml).appendTo('#mauticforms_actions');
            var newField = true;
        }
        //activate new stuff
        $(actionId + " a[data-toggle='ajax']").click(function (event) {
            event.preventDefault();
            return Mautic.ajaxifyLink(this, event);
        });
        //initialize tooltips
        $(actionId + " *[data-toggle='tooltip']").tooltip({html: true});

        $('#mauticforms_actions .mauticform-row').off(".mauticform");
        $('#mauticforms_actions .mauticform-row').on('mouseover.mauticformactions', function() {
            $(this).find('.form-buttons').removeClass('hide');
        }).on('mouseout.mauticformactions', function() {
            $(this).find('.form-buttons').addClass('hide');
        });

        //show actions panel
        if (!$('#actions-panel').hasClass('in')) {
            $('a[href="#actions-panel"]').trigger('click');
        }

        if (newField) {
            $('.bundle-main-inner-wrapper').scrollTop($('.bundle-main-inner-wrapper').height());
        }

        if ($('#form-action-placeholder').length) {
            $('#form-action-placeholder').remove();
        }
    }
};

Mautic.onPostSubmitActionChange = function(value) {
    if (value == 'return') {
        //remove required class
        $('#mauticform_postActionProperty').prev().removeClass('required');
    } else {
        $('#mauticform_postActionProperty').prev().addClass('required');
    }

    $('#mauticform_postActionProperty').next().html('');
    $('#mauticform_postActionProperty').parent().removeClass('has-error');
};