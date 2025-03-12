/** This section is only needed once per page if manually copying **/
if (typeof MauticPrefCenterLoaded === 'undefined') {
    var MauticPrefCenterLoaded = true;

    function togglePreferredChannel(channel) {
        var status = document.getElementById(channel).checked;
        const fieldsToToggle = [
            'frequency_number',
            'frequency_time',
            'contact_pause_start_date',
            'contact_pause_end_date',
            // Do we need the 4 above?
            'lead_channels_frequency_number',
            'lead_channels_frequency_time',
            'lead_channels_contact_pause_start_date',
            'lead_channels_contact_pause_end_date',
        ];
        fieldsToToggle.forEach(field => {
            const element = document.getElementById('lead_contact_frequency_rules_' + field + '_' + channel);

            if (element) {
                if (status) {
                    element.removeAttribute('disabled');
                } else {
                    element.setAttribute('disabled', 'disabled');
                }
                element.dispatchEvent(new CustomEvent('chosen:updated'));
            }
        });
    }

    function saveUnsubscribePreferences(formId) {
        var forms = document.getElementsByName(formId);
        for (var i = 0; i < forms.length; i++) {
            if (forms[i].tagName === 'FORM') {
                forms[i].submit();
            }
        }
    }
}
