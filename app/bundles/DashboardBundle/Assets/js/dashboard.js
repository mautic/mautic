//DashboardBundle
Mautic.dashboardOnLoad = function (container) {
    Mautic.loadDashboardMap();
    Mautic.renderOpenRateDoughnut();
    Mautic.renderClickRateDoughnut();
    Mautic.updateActiveVisitorCount();
};

Mautic.loadDashboardMap = function () {
    var mapData = {};
    mapData = mQuery.parseJSON(mQuery('#dashboard-map-data').text());
    jQuery('#dashboard-map').vectorMap({
        map: 'world_en',
        backgroundColor: null,
        color: '#ffffff',
        hoverOpacity: 0.7,
        selectedColor: '#666666',
        enableZoom: true,
        showTooltip: true,
        values: mapData,
        scaleColors: ['#C8EEFF', '#006491'],
        normalizeFunction: 'polynomial',
        onLabelShow: function (event, label, code) {
            if(mapData[code] > 0) {
                label.find('span').remove();
                label.append('<span>: '+mapData[code]+' Leads<span>');
            }
        }
    });
}

Mautic.renderOpenRateDoughnut = function () {
    var element = mQuery('#open-rate');
    var sentCount = +element.attr('data-sent-count');
    var readCount = +element.attr('data-read-count');
    var options = {percentageInnerCutout: 65, responsive: false}
    var data = [
        {
            value: readCount,
            color:"#4E5D9D",
            highlight: "#353F6A",
            label: "Opened"
        },
        {
            value: sentCount - readCount,
            color: "#efeeec",
            highlight: "#EBEBEB",
            label: "Not opened"
        }
    ];
    var ctx = document.getElementById("open-rate").getContext("2d");
    var myNewChart = new Chart(ctx).Doughnut(data, options);
}

Mautic.renderClickRateDoughnut = function () {
    var element = mQuery('#click-rate');
    var sentCount = +element.attr('data-read-count');
    var readCount = +element.attr('data-click-count');
    var options = {percentageInnerCutout: 65, responsive: false}
    var data = [
        {
            value: readCount,
            color:"#35B4B9",
            highlight: "#227276",
            label: "Clicked"
        },
        {
            value: sentCount - readCount,
            color: "#efeeec",
            highlight: "#EBEBEB",
            label: "Not clicked"
        }
    ];
    var ctx = document.getElementById("click-rate").getContext("2d");
    var myNewChart = new Chart(ctx).Doughnut(data, options);
}

Mautic.updateActiveVisitorCount = function () {
    mQuery.ajax({
        url: mauticAjaxUrl,
        type: "POST",
        data: "action=dashboard:viewingVisitors",
        dataType: "json",
        success: function (response) {
            if (response.success) {
                var element = mQuery('#active-visitors');
                element.text(response.viewingVisitors);
                if (response.viewingVisitors != Mautic.ActiveVisitorsCount) {
                    var color = '#34D43B';
                    if (Mautic.ActiveVisitorsCount > response.viewingVisitors) {
                        color = '#BC2525';
                    }
                    element.css('text-shadow', color+' 0px 0px 50px');
                    setTimeout(function() {
                        element.css('text-shadow', '#fff 0px 0px 50px');
                    }, 3000);
                }
                Mautic.ActiveVisitorsCount = response.viewingVisitors;
            }
        },
        error: function (request, textStatus, errorThrown) {
            Mautic.processAjaxError(request, textStatus, errorThrown);
        }
    });
}
