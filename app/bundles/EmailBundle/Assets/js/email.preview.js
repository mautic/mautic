// Email preview URL builder

Mautic.emailPreview = {

    urlBase : 'email/preview',

    buildValueUrlPart  : function(parameterName, value) {

        if (value === undefined || value.length === 0) {
            return '';
        }

        return parameterName + '/' + value + '/';
    },

    getTranslationUrlPart : function() {
        return this.buildValueUrlPart(
            'translation',
            mQuery('#email_preview_settings_translation').val()
        );
    },

    getVariantUrlPart : function() {
        return this.buildValueUrlPart(
            'variant',
            mQuery('#email_preview_settings_variant').val()
        );
    },

    getContactUrlPart : function() {
        return this.buildValueUrlPart(
            'contact',
            mQuery('#email_preview_settings_contact').val()
        );
    },

    regenerateUrl : function() {
        let url = mauticBaseUrl + this.urlBase + '/' + this.getTranslationUrlPart() + this.getVariantUrlPart() + this.getContactUrlPart();
        // Update url in preview input
        mQuery('#email_preview_url').val(url);
        // Update URL in preview button
        mQuery('#email_preview_url_button').attr('onClick', "window.open('" + url + "', '_blank');");
    }
}

