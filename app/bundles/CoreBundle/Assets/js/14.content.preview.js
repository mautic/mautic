/**
 * Email & page preview URL builder
 */
Mautic.contentPreviewUrlGenerator = {

    urlBase : 'email/download/preview',
    urlBaseFinal : 'email/preview',
    lastUsedObjectId : false,
    contactId: false,
    companyId: false,
    previewFrameBaseWidth : 640,
    previewFrameMinZoom : 0.35,
    previewFrameMaxZoom : 0.75,

    init() {
        this.lastUsedObjectId = mQuery('#content_preview_settings_object_id').val();
        this.resizePreviewFrame();
        mQuery(window)
            .off('resize.contentPreviewFrame')
            .on('resize.contentPreviewFrame', () => {
                this.resizePreviewFrame();
            });
    },

    resizePreviewFrame() {
        const previewFrame = mQuery('#content_preview_frame');

        if (!previewFrame.length) {
            return;
        }

        const previewWidth = previewFrame.parent().width();

        if (!previewWidth) {
            return;
        }

        const zoom = Math.min(
            this.previewFrameMaxZoom,
            Math.max(this.previewFrameMinZoom, previewWidth / this.previewFrameBaseWidth)
        );

        previewFrame.css('zoom', zoom);
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
        } else if (value !== false) {
            this.lastUsedObjectId = value;
        }

        if (elementId === 'content_preview_settings_company_id') {
            if (newValue === '') {
                this.companyId = false;
            } else {
                this.companyId = value;
            }
        } else if (value !== false) {
            this.lastUsedObjectId = value;
        }

        const mauticBaseUrl = window.location.origin;
        const emailId       = window.location.pathname.split('/').pop();
        let previewUrl = mauticBaseUrl + '/' + this.urlBase + '/' + emailId;
        let draftUrl  = previewUrl + '/draft';

        const parameters = {
            contactId: this.contactId,
            companyId: this.companyId
        };

        mQuery.each( parameters, function( key, value ) {
            if (!value) {
                delete parameters[key];
            }
        });

        if (!mQuery.isEmptyObject(parameters)) {
            previewUrl += '?' + mQuery.param(parameters);
            draftUrl   += '?' + mQuery.param(parameters);
        }

        // Update url in preview input
        mQuery('#content_preview_url').val(previewUrl);
        // Update URL in preview button
        mQuery('#content_preview_url_button').attr('onClick', "window.open('" + previewUrl + "', '_blank');");
        const previewFrame = mQuery('#content_preview_frame');
        if (previewFrame.length) {
            previewFrame.attr('src', previewUrl.replace(this.urlBase, this.urlBaseFinal));
            this.resizePreviewFrame();
        }

        if (mQuery('#content_draft_preview_url').length > 0)
        {
            mQuery('#content_draft_preview_url').val(draftUrl);
            mQuery('#content_draft_preview_url_button').attr('onClick', "window.open('" + draftUrl + "', '_blank');");
        }
    }
}

Mautic.updatePreviewContactLookupListFilter = function(field, item) {
    Mautic.updateLookupFieldListFilter(field, item, '#content_preview_settings_contact_id');
};

Mautic.updatePreviewCompanyLookupListFilter = function(field, item) {
    Mautic.updateLookupFieldListFilter(field, item, '#content_preview_settings_company_id');
};

Mautic.activatePreviewContactLookupField = function (fieldOptions, filterId) {
    Mautic.activateLookupField (fieldOptions, filterId, 'content_preview_settings_contact', '#content_preview_settings_contact_id', 'lead.lead');
};

Mautic.activatePreviewCompanyLookupField = function (fieldOptions, filterId) {
    Mautic.activateLookupField (fieldOptions, filterId, 'content_preview_settings_company', '#content_preview_settings_company_id', 'lead.company');
};

/**
 * Used in data-lookup-callback attr of form field in ContentPreviewSettingsType
 * Take a look at https://github.com/twitter/typeahead.js/
 */
Mautic.activateLookupField = function (fieldOptions, filterId, lookupElementId, elemId, searchKey) {
    const action = mQuery('#' + lookupElementId).attr('data-chosen-lookup');

    const options = {
        limit: 20,
        'searchKey': searchKey,
    };

    Mautic.activateFieldTypeahead(lookupElementId, filterId, options, action);
    Mautic.contentPreviewUrlGenerator.init();

    mQuery('#' + lookupElementId).on('change', function (event) {
        if (event.target.value === '') {
            Mautic.contentPreviewUrlGenerator.regenerateUrl('', mQuery(elemId));
            mQuery(elemId).val('');
        }
    });
};

/**
 * Used in data-lookup-callback attr of form field in ContentPreviewSettingsType
 */
Mautic.updateLookupFieldListFilter = function(field, item, elemId) {
    if (item && item.id) {
        mQuery(elemId).val(item.id);
        mQuery(field).val(item.value);
        Mautic.contentPreviewUrlGenerator.regenerateUrl(
            item.id,
            mQuery(elemId)
        );
    }
};
