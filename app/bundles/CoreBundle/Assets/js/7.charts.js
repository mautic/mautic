//set global Chart defaults
if (typeof Chart != 'undefined') {
    // configure global Chart options
    Chart.defaults.global.elements.line.borderWidth = 1;
    Chart.defaults.global.elements.point.radius = 2;
    Chart.defaults.global.legend.labels.boxWidth = 12;
    Chart.defaults.global.maintainAspectRatio = false;
}

/**
 * Render the chart.js charts
 *
 * @param mQuery|string scope
 */
Mautic.renderCharts = function(scope) {
    var charts = [];
    if (!Mautic.chartObjects) Mautic.chartObjects = [];

    if (mQuery.type(scope) === 'string') {
        charts = mQuery(scope).find('canvas.chart');
    } else if (scope) {
        charts = scope.find('canvas.chart');
    } else {
        charts = mQuery('canvas.chart');
    }

    if (charts.length) {
        charts.each(function(index, canvas) {
            canvas = mQuery(canvas);
            if (!canvas.hasClass('chart-rendered')) {
                if (canvas.hasClass('line-chart')) {
                    Mautic.renderLineChart(canvas)
                } else if (canvas.hasClass('pie-chart')) {
                    Mautic.renderPieChart(canvas)
                } else if (canvas.hasClass('bar-chart')) {
                    Mautic.renderBarChart(canvas)
                } else if (canvas.hasClass('liefechart-bar-chart')) {
                    Mautic.renderLifechartBarChart(canvas)
                } else if (canvas.hasClass('simple-bar-chart')) {
                    Mautic.renderSimpleBarChart(canvas)
                } else if (canvas.hasClass('horizontal-bar-chart')) {
                    Mautic.renderHorizontalBarChart(canvas)
                }
            }
            canvas.addClass('chart-rendered');
        });
    }
};

/**
 * Render the chart.js line chart
 *
 * @param mQuery element canvas
 */
Mautic.renderLineChart = function(canvas) {
    var data = mQuery.parseJSON(canvas.text());
    if (!data.labels.length || !data.datasets.length) return;
    var chart = new Chart(canvas, {
        type: 'line',
        data: data,
        options: {
            lineTension : 0.2,
            borderWidth: 1,
            scales: {
                yAxes: [{
                    ticks: {
                        beginAtZero: true
                    }
                }]
            }
        }
    });
    Mautic.chartObjects.push(chart);
};

/**
 * Render the chart.js pie chart
 *
 * @param mQuery element canvas
 */
Mautic.renderPieChart = function(canvas) {
    var data = mQuery.parseJSON(canvas.text());
    var options = {borderWidth: 1};
    var disableLegend = canvas.attr('data-disable-legend');
    if (typeof disableLegend !== 'undefined' && disableLegend !== false) {
        options.legend = {
            display: false
        }
    }
    // data = Mautic.emulateNoDataForPieChart(data);
    var chart = new Chart(canvas, {
        type: 'pie',
        data: data,
        options: options
    });
    Mautic.chartObjects.push(chart);
};

/**
 * Render the chart.js bar chart
 *
 * @param mQuery element canvas
 */
Mautic.renderBarChart = function(canvas) {
    var data = mQuery.parseJSON(canvas.text());
    var chart = new Chart(canvas, {
        type: 'bar',
        data: data,
        options: {
            scales: {
                xAxes: [{
                    barPercentage: 0.9,
                }]
            }
        }
    });
    Mautic.chartObjects.push(chart);
};

/**
 * Render the chart.js bar chart
 *
 * @param mQuery element canvas
 */
Mautic.renderLifechartBarChart = function(canvas) {
    var canvasWidth = mQuery(canvas).parent().width();
    var barWidth    = (canvasWidth < 300) ? 5 : 25;
    var data = mQuery.parseJSON(canvas.text());
    var chart = new Chart(canvas, {
        type: 'bar',
        data: data,
        options: {
            scales: {
                xAxes: [
                    {
                        barThickness: barWidth,
                    }
                ]
            }
        }
    });
    Mautic.chartObjects.push(chart);
};

/**
 * Render the chart.js simple bar chart
 *
 * @param mQuery element canvas
 */
Mautic.renderSimpleBarChart = function(canvas) {
    var data = mQuery.parseJSON(canvas.text());
    var chart = new Chart(canvas, {
        type: 'bar',
        data: data,
        options: {
            scales: {
                xAxes: [{
                    stacked: false,
                    ticks: {fontSize: 9},
                    gridLines: {display:false},
                }],
                yAxes: [{
                    display: false,
                    stacked: false,
                    ticks: {beginAtZero: true, display: false},
                    gridLines: {display:false}
                }],
                display: false
            },
            legend: {
                display: false
            }
        }
    });
    Mautic.chartObjects.push(chart);
};

/**
 * Render the chart.js simple bar chart
 *
 * @param mQuery element canvas
 */
Mautic.renderHorizontalBarChart = function(canvas) {
    var data = mQuery.parseJSON(canvas.text());
    var chart = new Chart(canvas, {
        type: 'horizontalBar',
        data: data,
        options: {
            scales: {
                xAxes: [{
                    display: true,
                    stacked: false,
                    gridLines: {display:false},
                    ticks: {beginAtZero: true,display: true, fontSize: 8, stepSize: 5}
                }],
                yAxes: [{
                    stacked: false,
                    ticks: {beginAtZero: true, display: true, fontSize: 9},
                    gridLines: {display:false},
                    barPercentage: 0.5,
                    categorySpacing: 1
                }],
                display: false
            },
            legend: {
                display: false
            },
            tooltips: {
                mode: 'single',
                bodyFontSize: 9,
                bodySpacing: 0,
                callbacks: {
                    title: function(tooltipItems, data) {
                        // Title doesn't make sense for scatter since we format the data as a point
                        return '';
                    },
                    label: function(tooltipItem, data) {
                        return  tooltipItem.xLabel + ': ' + tooltipItem.yLabel;
                    }
                }

            }
        }
    });
    Mautic.chartObjects.push(chart);
};

