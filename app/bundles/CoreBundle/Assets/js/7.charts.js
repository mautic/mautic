//set global Chart defaults
if (typeof Chart != 'undefined') {
    // configure global Chart options
    Chart.defaults.elements.line.borderWidth = 2;
    Chart.defaults.elements.line.fill = true;
    Chart.defaults.elements.line.tension = 0.4;
    Chart.defaults.elements.point.radius = 0;
    Chart.defaults.elements.point.hoverRadius = 6;
    Chart.defaults.elements.point.hitRadius = 20;
    Chart.defaults.plugins.legend.labels.boxWidth = 12;
    Chart.defaults.plugins.legend.labels.usePointStyle = true;
    Chart.defaults.plugins.legend.labels.pointStyle = 'circle';
    Chart.defaults.maintainAspectRatio = false;
    Chart.defaults.scale.ticks.padding = 10;
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
                } else if (canvas.hasClass('hour-chart')) {
                    Mautic.renderHourChart(canvas)
                }
            }
            canvas.addClass('chart-rendered');
        });
    }
};

Mautic.chartTickLabel = function(scale, value) {
    return scale.getLabelForValue(value);
};

Mautic.chartYAxisTicks = function(scale) {
    scale.ticks = [scale.min, (scale.max - scale.min) / 2, scale.max].map(function(value) {
        return {value};
    });
};

Mautic.chartYAxisTickLabel = function(value, index, values) {
    if (index === 0 || index === values.length - 1) {
        return value;
    }
    if (/^\d+\.5$/.test(value.toString())) {
        return '';
    }
    if (index === Math.floor(values.length / 2)) {
        return value !== 0.5 ? value : '';
    }
    return '';
};

/**
 * Render the chart.js line chart
 *
 * @param mQuery element canvas
 */
Mautic.renderLineChart = function(canvas) {
    var data = JSON.parse(canvas.text());
    if (!data.labels.length || !data.datasets.length) return;
    var chart = new Chart(canvas, {
        type: 'line',
        data: data,
        options: {
            interaction: {
                mode: 'index',
                intersect: false
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        maxRotation: 0,
                        callback: function(value, index, values) {
                            if (index === 0 || index === values.length - 1) {
                                return Mautic.chartTickLabel(this, value);
                            }
                            return '';
                        }
                    }
                },
                y: {
                    beginAtZero: true,
                    afterBuildTicks: Mautic.chartYAxisTicks,
                    border: {
                        display: false
                    },
                    ticks: {
                        callback: Mautic.chartYAxisTickLabel
                    }
                }
            }
        }
    });
    Mautic.chartObjects.push(chart);
};

