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
        <label class="text-muted"><?php echo $view['translator']->trans('mautic.lead.list.frequency.number'); ?></label>
        <input type="text" id="lead_contact_frequency_rules_frequency_number_email"
               name="lead_contact_frequency_rules[frequency_number_email]" class="frequency form-control"
               autocomplete="false">
        <label class="text-muted fw-n frequency-label"
               for="lead_contact_frequency_rules_frequency_time_email"><?php echo $view['translator']->trans('mautic.lead.list.frequency.times'); ?></label>
        <select
                id="lead_contact_frequency_rules_frequency_time_email"
                name="lead_contact_frequency_rules[frequency_time_email]" class="form-control" autocomplete="false">
            <option value=""></option>
            <option value="DAY"><?php echo $view['translator']->trans('mautic.core.time.days'); ?></option>
            <option value="WEEK"><?php echo $view['translator']->trans('mautic.core.time.weeks'); ?></option>
            <option value="MONTH"><?php echo $view['translator']->trans('mautic.core.time.months'); ?></option>
        </select>
    </div>
</div>