Mautic.getMaps = (scope) => {
    let maps = [];

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
 * @param mQuery element scope
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
    var data = wrapper.data('map-data');
    if (typeof data === 'undefined' || !data.length) {
        try {
            data = JSON.parse(wrapper.text());
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

    wrapper.parent().trigger("map-rendered");

    return wrapper;
    //}
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

Mautic.setUpMapOptions = (scope) => {
    const mapOptions = scope.find('[data-map-option]');

    if (mapOptions.length) {
        mQuery(mapOptions).on('click', (event) => {
            const currentOption = mQuery(event.currentTarget);
            const newValues = currentOption.data('map-data');
            const map = Mautic.getMaps(scope);

            Mautic.setNewMapValues(map, newValues);
            mapOptions.removeClass('active');
            currentOption.addClass('active');
        });
    }
}


Mautic.setNewMapValues = (map, values) => {
    const mapObject = map.vectorMap('get', 'mapObject');

    mapObject.reset();
    mapObject.series.regions[0].setValues(values.data);
}

Mautic.loadMap = (event) => {
    const scopeId = event.target.getAttribute('href');
    const scope = mQuery(scopeId);

    if (scope.length) {
        // Check if map is not already rendered
        if (scope.children('.map-rendered').length) {
            return;
        }

        // Loaded via AJAX not to block loading a whole page
        const mapUrl = scope.attr('data-map-url');

        scope.load(mapUrl, '', () => {
            Mautic.renderMaps(scope);
            Mautic.setUpMapOptions(scope);
        });
    }
}