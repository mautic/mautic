/**
 * Email & page preview URL builder
 */
Mautic.contentPreviewUrlGenerator = {

    urlBase : 'email/preview',
    lastUsedEmailId : false,

    /**
     * @param element mQuery representation
     * @returns {boolean|string}
     */
    getElementValue(element) {

        let value = element.val()

        if (value === undefined || value.length === 0) {
            return false;
        }

        return value;
    },

    /**
     * @param {string} elementId
     * @param {string} value
     * @returns {boolean|string}
     */
    setElementValue(elementId, value) {

        let element = mQuery(elementId);

        let hasOption = mQuery(elementId +  ' option[value="' + value + '"]');

        if (hasOption.length > 0) {
            // This value exists in other chosen element
            element.val(value);
        } else {
            // Value does not exists
            element.val("");
        }

        // Update chosen UI
        mQuery(element).trigger('chosen:updated');
    },


    regenerateUrl : function(emailId, changedElement) {

        changedElement = mQuery(changedElement);
        let elementId  = changedElement.attr('id');
        let contactId  = false;

        let value = this.getElementValue(changedElement);

        if (elementId === 'content_preview_settings_variant') {
            this.setElementValue('#content_preview_settings_translation', value);
        }

        if (elementId === 'content_preview_settings_translation') {
            this.setElementValue('#content_preview_settings_variant', value);
        }

        if (elementId === 'content_preview_settings_contact') {
            contactId = value;
            emailId = this.lastUsedEmailId;
        } else if (value !== false) {
            this.lastUsedEmailId = emailId = value;
        }

        let previewUrl = mauticBaseUrl + this.urlBase + '/' + emailId;

        if (contactId !== false) {
            previewUrl = previewUrl + '?contactId=' + contactId;
        }

        // Update url in preview input
        mQuery('#content_preview_url').val(previewUrl);
        // Update URL in preview button
        mQuery('#content_preview_url_button').attr('onClick', "window.open('" + previewUrl + "', '_blank');");
    }
}

Mautic.activateContactLookupField = function(fieldOptions, filterId) {

    let lookupElementId = 'content_preview_settings_contact';
    let action = mQuery('#'+ lookupElementId).attr('data-chosen-lookup');

    let options = {
        limit: 20,
        'searchKey': 'lead.lead',
    };

    Mautic.activateFieldTypeahead(lookupElementId, filterId, options, action);
};
