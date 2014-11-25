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

Mautic.emailUnLoad = function() {
    //remove email builder from body
    mQuery('.builder').remove();
};

Mautic.emailOnUnload = function(id) {
    if (id === '#app-content') {
        delete Mautic.listCompareChart;
    }
};

Mautic.launchEmailEditor = function () {
    var src = mQuery('#EmailBuilderUrl').val();
    src += '?template=' + mQuery('#emailform_template').val();

    var builder = mQuery("<iframe />", {
        css: {
            margin: "0",
            padding: "0",
            border: "none",
            width: "100%",
            height: "100%"
        },
        id: "builder-template-content"
    })
        .attr('src', src)
        .appendTo('.builder-content')
        .load(function () {
            var $this = mQuery(this);
            var contents = $this.contents();
            // here, catch the droppable div and create a droppable widget
            contents.find('.mautic-editable').droppable({
                iframeFix: true,
                drop: function (event, ui) {
                    var instance = mQuery(this).attr("id");
                    var predrop  = mQuery(ui.draggable).data('predrop');
                    if (predrop) {
                        Mautic[predrop](event, ui, instance);
                    } else {
                        var editor = Mautic.getEmailBuilderEditorInstances();
                        var token  = mQuery(ui.draggable).data('token');
                        if (token) {
                            editor[instance].insertText(token);
                        }
                    }
                    mQuery(this).removeClass('over-droppable');
                },
                over: function (e, ui) {
                    mQuery(this).addClass('over-droppable');
                },
                out: function (e, ui) {
                    mQuery(this).removeClass('over-droppable');
                }
            });
        });

    //make the panel full screen
    mQuery('.builder').addClass('builder-active');
    //show it
    mQuery('.builder').removeClass('hide');

    Mautic.emailEditorOnLoad('.builder-panel');
};

Mautic.getEmailBuilderEditorInstances = function() {
    return document.getElementById('builder-template-content').contentWindow.CKEDITOR.instances;
};

Mautic.closeEmailEditor = function() {
    Mautic.stopIconSpinPostEvent();

    mQuery('.builder').addClass('hide');

    //make sure editors have lost focus so the content is updated
    mQuery('#builder-template-content').contents().find('.mautic-editable').each(function (index) {
        mQuery(this).blur();
    });

    setTimeout( function() {
        //kill the draggables
        mQuery('#builder-template-content').contents().find('.mautic-editable').droppable('destroy');
        mQuery("*[data-token]").draggable('destroy');

        //kill the iframe
        mQuery('#builder-template-content').remove();

    }, 3000);
};

Mautic.emailEditorOnLoad = function (container) {
    //activate builder drag and drop
    mQuery(container + " *[data-token]").draggable({
        iframeFix: true,
        iframeId: 'builder-template-content',
        helper: 'clone',
        appendTo: '.builder',
        zIndex: 8000,
        scroll: true,
        scrollSensitivity: 100,
        scrollSpeed: 100,
        cursorAt: {top: 15, left: 15}
    });
};

Mautic.renderListCompareChart = function () {
    if (!mQuery("#list-compare-chart").length) {
        return;
    }
    var options = {};
    var data = mQuery.parseJSON(mQuery('#list-compare-chart-data').text());
    Mautic.listCompareChart = new Chart(document.getElementById("list-compare-chart").getContext("2d")).Bar(data, options);
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

    MauticVars.showLoadingBar = false;
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