/**
 * Email preview page URL builder.
 */
Mautic.contentPreviewUrlModifier = {

    regenerateDownloadPreviewUrl : function(newValue, entity) {
        let url = window.location.href;
        url = url.split('?')[0];

        const urlParams = new URLSearchParams(window.location.search);
        const parameters = {
            contactId: entity === 'contact' ? newValue : urlParams.get('contactId'),
            companyId: entity === 'company' ? newValue : urlParams.get('companyId')
        };

        mQuery.each( parameters, function( key, value ) {
            if (!value) {
                delete parameters[key];
            }
        });

        if (!mQuery.isEmptyObject(parameters)) {
            url += '?' + mQuery.param(parameters);
        }
        window.location.href = url;
    }
}

Mautic.updateContactPreviewLookupListFilter = function(field, item) {
     Mautic.updatePreviewLookupListFilter(field, item, 'contact');
};

Mautic.updateCompanyPreviewLookupListFilter = function(field, item) {
    Mautic.updatePreviewLookupListFilter(field, item, 'company');
};

/**
 * Used in data-lookup-callback attr of form field in EmailPreviewOptionsType.
 */
Mautic.updatePreviewLookupListFilter = function(field, item, obj) {
    if (item && item.id) {
        mQuery(field).val(item.value);
        Mautic.contentPreviewUrlModifier.regenerateDownloadPreviewUrl(
            item.id,
            obj
        );
    }
};

Mautic.activateContactPreviewLookupField = function (filterId) {
    Mautic.activatePreviewLookupField(filterId, 'email_preview_options_contact', 'contact', 'lead.lead');
};

Mautic.activateCompanyPreviewLookupField = function (filterId) {
    Mautic.activatePreviewLookupField(filterId, 'email_preview_options_company', 'company', 'lead.company');
};

/**
 * Used in data-lookup-callback attr of form field in EmailPreviewOptionsType.
 * Take a look at https://github.com/twitter/typeahead.js/
 */
Mautic.activatePreviewLookupField = function (filterId, lookupElementId, obj, searchKey) {
    const action = mQuery('#' + lookupElementId).attr('data-chosen-lookup');

    const options = {
        limit: 20,
        'searchKey': searchKey,
    };

    Mautic.activateFieldTypeahead(lookupElementId, filterId, options, action);

    mQuery('#' + lookupElementId).on('change', function (event) {
        if (event.target.value === '') {
            // Delete selected company ID from URL and hidden input
            Mautic.contentPreviewUrlModifier.regenerateDownloadPreviewUrl('', obj);
        }
    });
};
