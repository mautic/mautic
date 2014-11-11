/** EmailBundle **/
Mautic.emailOnLoad = function (container) {
    if (mQuery(container + ' #list-search').length) {
        Mautic.activateSearchAutocomplete('list-search', 'email');
    }

    if (mQuery(container + ' form[name="emailform"]').length) {
        Mautic.activateCategoryLookup('emailform', 'email');
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
                    var editor   = document.getElementById('builder-template-content').contentWindow.CKEDITOR.instances;
                    var token = mQuery(ui.draggable).find('input.email-token').val();
                    editor[instance].insertText(token);
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
        mQuery("ul.draggable li").draggable('destroy');

        //kill the iframe
        mQuery('#builder-template-content').remove();

    }, 3000);
};

Mautic.emailEditorOnLoad = function (container) {
    //activate builder drag and drop
    mQuery(container + " a[data-token]").draggable({
        iframeFix: true,
        iframeId: 'builder-template-content',
        helper: function() {
            return mQuery('<div><i class="fa fa-lg fa-crosshairs"></i></div>');
        },
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
}