/** EmailBundle **/
Mautic.emailOnLoad = function (container, response) {
    if (response && response.updateSelect) {
        //added email through a popup
        var newOption = mQuery('<option />').val(response.emailId);
        newOption.html(response.emailName);

        var opener = window.opener;
        if(opener) {
            var el = '#' + response.updateSelect;
            var optgroup = el + " optgroup[label=" + response.emailLang + "]";
            if (opener.mQuery(optgroup).length) {
                // update option when new option equal with option item in group.
                var firstOptionGroups = opener.mQuery(el + ' optgroup');
                var isUpdateOption = false;
                firstOptionGroups.each(function() {
                    var firstOptions = mQuery(this).children();
                    for (var i = 0; i < firstOptions.length; i++) {
                        if (firstOptions[i].value === response.emailId.toString()) {
                            firstOptions[i].text = response.emailName;
                            isUpdateOption = true;
                            break;
                        }
                    }
                });

                if (!isUpdateOption) {
                    //the optgroup exist so append to it
                    opener.mQuery(optgroup + " option:last").prev().before(newOption);
                }
            } else {
                //create the optgroup
                var newOptgroup = mQuery('<optgroup label="' + response.emailLang + '" />');
                newOption.appendTo(newOptgroup);
                opener.mQuery(newOptgroup).appendTo(opener.mQuery(el));
            }

            var chooseOneOption = opener.mQuery(el + ' option:first');

            var optionGroups = opener.mQuery(el + ' optgroup');
            optionGroups.sort(function(a, b) {
                var aLabel = mQuery(a).attr('label');
                var bLabel = mQuery(b).attr('label');

                if (aLabel > bLabel) {
                    return 1;
                } else if (aLabel < bLabel) {
                    return -1;
                } else {
                    return 0;
                }
            });

            optionGroups.each(function() {
                var options = mQuery(this).children();
                options.sort(function(a, b) {
                    if (a.text > b.text) {
                        return 1;
                    } else if (a.text < b.text) {
                        return -1;
                    } else {
                        return 0;
                    }
                });
                mQuery(this).html(options);
            });

            if (opener.mQuery(el).prop('disabled')) {
                opener.mQuery(el).prop('disabled', false);
                chooseOneOption = mQuery('<option value="">' + mauticLang.chosenChooseOne + '</option>');
            }

            opener.mQuery(el).html(chooseOneOption);
            optionGroups.appendTo(opener.mQuery(el));

            newOption.prop('selected', true);

            opener.mQuery(el).trigger("chosen:updated");

            Mautic.disabledEmailAction(opener);
        }

        window.close();
    } else if (mQuery('#emailform_plainText').length) {
        // Activate the plain text editor to support token inserts

        // Get the plain text first
        var plainText = mQuery('#emailform_plainText').val();

        // Now empty it so that ckeditor doesn't load it as html
        mQuery('#emailform_plainText').val('');

        var events = Mautic.getGlobalEditorEvents();
        events.instanceReady = function( event ) {
            event.editor.insertText(plainText);
        };

        mQuery('#emailform_plainText').ckeditor({
            removePlugins: 'elementspath,toolbar',
            extraPlugins: 'tokens',
            autoParagraph: false,
            height: "235px",
            on: events
        });

        if (mQuery('#emailform_emailType').val() == '') {
            mQuery('body').addClass('noscroll');
        }

        Mautic.toggleBuilderButton(mQuery('#emailform_template').val() == '');
    } else if (mQuery(container + ' #list-search').length) {
        Mautic.activateSearchAutocomplete('list-search', 'email');
    } else {
        if (typeof Mautic.listCompareChart === 'undefined') {
            Mautic.renderListCompareChart();
        }

        Mautic.variantChartData = 'variant';
        var switchChartData;
        switchChartData = function() {
            Mautic.variantChartData = mQuery(this).data('chart');

            // Convert this one to a span
            mQuery(this).replaceWith(function() {
                return '<span data-chart="' + Mautic.variantChartData + '">' + mQuery(this).text() + '</span>';
            });

            // Convert the other to an a tag
            var opposite = (Mautic.variantChartData == 'variant') ? 'all' : 'variant';
            mQuery('span[data-chart="' + opposite + '"]').replaceWith(function() {
                return '<a href="javascript:void(0)" data-chart="' + opposite + '">' + mQuery(this).text() + '</a>';
            });
            mQuery('a[data-chart="' + opposite + '"]').on('click', switchChartData);

            if (mQuery('#time-scopes').length) {
                var activeButton = mQuery('#time-scopes').parent().find('a').filter(
                    function (index) {
                        return mQuery.trim(mQuery(this).text()) === mQuery.trim(mQuery('#time-scopes span.button-label').text());
                    }
                );
                activeButton[0].click();
            } else {
                Mautic.updateListCompareChart();
            }
        };
        mQuery('a[data-chart]').on('click', switchChartData);
    }

    Mautic.initDateRangePicker();
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

Mautic.renderListCompareChart = function (chartData) {
    if (!mQuery("#list-compare-chart").length) {
        return;
    }

    if (!chartData) {
        var chartData = mQuery.parseJSON(mQuery('#list-compare-chart-data').text());
    } else if (chartData.stats) {
        chartData = chartData.stats;
    }

    var options = {
        legendTemplate: "<% for (var i=0; i<datasets.length; i++){%><span class=\"label label-default mr-xs\" style=\"background-color:<%=datasets[i].fillColor%>\"><%if(datasets[i].label){%><%=datasets[i].label%><%}%></span><%}%>"
    };

    Mautic.listCompareChart = new Chart(document.getElementById("list-compare-chart").getContext("2d")).Bar(chartData, options);
    var legendHolder = document.createElement('div');
    legendHolder.innerHTML = Mautic.listCompareChart.generateLegend();
    mQuery('#legend').html(legendHolder);
    Mautic.listCompareChart.update();
};

Mautic.updateListCompareChart = function() {
    var emailId         = Mautic.getEntityId();
    var includeVariants = (Mautic.variantChartData == 'all');
    var query           = "emailType=list&emailId=" + emailId + "&includeVariants=" + includeVariants;

    Mautic.ajaxActionRequest('email:updateStatsChart', query, function(response) {
        Mautic.renderListCompareChart(response);
    }, true);
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
    var data = 'id=' + mQuery('.progress-bar-send').data('email') + '&pending=' + mQuery('.progress-bar-send').attr('aria-valuemax') + '&batchlimit=' + mQuery('.progress-bar-send').data('batchlimit');
    Mautic.sendEmailBatchXhr = Mautic.ajaxActionRequest('email:sendBatch', data, function (response) {
        if (response.progress) {
            if (response.progress[0] > 0) {
                mQuery('.imported-count').html(response.progress[0]);
                mQuery('.progress-bar-send').attr('aria-valuenow', response.progress[0]).css('width', response.percent + '%');
                mQuery('.progress-bar-send span.sr-only').html(response.percent + '%');
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

    var mode = (mQuery('#emailform_template').val() == '') ? 'custom' : 'template';
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

Mautic.selectEmailType = function(emailType) {
    if (emailType == 'list') {
        mQuery('#leadList').removeClass('hide');
        mQuery('#publishStatus').addClass('hide');
        mQuery('.page-header h3').text(mauticLang.newListEmail);
    } else {
        mQuery('#publishStatus').removeClass('hide');
        mQuery('#leadList').addClass('hide');
        mQuery('.page-header h3').text(mauticLang.newTemplateEmail);
    }

    mQuery('#emailform_emailType').val(emailType);

    mQuery('body').removeClass('noscroll');

    mQuery('.email-type-modal').remove();
    mQuery('.email-type-modal-backdrop').remove();
};

Mautic.getTotalAttachmentSize = function() {
    var assets = mQuery('#emailform_assetAttachments').val();
    if (assets) {
        assets = {
            'assets': assets
        };
        Mautic.ajaxActionRequest('email:getAttachmentsSize', assets, function(response) {
            mQuery('#attachment-size').text(response.size);
        });
    } else {
        mQuery('#attachment-size').text('0');
    }
};

Mautic.standardEmailUrl = function(options) {
    if (!options) {
        return;
    }

    var url = options.windowUrl;
    if (url) {
        var editEmailKey = '/emails/edit/emailId';
        var previewEmailKey = '/email/preview/emailId';
        if (url.indexOf(editEmailKey) > -1 ||
            url.indexOf(previewEmailKey) > -1) {
            options.windowUrl = url.replace('emailId', mQuery('#campaignevent_properties_email').val());
        }
    }

    return options;
};

Mautic.disabledEmailAction = function(opener) {
    if (typeof opener == 'undefined') {
        opener = window;
    }
    var email = opener.mQuery('#campaignevent_properties_email').val();

    var disabled = email === '' || email === null;

    opener.mQuery('#campaignevent_properties_editEmailButton').prop('disabled', disabled);
    opener.mQuery('#campaignevent_properties_previewEmailButton').prop('disabled', disabled);
};
