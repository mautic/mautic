/*
 *  jQuery Flot sample
 */
;(function($, window, document, undefined) {

  var pluginName = 'flotChart';
  var defaults = {
    // Area chart option
    areaChartOpt: {
      series: {
        lines: {
          show: false
        },
        splines: {
          show: true,
          tension: 0.5,
          lineWidth: 2,
          fill: 0.8
        },
        points: {
          show: true,
          radius: 4
        }
      },
      grid: {
        borderColor: '#ebedf0',
        borderWidth: 1,
        hoverable: true,
        backgroundColor: '#fbfbfb'
      },
      tooltip: true,
      tooltipOpts: {
        content: '%y'
      },
      xaxis: {
        tickColor: 'transparent',
        mode: 'categories'
      },
      yaxis: {
        tickColor: '#ebedf0',
        tickFormatter: function(v) {
          return v;
        }
      },
      shadowSize: 0
    },

    // Bar chart option
    barChartOpt: {
      series: {
        bars: {
          align: 'center',
          lineWidth: 0,
          show: true,
          barWidth: 0.6,
          fill: 0.9
        }
      },
      grid: {
        borderColor: '#eee',
        borderWidth: 1,
        hoverable: true,
        backgroundColor: '#fcfcfc'
      },
      tooltip: true,
      tooltipOpts: {
        content: '%y'
      },
      xaxis: {
        tickColor: '#fcfcfc',
        mode: 'categories'
      },
      yaxis: {
        tickColor: '#eee'
      },
      shadowSize: 0
    },

    // Donut chart option
    donutChartOpt: {
      series: {
        pie: {
          innerRadius: 0.5,
          show: true,
          label: {
            show: true,
            radius: 0
          }
        }
      },
      legend: {
        show: false
      }
    },

    // Line chart option
    lineChartOpt: {
      series: {
        lines: { show: false },
        splines: {
          show: true,
          tension: 0.4,
          lineWidth: 2,
          fill: 0
        },
        points: {
          show: true,
          radius: 4
        }
      },
      grid: {
        borderColor: '#eee',
        borderWidth: 1,
        hoverable: true,
        backgroundColor: '#fcfcfc'
      },
      tooltip: true,
      tooltipOpts: {
        content: '%y'
      },
      xaxis: {
        show: false,
        tickColor: 'transparent',
        mode: 'categories'
      },
      yaxis: {
        tickColor: '#ebedf0'
      },
      shadowSize: 0
    }
  };

  function Plugin(element, options) {
    this.element = element;
    this.settings = $.extend({}, defaults, options);
    this._defaults = defaults;
    this._name = pluginName;
    this.init();
  }

  $.extend(Plugin.prototype, {
    init: function() {
      var jsonData = $(this.element).children('.flotdata').text();
      var chartType = $(this.element).data('type');
      var flotOption = '';

      // flot chart option
      if(chartType === 'area') {
        flotOption = this.settings.areaChartOpt;
      } else if(chartType === 'bar') {
        flotOption = this.settings.barChartOpt;
      } else if(chartType === 'donut') {
        flotOption = this.settings.donutChartOpt;
      } else if(chartType === 'line') {
        flotOption = this.settings.lineChartOpt;
      }
      
      // init flot
      if(jsonData === '') { return; }
      $.plot($(this.element), $.parseJSON(jsonData), flotOption);
    }
  });

  $.fn[pluginName] = function(options) {
    this.each(function() {
      if (!$.data(this, 'plugin_' + pluginName)) {
        $.data(this, 'plugin_' + pluginName, new Plugin(this, options));
      }
    });
    return this;
  };
})(jQuery, window, document);