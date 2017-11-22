/** This section is only needed once per page if manually copying **/
if (typeof MauticPrefCenterLoaded === 'undefined') {
    var MauticPrefCenterLoaded = true;

    function replaceSlotParams(slot){
        var i, l = slot.dataset['paramLabelText'];

        function setLabelText(query) {
            var labels = slot.querySelectorAll(query);
            for (i = 0; i < labels.length; i++) {
                labels[i].innerHTML = l;
            }
        }

        if (l) {
            setLabelText('label.control-label', l);
            var channels = slot.querySelectorAll('label[data-channel]');
            for (i = 0; i < channels.length; i++) {
                channels[i].innerHTML = l.replace('%channel%', channels[i].dataset['channel']);
            }
        }
        var numOfLabelsInSlot = 4;
        for (i = 1; i <= numOfLabelsInSlot; i++) {
            l = slot.dataset['paramLabelText' + i];
            if (l) {
                setLabelText('label.label' + i, l);
            }
        }
    }

    // Handler when the DOM is fully loaded
    var callback = function(){
        var slots = document.querySelectorAll('div[data-slot="segmentlist"], div[data-slot="categorylist"], div[data-slot="preferredchannel"], div[data-slot="channelfrequency"]');
        for (var i = 0; i < slots.length; i++) {
            replaceSlotParams(slots[i]);
        }
    };

    if (document.readyState === "complete" || !(document.readyState === "loading" || document.documentElement.doScroll)) {
        callback();
    } else {
        document.addEventListener("DOMContentLoaded", callback);
    }

    function togglePreferredChannel(channel) {
        var status = document.getElementById(channel).checked;
        if (status) {
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
        var forms = document.getElementsByName(formId);
        for (var i = 0; i < forms.length; i++) {
            if (forms[i].tagName === 'FORM') {
                forms[i].submit();
            }
        }
    }
}
