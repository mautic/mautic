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

        const value = element.val()

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

        const element = mQuery(elementId);

        const hasOption = mQuery(elementId +  ' option[value="' + value + '"]');

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


    regenerateUrl : function(newValue, changedElement) {

        this.urlBase = mQuery("#content_preview_url").attr('data-route');

        changedElement  = mQuery(changedElement);
        const elementId = changedElement.attr('id');

        const value = this.getElementValue(changedElement);

        if (elementId === 'content_preview_settings_variant') {
            this.setElementValue('#content_preview_settings_translation', value);
        }

        if (elementId === 'content_preview_settings_translation') {
            this.setElementValue('#content_preview_settings_variant', value);
        }

        if (elementId === 'content_preview_settings_contact_id') {
            if (newValue === '') {
                this.contactId = false;
            } else {
                this.contactId = value;
            }
            newValue = this.lastUsedObjectId;
        } else if (value !== false) {
            this.lastUsedObjectId = newValue = value;
        }

        let previewUrl = mauticBaseUrl + this.urlBase + '/' + newValue;

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
Mautic.updatePreviewContactLookupListFilter = function(field, item) {
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
Mautic.activatePreviewContactLookupField = function(fieldOptions, filterId) {

    const lookupElementId = 'content_preview_settings_contact';
    const action          = mQuery('#'+ lookupElementId).attr('data-chosen-lookup');

    const options = {
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
