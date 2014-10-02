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
        content: '%x : %y'
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
        content: '%x : %y'
      },
      xaxis: {
        tickColor: '#fcfcfc',
        mode: 'categories'
      },
      yaxis: {
        tickColor: '#eee'
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
      
      // init flot
      if(jsonData === "") { return; }
      $.plot($(this.element), $.parseJSON(jsonData), this.settings.areaChartOpt);
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

  // instatiate the plugin
  $('.flotchart').flotChart();

})(jQuery, window, document);