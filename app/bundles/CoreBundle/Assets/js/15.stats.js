Mautic.lazyLoadCountriesStats = () => {
    let containerId = '#countries-stats';
    let container = mQuery(containerId);

    // Load the table only if the container exists.
    if (!container.length) {
        return;
    }

    let tableUrl = container.data('target-url');
    mQuery.get(tableUrl, (response) => {
        response.target = containerId;
        mQuery(containerId).html(response);
    });
};
