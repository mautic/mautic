/**
 * Email & page preview URL builder
 */
Mautic.contentPreviewUrlGenerator = {

    urlBase : 'email/preview',
    urlParams : {},

    addUrlParameter  : function(value, parameterName = 'emailId') {

        if (value === undefined || value.length === 0) {
            // Unset value if needed
            if (this.urlParams.hasOwnProperty(parameterName)) {
                delete this.urlParams[parameterName];
            }
            return;
        }

        this.urlParams[parameterName] = value;
    },

    regenerateUrl : function(emailId) {

        this.addUrlParameter(
            mQuery('#email_preview_settings_translation').val(),
        );

        this.addUrlParameter(
            mQuery('#email_preview_settings_variant').val(),
        );

        this.addUrlParameter(
            mQuery('#email_preview_settings_contact').val(),
            'contactId'
        );

        if (this.urlParams.hasOwnProperty('emailId')) {
            emailId = this.urlParams.emailId;
        }

        let previewUrl = mauticBaseUrl + this.urlBase + '/' + emailId;
        if (this.urlParams.hasOwnProperty('contactId')) {
            previewUrl = previewUrl + '?contactId=' + this.urlParams.contactId;
        }

        // Update url in preview input
        mQuery('#email_preview_url').val(previewUrl);
        // Update URL in preview button
        mQuery('#email_preview_url_button').attr('onClick', "window.open('" + previewUrl + "', '_blank');");
    }
}

Mautic.activateContactLookupField = function(fieldOptions, filterId) {

    let lookupElementId = 'email_preview_settings_contact';
    let action = mQuery('#'+ lookupElementId).attr('data-chosen-lookup');

    let options = {
        limit: 20,
        'searchKey': 'lead.lead',
    };

    Mautic.activateFieldTypeahead(lookupElementId, filterId, options, action);
};
