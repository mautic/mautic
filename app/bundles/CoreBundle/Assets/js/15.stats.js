Mautic.lazyLoadCountriesStats = () => {
    let containerId = '#countries-stats-container';
    let geoStatsId = '#countries-stats';
    let container = mQuery(containerId);
    let geoStats = mQuery(geoStatsId);

    // Load the table only if the container exists.
    if (!container.length && !geoStats.length) {
        return;
    }

    let tableUrl = container.data('target-url');
    mQuery.get(tableUrl, (response) => {
        response.target = geoStatsId;
        mQuery(geoStatsId).html(response);
    });
};
