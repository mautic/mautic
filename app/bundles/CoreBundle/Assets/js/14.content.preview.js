/**
 * Email & page preview URL builder
 */
Mautic.contentPreviewUrlGenerator = {

    urlBase : 'email/preview',
    lastUsedObjectId : false,
    contactId: false,

    init() {
        this.lastUsedObjectId = mQuery('#content_preview_settings_object_id').val();
    },

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

        this.urlBase = mQuery("#content_preview_url").attr('data-route');

        changedElement = mQuery(changedElement);
        let elementId  = changedElement.attr('id');

        let value = this.getElementValue(changedElement);

        if (elementId === 'content_preview_settings_variant') {
            this.setElementValue('#content_preview_settings_translation', value);
        }

        if (elementId === 'content_preview_settings_translation') {
            this.setElementValue('#content_preview_settings_variant', value);
        }

        if (elementId === 'content_preview_settings_contact_id') {
            this.contactId = value;
            emailId = this.lastUsedObjectId;
        } else if (value !== false) {
            this.lastUsedObjectId = emailId = value;
        }

        let previewUrl = mauticBaseUrl + this.urlBase + '/' + emailId;

        if (this.contactId !== false) {
            previewUrl = previewUrl + '?contactId=' + this.contactId;
        }

        // Update url in preview input
        mQuery('#content_preview_url').val(previewUrl);
        // Update URL in preview button
        mQuery('#content_preview_url_button').attr('onClick', "window.open('" + previewUrl + "', '_blank');");
    }
}

/**
 * Used in data-lookup-callback attr of form field in ContentPreviewSettingsType
 */
Mautic.updateContactLookupListFilter = function(field, item) {
    if (item && item.id) {
        mQuery('#content_preview_settings_contact_id').val(item.id);
        mQuery(field).val(item.value);
        Mautic.contentPreviewUrlGenerator.regenerateUrl(
            item.id,
            mQuery('#content_preview_settings_contact_id')
        );
    }
};

/**
 * Used in data-lookup-callback attr of form field in ContentPreviewSettingsType
 * Take a look at https://github.com/twitter/typeahead.js/
 */
Mautic.activateContactLookupField = function(fieldOptions, filterId) {

    let lookupElementId = 'content_preview_settings_contact';
    let action = mQuery('#'+ lookupElementId).attr('data-chosen-lookup');

    let options = {
        limit: 20,
        'searchKey': 'lead.lead',
    };

    Mautic.activateFieldTypeahead(lookupElementId, filterId, options, action);
    Mautic.contentPreviewUrlGenerator.init();

    mQuery('#content_preview_settings_contact').on("change",function(event) {
        if (event.target.value === '') {
            // Delete selected contact ID from URL and hidden input
            Mautic.contentPreviewUrlGenerator.regenerateUrl('', mQuery('#content_preview_settings_contact_id'));
        }
    });

};
