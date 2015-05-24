/** EmailBundle **/
Mautic.emailOnLoad = function (container, response) {
    if (response && response.updateSelect) {
        //added email through a popup
        var newOption = mQuery('<option />').val(response.emailId);
        newOption.html(response.emailId + ':' + response.emailSubject);

        var opener = window.opener;
        if(opener) {
            var el = '#' + response.updateSelect;
            var optgroup = el + " optgroup[label=" + response.emailLang + "]";
            if (opener.mQuery(optgroup).length) {
                //the optgroup exist so append to it
                opener.mQuery(optgroup + " option:last").prev().before(newOption);
            } else {
                //create the optgroup
                var newOptgroup = mQuery('<optgroup label="' + response.emailLang + '" />');
                newOption.appendTo(newOptgroup);
                opener.mQuery(newOptgroup).appendTo(opener.mQuery(el));
            }
            opener.mQuery(el + " option:last").prev().before(newOption);
            newOption.prop('selected', true);

            opener.mQuery(el).trigger("chosen:updated");
        }

        window.close();
    } else if (mQuery('#emailform_plainText').length) {
        // Activate the plain text editor to support token inserts

        // Get the plain text first
        var plainText = mQuery('#emailform_plainText').val();

        // Now empty it so that ckeditor doesn't load it as html
        mQuery('#emailform_plainText').val('');

        mQuery('#emailform_plainText').ckeditor({
            removePlugins: 'toolbar,elementspath,resize',
            extraPlugins: 'tokens',
            autoParagraph: false,
            on: {
                instanceReady: function( event ) {
                    event.editor.insertText(plainText);
                }
            }
        });
    } else {
        if (mQuery(container + ' #list-search').length) {
            Mautic.activateSearchAutocomplete('list-search', 'email');
        }

        if (typeof Mautic.listCompareChart === 'undefined') {
            Mautic.renderListCompareChart();
        }

        Mautic.initializeEmailFilters(container);
    }
};

Mautic.emailOnUnload = function(id) {
    if (id === '#app-content') {
        delete Mautic.listCompareChart;
    }

    if (mQuery('#emailform_plainText').length) {
        // Activate the plain text editor to support token inserts
        CKEDITOR.instances['emailform_plainText'].destroy(true);
    }
};

Mautic.renderListCompareChart = function () {
    if (!mQuery("#list-compare-chart").length) {
        return;
    }
    var options = {
        legendTemplate: "<% for (var i=0; i<datasets.length; i++){%><span class=\"label label-default mr-xs\" style=\"background-color:<%=datasets[i].fillColor%>\"><%if(datasets[i].label){%><%=datasets[i].label%><%}%></span><%}%>"
    };
    var data = mQuery.parseJSON(mQuery('#list-compare-chart-data').text());
    Mautic.listCompareChart = new Chart(document.getElementById("list-compare-chart").getContext("2d")).Bar(data, options);
    var legendHolder = document.createElement('div');
    legendHolder.innerHTML = Mautic.listCompareChart.generateLegend();
    mQuery('#legend').html(legendHolder);
    Mautic.listCompareChart.update();
};

Mautic.initializeEmailFilters = function(container) {
    var emailForm = mQuery(container + ' #email-filters');
    if (emailForm.length) {
        emailForm.on('change', function() {
            emailForm.submit();
        }).on('keyup', function() {
            emailForm.delay(200).submit();
        }).on('submit', function(e) {
            e.preventDefault();
            var formData = emailForm.serialize();
            var request = window.location.pathname + '?tmpl=list&name=email&' + formData;
            Mautic.loadContent(request, '', 'POST', '.page-list');
        });
    }
};

Mautic.insertEmailBuilderToken = function(editorId, token) {
    var editor = Mautic.getEmailBuilderEditorInstances();
    editor[instance].insertText(token);
};

