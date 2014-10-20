//DashboardBundle
Mautic.dashboardOnLoad = function (container) {
    Mautic.loadDashboardMap();
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