Mautic.renderHourChart = function(canvas) {
    const data = JSON.parse(canvas.text());
    const chart = new Chart(canvas, {
        type: 'line',
        data,
        options: {
            interaction: {mode: 'index', intersect: false},
            scales: {
                x: {
                    grid: {display: false},
                    ticks: {
                        autoSkip: true,
                        maxTicksLimit: 6,
                        maxRotation: 0,
                        callback: function(value) {
                            return Mautic.chartTickLabel(this, value).split(' - ')[0];
                        }
                    }
                },
                y: {
                    beginAtZero: true,
                    afterBuildTicks: Mautic.chartYAxisTicks,
                    border: {display: false},
                    ticks: {
                        callback: Mautic.chartYAxisTickLabel
                    }
                }
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
    var data = JSON.parse(canvas.text());
    var options = {};
    var disableLegend = canvas.attr('data-disable-legend');
    if (typeof disableLegend !== 'undefined' && disableLegend !== false) {
        options.plugins = {legend: {display: false}};
    }
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
    var data = JSON.parse(canvas.text());
    var chart = new Chart(canvas, {
        type: 'bar',
        data: data,
        options: {
            interaction: {
                mode: 'index',
                intersect: true
            },
            datasets: {
                bar: {barPercentage: 0.9}
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
    var data = JSON.parse(canvas.text());
    var chart = new Chart(canvas, {
        type: 'bar',
        data: data,
        options: {
            interaction: {
                mode: 'index',
                intersect: true
            },
            datasets: {
                bar: {barThickness: barWidth}
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
    var data = JSON.parse(canvas.text());
    var chart = new Chart(canvas, {
        type: 'bar',
        data: data,
        options: {
            scales: {
                x: {
                    stacked: false,
                    ticks: {font: {size: 9}},
                    grid: {display: false}
                },
                y: {
                    display: false,
                    stacked: false,
                    beginAtZero: true,
                    ticks: {display: false},
                    grid: {display: false}
                }
            },
            plugins: {
                legend: {display: false}
            }
        }
    });
    Mautic.chartObjects.push(chart);
};

/**
 * Render the chart.js horizontal bar chart
 *
 * @param mQuery element canvas
 */
Mautic.renderHorizontalBarChart = function(canvas) {
    var data = JSON.parse(canvas.text());
    var chart = new Chart(canvas, {
        type: 'bar',
        data: data,
        options: {
            indexAxis: 'y',
            datasets: {
                bar: {barPercentage: 0.5}
            },
            scales: {
                x: {
                    display: true,
                    stacked: false,
                    beginAtZero: true,
                    grid: {display: false},
                    ticks: {font: {size: 8}, stepSize: 5}
                },
                y: {
                    stacked: false,
                    grid: {display: false},
                    ticks: {font: {size: 9}}
                }
            },
            plugins: {
                legend: {display: false},
                tooltip: {
                    bodyFont: {size: 9},
                    bodySpacing: 0,
                    callbacks: {
                        title: function() {
                            return '';
                        },
                        label: function(context) {
                            return context.parsed.x + ': ' + context.label;
                        }
                    }
                }
            }
        }
    });
    Mautic.chartObjects.push(chart);
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

Mautic.setDateRange = (option) => {
  const today = new Date();
  const dayInMilliseconds = 24 * 60 * 60 * 1000;
  let fromDate;
  let toDate;

  switch (option) {
    case 'today':
      fromDate = today;
      toDate = today;
      break;
    case 'yesterday':
      fromDate = new Date(today.getTime() - dayInMilliseconds);
      toDate = fromDate;
      break;
    default:
      if (typeof option !== 'number') {
        console.error('Invalid date range option.');

        return;
      }

      fromDate = new Date(today.getTime() - (option * dayInMilliseconds));
      toDate = today;
  }

  const dateFromInput = document.getElementById('daterange_date_from');
  const dateToInput = document.getElementById('daterange_date_to');
  const applyButton = document.getElementById('daterange_apply');

  if (!dateFromInput || !dateToInput || !applyButton) {
    console.error('Date range inputs are missing.');

    return;
  }

  dateFromInput.value = Mautic.formatDate(fromDate);
  dateToInput.value = Mautic.formatDate(toDate);
  applyButton.click();
};

Mautic.formatDate = (date) => {
  const monthNames = [
    'Jan',
    'Feb',
    'Mar',
    'Apr',
    'May',
    'Jun',
    'Jul',
    'Aug',
    'Sep',
    'Oct',
    'Nov',
    'Dec'
  ];

  return `${monthNames[date.getMonth()]} ${date.getDate()}, ${date.getFullYear()}`;
};

document.addEventListener('click', (event) => {
  if (!(event.target instanceof Element)) {
    return;
  }

  const dateRangeTrigger = event.target.closest('[data-date-range-option]');

  if (!dateRangeTrigger) {
    return;
  }

  event.preventDefault();

  const option = dateRangeTrigger.dataset.dateRangeOption;
  const dateRangeOption = /^\d+$/.test(option) ? Number(option) : option;

  Mautic.setDateRange(dateRangeOption);
});

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
