//DashboardBundle
Mautic.dashboardOnLoad = function (container) {
    Mautic.loadDashboardMap();
    Mautic.renderOpenRateDoughnut();
    Mautic.updateActiveVisitorCount();
    setInterval(function() {
        Mautic.updateActiveVisitorCount();
    }, 5000);
    
};

Mautic.loadDashboardMap = function () {
    var mapData = {};
    mQuery.ajax({
        url: mauticAjaxUrl,
        type: "POST",
        data: "action=dashboard:mapData",
        dataType: "json",
        success: function (response) {
            if (response.success) {
                mapData = response.stats;
            }
            Mautic.stopIconSpinPostEvent(event);
        },
        error: function (request, textStatus, errorThrown) {
            Mautic.processAjaxError(request, textStatus, errorThrown);
            Mautic.stopIconSpinPostEvent(event);
        },
        complete: function () {
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
    });
}

Mautic.renderOpenRateDoughnut = function () {
    var element = mQuery('#open-reate');
    var sentCount = +element.attr('data-sent-count');
    var readCount = +element.attr('data-read-count');
    var options = {percentageInnerCutout: 90, responsive: false}
    var data = [
        {
            value: readCount,
            color:"#4E5D9D",
            highlight: "#353F6A",
            label: "Opened"
        },
        {
            value: sentCount - readCount,
            color: "#ffffff",
            highlight: "#EBEBEB",
            label: "Not opened"
        }
    ];
    var ctx = document.getElementById("open-reate").getContext("2d");
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
