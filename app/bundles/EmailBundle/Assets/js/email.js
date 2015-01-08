/** EmailBundle **/
Mautic.emailOnLoad = function (container) {
    if (mQuery(container + ' #list-search').length) {
        Mautic.activateSearchAutocomplete('list-search', 'email');
    }

    if (typeof Mautic.listCompareChart === 'undefined') {
        Mautic.renderListCompareChart();
    }

    Mautic.initializeEmailFilters(container);
};

Mautic.emailOnUnload = function(id) {
    if (id === '#app-content') {
        delete Mautic.listCompareChart;
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

Mautic.toggleEmailContentMode = function (el) {
    var builder = (mQuery(el).val() === '0') ? false : true;

    if (builder) {
        mQuery('#customHtmlContainer').addClass('hide');
        mQuery('#builderHtmlContainer').removeClass('hide');
    } else {
        mQuery('#customHtmlContainer').removeClass('hide');
        mQuery('#builderHtmlContainer').addClass('hide');
    }
};

Mautic.getEmailAbTestWinnerForm = function(abKey) {
    if (abKey && mQuery(abKey).val() && mQuery(abKey).closest('.form-group').hasClass('has-error')) {
        mQuery(abKey).closest('.form-group').removeClass('has-error');
        if (mQuery(abKey).next().hasClass('help-block')) {
            mQuery(abKey).next().remove();
        }
    }

    var labelSpinner = mQuery("label[for='email_variantSettings_winnerCriteria']");
    var spinner = mQuery('<i class="fa fa-fw fa-spinner fa-spin"></i>');
    labelSpinner.append(spinner);

    var emailId = mQuery('#email_sessionId').val();

    var query = "action=email:getAbTestForm&abKey=" + mQuery(abKey).val() + "&emailId=" + emailId;

    mQuery.ajax({
        url: mauticAjaxUrl,
        type: "POST",
        data: query,
        dataType: "json",
        success: function (response) {
            if (typeof response.html != 'undefined') {
                if (mQuery('#email_variantSettings_properties').length) {
                    mQuery('#email_variantSettings_properties').replaceWith(response.html);
                } else {
                    mQuery('#email_variantSettings').append(response.html);
                }

                if (response.html != '') {
                    Mautic.onPageLoad('#email_variantSettings_properties', response);
                }
            }
            spinner.remove();
        },
        error: function (request, textStatus, errorThrown) {
            Mautic.processAjaxError(request, textStatus, errorThrown);
            spinner.remove();
        }
    });
};