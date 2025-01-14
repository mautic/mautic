'use strict';

Mautic.Marketplace = {
    /**
     * @param string vendorName The packagist vendor name
     * @param string packageName The packagist package name to install
     * @param callback successCallback Callback to be executed on success (jQuery success object)
     * @param callback errorCallback Callback to be executed on error (jQuery error object)
     */
    installPackage: (vendorName, packageName, successCallback, errorCallback) => {
        mQuery.ajax({
            showLoadingBar: true,
            url: mauticAjaxUrl + `?action=marketplace:installPackage`,
            type: 'POST',
            data: JSON.stringify({
                vendor: vendorName,
                package: packageName
            }),
            dataType: 'json',
            success: successCallback,
            error: errorCallback
        });
    },

    /**
     * @param string vendorName The packagist vendor name
     * @param string packageName The packagist package name to remove
     * @param callback successCallback Callback to be executed on success (jQuery success object)
     * @param callback errorCallback Callback to be executed on error (jQuery error object)
     */
    removePackage: (vendorName, packageName, successCallback, errorCallback) => {
        mQuery.ajax({
            showLoadingBar: true,
            url: mauticAjaxUrl + `?action=marketplace:removePackage`,
            type: 'POST',
            data: JSON.stringify({
                vendor: vendorName,
                package: packageName
            }),
            dataType: 'json',
            success: successCallback,
            error: errorCallback
        });
    },
}