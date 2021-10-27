'use strict';

Mautic.Marketplace = {
    /**
     * @param string vendorName The packagist vendor name
     * @param string packageName The packagist package name to install 
     */
    installPackage: (vendorName, packageName) => {
        console.log(vendorName);
        console.log(packageName);
        console.log('installing...');

        mQuery.ajax({
            showLoadingBar: true,
            url: mauticBaseUrl + 's/marketplace/testing',
            type: 'POST',
            data: JSON.stringify({
                vendor: vendorName,
                package: packageName
            }),
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    console.log('OK')
                } else if (response.redirect) {
                    window.location = response.redirect;
                }
            },
            error: function (request, textStatus, errorThrown) {
                Mautic.processAjaxError(request, textStatus, errorThrown);
                console.log('ERROR');
            }
        });
    },

}