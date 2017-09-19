/** This section is only needed once per page if manually copying **/
if (typeof MauticPrefCenterLoaded == 'undefined') {
    var MauticPrefCenterLoaded = true;
    // Handler when the DOM is fully loaded
    var callback = function(){
        // replace slot parameters
        jQuery('div[data-slot=segmentlist], div[data-slot=categorylist], div[data-slot=preferredchannel], div[data-slot=channelfrequency]').each(function(){
            var $s = jQuery(this);
            var l = $s.data('param-label-text');
            if (l) {
                jQuery('label.control-label', $s).text(l);
                jQuery('label[data-channel]', $s).each(function(){
                    var $l = jQuery(this);
                    $l.text(l.replace('%channel%', $l.data('channel')));
                });
            }
            for (var i = 1; i <= 4; i++) {
                l = $s.data('param-label-text' + i);
                if (l) {
                    jQuery('label.label' + i, $s).text(l);
                }
            }
        });
    };

    if (
        document.readyState === "complete" ||
        !(document.readyState === "loading" || document.documentElement.doScroll)
    ) {
        callback();
    } else {
        document.addEventListener("DOMContentLoaded", callback);
    }

    function togglePreferredChannel(channel) {
        var status = document.getElementById(channel).checked;
        if(status)
        {
            document.getElementById('lead_contact_frequency_rules_frequency_number_' + channel).disabled = false;
            document.getElementById('lead_contact_frequency_rules_frequency_time_' + channel).disabled = false;
            document.getElementById('lead_contact_frequency_rules_contact_pause_start_date_' + channel).disabled = false;
            document.getElementById('lead_contact_frequency_rules_contact_pause_end_date_' + channel).disabled = false;
        } else {
            document.getElementById('lead_contact_frequency_rules_frequency_number_' + channel).disabled = true;
            document.getElementById('lead_contact_frequency_rules_frequency_time_' + channel).disabled = true;
            document.getElementById('lead_contact_frequency_rules_contact_pause_start_date_' + channel).disabled = true;
            document.getElementById('lead_contact_frequency_rules_contact_pause_end_date_' + channel).disabled = true;
        }
    }

    function saveUnsubscribePreferences(formId) {
        var form = jQuery('form[name=' + formId + ']');
        form.submit();
    }
}
