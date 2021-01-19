// Email preview URL builder

Mautic.emailPreview = {

    urlBase : 'email/preview',

    buildValueUrlPart  : function(name, value) {

        if (value === undefined) {
            return '';
        }

        return name + '/' + value + '/';
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
        mQuery('#email_preview_url').val(url);
    }
}

