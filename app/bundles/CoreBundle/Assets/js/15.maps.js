Mautic.getMaps = (scope) => {
    let maps;

    if (mQuery.type(scope) === 'string') {
        maps = mQuery(scope).find('.vector-map');
    } else if (scope) {
        maps = scope.find('.vector-map');
    } else {
        maps = mQuery('.vector-map');
    }

    return maps;
}

/**
 * Render vector maps
 *
 * @param scope
 */
Mautic.renderMaps = function(scope) {
    const maps = Mautic.getMaps(scope);

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
    //if (wrapper.is(':visible')) {
    if (!Mautic.mapObjects) Mautic.mapObjects = [];
    let data = wrapper.data('map-data');

    if (typeof data === 'undefined' || !data.length) {
        try {
            data = JSON.parse(wrapper.text());
            wrapper.data('map-data', data);
        } catch (error) {

            return;
        }
    }

    // Markers have numerical indexes
    const firstKey = Object.keys(data)[0];
    let markersData, regionsData;

    // Check type of data
    if (firstKey === "0") {
        // Markers
        markersData = data;
        regionsData = {};
    } else {
        // Regions
        markersData = {};
        regionsData = data;
    }

    wrapper.text('');
    wrapper.vectorMap({
        backgroundColor: 'transparent',
        zoomOnScroll: false,
        zoomAnimate: true,
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
                scale: ['#b9ebe4', '#40C7B5'],
                normalizeFunction: 'polynomial',
                legend: {
                    horizontal: true,
                    title: '<div data-map-legend="true"></div>',
                }
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

    wrapper.parent().trigger('map-rendered');

    return wrapper;
};

/**
 * Destroy a jVector map
 */
Mautic.destroyMap = function(wrapper) {
    if (wrapper.hasClass('map-rendered')) {
        const map = wrapper.vectorMap('get', 'mapObject');
        map.removeAllMarkers();
        map.remove();
        wrapper.empty();
        wrapper.removeClass('map-rendered');
    }
};

class MauticMap {

    static TYPES = {
        'markers': 0,
        'regions': 1,
    };

    static SETTINGS = {
        backgroundColor: 'transparent',
        zoomOnScroll: false,
        zoomAnimate: true,
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
                scale: ['#b9ebe4', '#40C7B5'],
                normalizeFunction: 'polynomial',
                legend: {
                    horizontal: true,
                    title: '<div data-map-legend="true"></div>',
                }
            }]
        },

    }

    constructor(wrapper, typeKey = 'regions' ) {
        this.type = MauticMap.TYPES[typeKey];
        this.scope = mQuery(wrapper);
        this.mapData = this.getMapData();
        this.settings = MauticMap.SETTINGS;
        this.settings.onRegionTipShow = (event, label, index) => {
            if (this.mapData[index] > 0) {
                 label.html(
                     '<b>'+label.html()+'</b></br>'+
                     this.mapData[index]+' Leads'
                 );
             }
        }
        this.map = this.getMapsInScope();
        this.mapOptions = this.scope.find('[data-map-option]');
    }

    init() {
        this.initSeries(this.mapData);
        this.initMap();
    }

    initSeries(data) {
        if (this.type === MauticMap.TYPES['regions']) {
            this.settings.series.regions[0].values = data;
            this.settings.markers = {};
        }

        if(this.type === MauticMap.TYPES['markers']) {
            this.settings.series.regions[0].values = {};
            this.settings.markers = data;
        }
    }

    getMapsInScope() {
        return this.scope.find('.vector-map');
    }

    /**
     * Render vector maps
     **/
    renderMaps() {
        const maps = this.getMapsInScope();

        if (maps.length) {
            maps.each((index, element) => {
                this.renderMap(mQuery(element));
            });
        }
    }

    getMapData() {
        const map = this.getMapsInScope();
        let data = map.data('map-data');

        if (typeof data === 'undefined' || !data.length) {
            try {
                data = JSON.parse(map.text());
                map.data('map-data', data)
                map.attr('data-map-data', JSON.stringify(data));
            } catch (error) {
                return {};
            }
        }

        return data;
    }

    /**
     *
     * @returns {*}
     */
    renderMap() {
        // Map render causes a JS error on FF when the element is hidden
        //if (wrapper.is(':visible')) {
        if (!Mautic.mapObjects) Mautic.mapObjects = [];

        this.map.text('');
        this.map.vectorMap(this.settings);
        mQuery(this.map).addClass('map-rendered');

        Mautic.mapObjects.push(this.scope);
        this.scope.parent().trigger("map-rendered");

        return this.scope;
    };

    /**
     * Destroy a jVector map
     */
    destroyMap() {
        if (this.map) {
            const mapObj = this.scope.vectorMap('get', 'mapObject');
            mapObj.removeAllMarkers();
            mapObj.remove();
            this.scope.empty();
            this.scope.removeClass('map-rendered');
        }
    };

    addMapOptionsListener() {
        if (this.mapOptions.length) {
            mQuery(this.mapOptions).on('click', (event) => {
                const currentOption = mQuery(event.currentTarget);
                const newValues = currentOption.data('map-series');
                const legendText = currentOption.data('legend-text');

                this.setMapValues(newValues);
                this.setActiveOption(currentOption);
                this.setLegend(legendText);
            });

            const legendText = mQuery(this.mapOptions[0]).data('legend-text');
            this.setLegend(legendText);
        }
    }

    setActiveOption(option) {
        this.mapOptions.removeClass('active');
        option.addClass('active');
    }

    setLegend(legendText) {
        const mapLegend = this.scope.find('[data-map-legend]');
        mQuery(mapLegend).text(legendText);
    }

    setMapValues(values) {
        const mapObject = this.map.vectorMap('get', 'mapObject');

        this.mapData = values;
        mapObject.reset();
        mapObject.series.regions[0].setValues(values);
    }

    initMap() {
        if (this.scope.length) {
            // Check if map is not already rendered
            if (this.scope.children('.map-rendered').length) {
                return;
            }

            // Loaded via AJAX not to block loading a whole page
            const mapUrl = this.scope.attr('data-map-url');

            this.renderMaps(this.scope);
            this.addMapOptionsListener();
        }
    }
}

Mautic.initMap = (wrapper, typeKey) => {
    const map = new MauticMap(wrapper, typeKey);
    map.init();

    return map;
}