Mautic.getEmailAbTestWinnerForm = function(abKey) {
    if (abKey && mQuery(abKey).val() && mQuery(abKey).closest('.form-group').hasClass('has-error')) {
        mQuery(abKey).closest('.form-group').removeClass('has-error');
        if (mQuery(abKey).next().hasClass('help-block')) {
            mQuery(abKey).next().remove();
        }
    }

    Mautic.activateLabelLoadingIndicator('emailform_variantSettings_winnerCriteria');
    var emailId = mQuery('#emailform_sessionId').val();

    var query = "action=email:getAbTestForm&abKey=" + mQuery(abKey).val() + "&emailId=" + emailId;

    mQuery.ajax({
        url: mauticAjaxUrl,
        type: "POST",
        data: query,
        dataType: "json",
        success: function (response) {
            if (typeof response.html != 'undefined') {
                if (mQuery('#emailform_variantSettings_properties').length) {
                    mQuery('#emailform_variantSettings_properties').replaceWith(response.html);
                } else {
                    mQuery('#emailform_variantSettings').append(response.html);
                }

                if (response.html != '') {
                    Mautic.onPageLoad('#emailform_variantSettings_properties', response);
                }
            }
        },
        error: function (request, textStatus, errorThrown) {
            Mautic.processAjaxError(request, textStatus, errorThrown);
        },
        complete: function() {
            Mautic.removeLabelLoadingIndicator();
        }
    });
};

Mautic.loadNewEmailWindow = function(options) {
    if (options.windowUrl) {
        Mautic.startModalLoadingBar();

        setTimeout(function() {
            var generator = window.open(options.windowUrl, 'newemailwindow', 'height=600,width=1100');

            if (!generator || generator.closed || typeof generator.closed == 'undefined') {
                alert(response.popupBlockerMessage);
            } else {
                generator.onload = function () {
                    Mautic.stopModalLoadingBar();
                    Mautic.stopIconSpinPostEvent();
                };
            }
        }, 100);
    }
};

Mautic.submitSendForm = function () {
    Mautic.dismissConfirmation();
    mQuery('.btn-send').prop('disabled', true);
    mQuery('form[name=\'batch_send\']').submit();
};

Mautic.emailSendOnLoad = function (container, response) {
    if (mQuery('.email-send-progress').length) {
        if (!mQuery('#emailSendProgress').length) {
            Mautic.clearModeratedInterval('emailSendProgress');
        } else {
            Mautic.setModeratedInterval('emailSendProgress', 'sendEmailBatch', 2000);
        }
    }
};

Mautic.emailSendOnUnload = function () {
    if (mQuery('.email-send-progress').length) {
        Mautic.clearModeratedInterval('emailSendProgress');
        if (typeof Mautic.sendEmailBatchXhr != 'undefined') {
            Mautic.sendEmailBatchXhr.abort();
            delete Mautic.sendEmailBatchXhr;
        }
    }
};

Mautic.sendEmailBatch = function () {
    var data = 'id=' + mQuery('.progress-bar').data('email') + '&pending=' + mQuery('.progress-bar').attr('aria-valuemax') + '&batchlimit=' + mQuery('.progress-bar').data('batchlimit');
    Mautic.sendEmailBatchXhr = Mautic.ajaxActionRequest('email:sendBatch', data, function (response) {
        if (response.progress) {
            if (response.progress[0] > 0) {
                mQuery('.imported-count').html(response.progress[0]);
                mQuery('.progress-bar').attr('aria-valuenow', response.progress[0]).css('width', response.percent + '%');
                mQuery('.progress-bar span.sr-only').html(response.percent + '%');
            }

            if (response.progress[0] >= response.progress[1]) {
                Mautic.clearModeratedInterval('emailSendProgress');

                setTimeout(function () {
                    mQuery.ajax({
                        type: 'POST',
                        showLoadingBar: false,
                        url: window.location,
                        data: 'complete=1',
                        success: function (response) {

                            if (response.newContent) {
                                // It's done so pass to process page
                                Mautic.processPageContent(response);
                            }
                        }
                    });
                }, 1000);
            }
        }

        Mautic.moderatedIntervalCallbackIsComplete('emailSendProgress');
    });
};

Mautic.autoGeneratePlaintext = function() {
    mQuery('.plaintext-spinner').removeClass('hide');

    var mode = (mQuery('#emailform_contentMode_0').prop('checked')) ? 'custom' : 'template';
    var custom = mQuery('#emailform_customHtml').val();
    var id = mQuery('#emailform_sessionId').val();

    var data = {
        mode: mode,
        id: id,
        custom: custom
    };

    Mautic.ajaxActionRequest(
        'email:generatePlaintText',
        data,
        function (response) {
            CKEDITOR.instances['emailform_plainText'].insertText(response.text);
            mQuery('.plaintext-spinner').addClass('hide');
        }
    );
};