// Email preview URL builder

Mautic.emailPreview = {

    urlBase : 'email/preview',
    urlParams : {},

    init : function() {
        // Activate contact chosen
//        Mautic.activateContactLookupField();
    },

    addUrlParameter  : function(parameterName, value) {

        if (value === undefined || value.length === 0) {
            if (this.urlParams.hasOwnProperty(parameterName)) {
                delete this.urlParams[parameterName];
            }
            return;
        }

        this.urlParams[parameterName] = value;
    },

    regenerateUrl : function(emailId) {
        this.addUrlParameter(
            'translationId',
            mQuery('#email_preview_settings_translation').val()
        );

        this.addUrlParameter(
            'variantId',
            mQuery('#email_preview_settings_variant').val()
        );

        this.addUrlParameter(
            'contactId',
            mQuery('#email_preview_settings_contact').val()
        );

        let previewUrl = mauticBaseUrl + this.urlBase + '/' + emailId;
        if (Object.keys(this.urlParams).length > 0) {
            previewUrl = previewUrl + '?' + new URLSearchParams(this.urlParams);
        }

        console.log(this.urlParams.length);

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
},

mQuery(document).ready(function() {
    Mautic.emailPreview.init();
});