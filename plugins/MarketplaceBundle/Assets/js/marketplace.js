Marketplace = {
    startInstall: function(elHtml, package) {
        mQuery(elHtml).attr('disable', true);
        mQuery.ajax({
            type: 'GET',
            url: mauticBaseUrl+'s/marketplace/install/'+package+'/step/composer',
            showLoadingBar: true,
            success: function() {
                console.log('success');
            }
        });
    }
};
