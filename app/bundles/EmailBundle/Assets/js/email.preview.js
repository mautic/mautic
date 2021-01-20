// Email preview URL builder

Mautic.emailPreview = {

    urlBase : 'email/preview',

    init : function() {
        // Activate contact chosen
        // TODO
        Mautic.activateChosenSelect(mQuery('#email_preview_settings_contact'));
    },

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

    regenerateUrl : function(emailId) {
        let previewUrl = mauticBaseUrl + this.urlBase + '/' + emailId + '/'
            + this.getTranslationUrlPart() + this.getVariantUrlPart() + this.getContactUrlPart();
        // Update url in preview input
        mQuery('#email_preview_url').val(previewUrl);
        // Update URL in preview button
        mQuery('#email_preview_url_button').attr('onClick', "window.open('" + previewUrl + "', '_blank');");
    }
}

mQuery(document).ready(function() {
    Mautic.emailPreview.init();
});