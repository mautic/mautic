<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>
<div class="frequency_email text-left">
    <div class="col-md-6">
        <label class="text-muted"><?php echo $view['translator']->trans('mautic.lead.frequency.dates.label'); ?></label>
        <input type="date" id="lead_contact_frequency_rules_contact_pause_start_date_email"
               name="lead_contact_frequency_rules[contact_pause_start_date_email]" class="form-control"
               autocomplete="false">
        <label class="frequency-label text-muted fw-n"
               for="lead_contact_frequency_rules_contact_pause_end_date_email"><?php echo $view['translator']->trans('mautic.lead.frequency.contact.end.date'); ?></label>
        <input type="date" id="lead_contact_frequency_rules_contact_pause_end_date_email"
               name="lead_contact_frequency_rules[contact_pause_end_date_email]" class="form-control"
               autocomplete="false">
    </div>
</div>