/**
 * Render vector maps
 *
 * @param mQuery element scope
 */
Mautic.renderMaps = function(scope) {
    var maps = [];

    if (mQuery.type(scope) === 'string') {
        maps = mQuery(scope).find('.vector-map');
    } else if (scope) {
        maps = scope.find('.vector-map');
    } else {
        maps = mQuery('.vector-map');
    }

    if (maps.length) {
        maps.each(function(index, element) {
            Mautic.renderMap(mQuery(element));
        });
    }
};

/**
 *
 * @param wrapper
 * @returns {*}
 */
Mautic.renderMap = function(wrapper) {
    // Map render causes a JS error on FF when the element is hidden
    if (wrapper.is(':visible')) {
        if (!Mautic.mapObjects) Mautic.mapObjects = [];
        var data = wrapper.data('map-data');
        if (typeof data === 'undefined' || !data.length) {
            try {
                data = mQuery.parseJSON(wrapper.text());
                wrapper.data('map-data', data);
            } catch (error) {

                return;
            }
        }

        // Markers have numerical indexes
        var firstKey = Object.keys(data)[0];

        // Check type of data
        if (firstKey == "0") {
            // Markers
            var markersData = data,
                regionsData = {};
        } else {
            // Regions
            var markersData = {},
                regionsData = data;
        }

        wrapper.text('');
        wrapper.vectorMap({
            backgroundColor: 'transparent',
            zoomOnScroll: false,
            markers: markersData,
            markerStyle: {
                initial: {
                    fill: '#40C7B5'
                },
                selected: {
                    fill: '#40C7B5'
                }
            },
            regionStyle: {
                initial: {
                    "fill": '#dce0e5',
                    "fill-opacity": 1,
                    "stroke": 'none',
                    "stroke-width": 0,
                    "stroke-opacity": 1
                },
                hover: {
                    "fill-opacity": 0.7,
                    "cursor": 'pointer'
                }
            },
            map: 'world_mill_en',
            series: {
                regions: [{
                    values: regionsData,
                    scale: ['#dce0e5', '#40C7B5'],
                    normalizeFunction: 'polynomial'
                }]
            },
            onRegionTipShow: function (event, label, index) {
                if (data[index] > 0) {
                    label.html(
                        '<b>'+label.html()+'</b></br>'+
                        data[index]+' Leads'
                    );
                }
            }
        });
        wrapper.addClass('map-rendered');
        Mautic.mapObjects.push(wrapper);
        return wrapper;
    }
};

/**
 * Destroy a jVector map
 */
Mautic.destroyMap = function(wrapper) {
    if (wrapper.hasClass('map-rendered')) {
        var map = wrapper.vectorMap('get', 'mapObject');
        map.removeAllMarkers();
        map.remove();
        wrapper.empty();
        wrapper.removeClass('map-rendered');
    }
};

/**
 * Initialize graph date range selectors
 */
Mautic.initDateRangePicker = function (fromId, toId) {
    var dateFrom = mQuery(fromId);
    var dateTo = mQuery(toId);

    if (dateFrom.length && dateTo.length) {
        dateFrom.datetimepicker({
            format: 'M j, Y',
            onShow: function (ct) {
                this.setOptions({
                    maxDate: dateTo.val() ? new Date(dateTo.val()) : false
                });
            },
            timepicker: false,
            scrollMonth: false,
            scrollInput: false
        });

        dateTo.datetimepicker({
            format: 'M j, Y',
            onShow: function (ct) {
                this.setOptions({
                    maxDate: new Date(),
                    minDate: dateFrom.val() ? new Date(dateFrom.val()) : false
                });
            },
            timepicker: false,
            scrollMonth: false,
            scrollInput: false
        });
    }
};

/**
 * Helper function to timeframe based graphs
 *
 * @param element
 * @param action
 * @param query
 * @param callback
 */
Mautic.getChartData = function(element, action, query, callback) {
    var element = mQuery(element);
    var wrapper = element.closest('ul');
    var button  = mQuery('#time-scopes .button-label');
    wrapper.find('a').removeClass('bg-primary');
    element.addClass('bg-primary');
    button.text(element.text());

    // Append action
    query = query + '&action=' + action;

    mQuery.ajax({
        showLoadingBar: true,
        url: mauticAjaxUrl,
        type: 'POST',
        data: query,
        dataType: "json",
        success: function (response) {
            if (response.success) {
                Mautic.stopPageLoadingBar();
                if (typeof callback == 'function') {
                    callback(response);
                } else if(typeof window["Mautic"][callback] !== 'undefined') {
                    window["Mautic"][callback].apply('window', [response]);
                }
            }
        },
        error: function (request, textStatus, errorThrown) {
            Mautic.processAjaxError(request, textStatus, errorThrown);
        }
    });
};

/**
 * Emulates empty data object if doughnut/pie chart data are empty.
 *
 *
 * @param data
 */
Mautic.emulateNoDataForPieChart = function (data) {
    var dataEmpty = true;
    mQuery.each(data, function (i, part) {
        if (part.value) {
            dataEmpty = false;
        }
    });
    if (dataEmpty) {
        data = [{
            value: 1,
            color: "#efeeec",
            highlight: "#EBEBEB",
            label: "No data"
        }];
    }
    return data;
};
