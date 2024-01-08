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
            }]
        },
        onRegionOver: () => {
            document.body.style.cursor = 'pointer';
        },
        onRegionOut: () =>
        {
            document.body.style.cursor = 'default';
        }
    }

    constructor(wrapper, typeKey = 'regions' ) {
        this.type = MauticMap.TYPES[typeKey];
        this.scope = mQuery(wrapper);
        this.mapData = this.getMapData();
        this.settings = MauticMap.SETTINGS;
        this.map = this.getMapsInScope();
        this.legendEnabled = this.isLegendEnabled();
        this.statUnit = this.getStatUnitFromItem(this.map);

        this.settings.onRegionTipShow = (event, label, index) => {
            if (this.mapData) {
                const value = this.mapData[index];

                if (value > 0) {
                    const tooltip = `<b>${label.html()}</b></br>${value} ${this.statUnit}${(value > 1) ? 's' : ''}`;
                    label.html(tooltip);
                }
            }
        }

        if (this.legendEnabled) {
            this.settings.series.regions[0].legend = {
                horizontal: true,
                title: '<div data-map-legend="true"></div>',
            }
        }

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

    isLegendEnabled() {
        return this.map.data('legend-enabled');
    }

    getMapsInScope() {
        return this.scope.find('.vector-map');
    }

    getStatUnitFromItem(item) {
        return mQuery(item).data('stat-unit');
    }

    setStatUnit(value) {
        this.statUnit = value;
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

    renderMap() {
        // Map render causes a JS error on FF when the element is hidden
        if (this.scope.is(':visible')) {
            this.map.text('');
            this.map.vectorMap(this.settings);
            mQuery(this.map).addClass('map-rendered');
        }
    };

    /**
     * Destroy a jVector map
     */
    destroyMap() {
        if (this.map.length) {
            const mapObj = this.map.vectorMap('get', 'mapObject');

            if (mapObj) {
                mapObj.removeAllMarkers();
                mapObj.remove();
                this.map.empty();
                this.map.removeClass('map-rendered');
            }
        }
    };

    /**
     * Get legend content from option
     */
    getOptionLegendText(option) {
        return option.data('legend-text');
    }

    /**
     * Add click listeners to map option buttons
     */
    addMapOptionsListener() {
        if (this.mapOptions.length) {
            mQuery(this.mapOptions).on('click', (event) => {
                const currentOption = mQuery(event.currentTarget);
                const newValues = currentOption.data('map-series');
                const legendText = this.getOptionLegendText(currentOption);
                const statUnit = this.getStatUnitFromItem(currentOption);

                this.setMapValues(newValues);
                this.setActiveOption(currentOption);

                if (this.legendEnabled) {
                    this.setLegend(legendText);
                }

                if (statUnit) {
                    this.setStatUnit(statUnit);
                }
            });

            if (this.legendEnabled) {
                const defaultOption = mQuery(this.mapOptions[0]);
                const legendText = this.getOptionLegendText(defaultOption);
                this.setLegend(legendText);
            }
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
        const dataSeries = mapObject.series.regions[0];
        this.mapData = values;
        mapObject.reset();

        if (this.type === MauticMap.TYPES['regions'] && dataSeries) {
            // Force map color scaling
            this.unsetExtremeValues(dataSeries);
            dataSeries.setValues(values);
        } else if (this.type === MauticMap.TYPES['markers']) {
            this.settings.markers[0].setValues(values)
        }
    }

    unsetExtremeValues(dataSeries) {
        dataSeries.params.min = undefined;
        dataSeries.params.max = undefined;
    }

    initMap() {
        if (this.scope.length) {
            // Check if map is not already rendered
            if (this.scope.children('.map-rendered').length) {
                return;
            }

            const map = this.getMapsInScope()

            this.renderMap(map);
            this.addMapOptionsListener();
        }
    }
}

Mautic.initMap = (wrapper, typeKey) => {
    const map = new MauticMap(wrapper, typeKey);
    map.init();

    if (!Mautic.mapObjects) Mautic.mapObjects = [];
    Mautic.mapObjects.push(map);

    return map;
}