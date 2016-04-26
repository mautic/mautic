//PageBundle
Mautic.pageOnLoad = function (container) {
    if (mQuery(container + ' #list-search').length) {
        Mautic.activateSearchAutocomplete('list-search', 'page.page');
    }

    if (mQuery(container + ' #page_template').length) {
        Mautic.toggleBuilderButton(mQuery('#page_template').val() == '');
    }
    
    if (mQuery(container + ' form[name="page"]').length) {
        mQuery("*[data-toggle='field-lookup']").each(function (index) {
            var target = mQuery(this).attr('data-target');
            var field  = mQuery(this).attr('id');
            var options = mQuery(this).attr('data-options');
            Mautic.activatePageFieldTypeahead(field, target, options);
        });
    }
    
    //Handle autohide of "Redirect URL" field if "Redirect Type" is none
    if (mQuery(container + ' select[name="page[redirectType]"]').length) {
        //Auto-hide on page loading
        Mautic.autoHideRedirectUrl(container);
        
        //Auto-hide on select changing
        mQuery(container + ' select[name="page[redirectType]"]').chosen().change(function(){
            Mautic.autoHideRedirectUrl(container);
        });
    }
};

Mautic.getPageAbTestWinnerForm = function(abKey) {
    if (abKey && mQuery(abKey).val() && mQuery(abKey).closest('.form-group').hasClass('has-error')) {
        mQuery(abKey).closest('.form-group').removeClass('has-error');
        if (mQuery(abKey).next().hasClass('help-block')) {
            mQuery(abKey).next().remove();
        }
    }

    Mautic.activateLabelLoadingIndicator('page_variantSettings_winnerCriteria');

    var pageId = mQuery('#page_sessionId').val();
    var query  = "action=page:getAbTestForm&abKey=" + mQuery(abKey).val() + "&pageId=" + pageId;

    mQuery.ajax({
        url: mauticAjaxUrl,
        type: "POST",
        data: query,
        dataType: "json",
        success: function (response) {
            if (typeof response.html != 'undefined') {
                if (mQuery('#page_variantSettings_properties').length) {
                    mQuery('#page_variantSettings_properties').replaceWith(response.html);
                } else {
                    mQuery('#page_variantSettings').append(response.html);
                }

                if (response.html != '') {
                    Mautic.onPageLoad('#page_variantSettings_properties', response);
                }
            }

            Mautic.removeLabelLoadingIndicator();

        },
        error: function (request, textStatus, errorThrown) {
            Mautic.processAjaxError(request, textStatus, errorThrown);
            spinner.remove();
        },
        complete: function () {
            Mautic.removeLabelLoadingIndicator();
        }
    });
};

Mautic.activatePageFieldTypeahead = function(field, target, options) {
    if (options) {
        var keys = values = [];
        //check to see if there is a key/value split
        options = options.split('||');
        if (options.length == 2) {
            keys = options[1].split('|');
            values = options[0].split('|');
        } else {
            values = options[0].split('|');
        }

        var fieldTypeahead = Mautic.activateTypeahead('#' + field, {
            dataOptions: values,
            dataOptionKeys: keys,
            minLength: 0
        });
    } else {
        var fieldTypeahead = Mautic.activateTypeahead('#' + field, {
            prefetch: true,
            remote: true,
            action: "page:fieldList&field=" + target
        });
    }
    
    mQuery(fieldTypeahead).on('typeahead:selected', function (event, datum) {
        if (mQuery("#" + field).length && datum["value"]) {
            mQuery("#" + field).val(datum["value"]);
        }
    }).on('typeahead:autocompleted', function (event, datum) {
        if (mQuery("#" + field).length && datum["value"]) {
            mQuery("#" + field).val(datum["value"]);
        }
    });
};

Mautic.autoHideRedirectUrl = function(container) {
    var select = mQuery(container + ' select[name="page[redirectType]"]');
    var input = mQuery(container + ' input[name="page[redirectUrl]"]');
    
    //If value is none we autohide the "Redirect URL" field and empty it
    if (select.val() == '') {
        input.closest('.form-group').hide();
        input.val('');
    } else {
        input.closest('.form-group').show();
    }
};
