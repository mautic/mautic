//DashboardBundle
Mautic.dashboardOnLoad = function (container) {
    Mautic.renderDashboardMap();
    Mautic.renderOpenRateDoughnut();
    Mautic.renderClickRateDoughnut();
    Mautic.updateActiveVisitorCount();

    // Refresh page visits every 5 sec
    Mautic.ActiveVisitorsLoop = setInterval(function() {
        Mautic.updateActiveVisitorCount();
    }, 5000);

};

Mautic.dashboardOnUnload = function(id) {
    // Trash initialized dashboard vars on app content change.
    mQuery('.jvectormap-tip').remove();
    if (id === '#app-content') {
        delete Mautic.dashboardMapData;
        Mautic.dashboardMap.remove();
        delete Mautic.dashboardClickRateDoughnutObject;
        delete Mautic.dashboardOpenRateDoughnutObject;
        delete Mautic.ActiveVisitorsCount;
        clearInterval(Mautic.ActiveVisitorsLoop);
    }
};

Mautic.renderDashboardMap = function () {
    // Initilize map only for first time
    if (typeof Mautic.dashboardMapData === 'object') {
        return;
    }

    Mautic.dashboardMapData = mQuery.parseJSON(mQuery('#dashboard-map-data').text());
    var element = mQuery('#dashboard-map');
    element.vectorMap({
        backgroundColor: 'transparent',
        zoomOnScroll: false,
        regionStyle: {
            initial: {
                fill: '#dce0e5',
                "fill-opacity": 1,
                stroke: 'none',
                "stroke-width": 0,
                "stroke-opacity": 1
            },
            hover: {
                "fill-opacity": 0.7,
                cursor: 'pointer'
            }
        },
        map: 'world_mill_en',
        series: {
            regions: [{
                values: Mautic.dashboardMapData,
                scale: ['#C8EEFF', '#006491'],
                normalizeFunction: 'polynomial'
            }]
        },
        onRegionTipShow: function (event, label, index) {
            if(Mautic.dashboardMapData[index] > 0) {
                label.html(
                    '<b>'+label.html()+'</b></br>'+
                    Mautic.dashboardMapData[index]+' Leads'
                );
            }
        }
    });
    Mautic.dashboardMap = element.vectorMap('get', 'mapObject');
}

Mautic.renderOpenRateDoughnut = function () {
    // Initilize chart only for first time
    if (typeof Mautic.dashboardOpenRateDoughnutObject === 'object') {
        return;
    }
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
    data = Mautic.emulateNoDataForPieChart(data);
    var ctx = document.getElementById("open-rate").getContext("2d");
    Mautic.dashboardOpenRateDoughnutObject = new Chart(ctx).Doughnut(data, options);
}

Mautic.renderClickRateDoughnut = function () {
    // Initilize chart only for first time
    if (typeof Mautic.dashboardClickRateDoughnutObject === 'object') {
        return;
    }
    var element = mQuery('#click-rate');
    var readCount = +element.attr('data-read-count');
    var clickCount = +element.attr('data-click-count');
    var options = {percentageInnerCutout: 65, responsive: false}
    var data = [
        {
            value: clickCount,
            color:"#35B4B9",
            highlight: "#227276",
            label: "Clicked"
        },
        {
            value: readCount - clickCount,
            color: "#efeeec",
            highlight: "#EBEBEB",
            label: "Not clicked"
        }
    ];
    data = Mautic.emulateNoDataForPieChart(data);
    var ctx = document.getElementById("click-rate").getContext("2d");
    Mautic.dashboardClickRateDoughnutObject = new Chart(ctx).Doughnut(data, options);
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
