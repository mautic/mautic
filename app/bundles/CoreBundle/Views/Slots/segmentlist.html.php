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
<div class="contact-segments">
    <div class="text-left">
        <label class="control-label"><?php echo  $view['translator']->trans('mautic.lead.form.list'); ?></label>
    </div>
    <div id="segment-1" class="text-left">
        <input type="checkbox" id="lead_contact_frequency_rules_lead_lists_1" name="lead_contact_frequency_rules[lead_lists][]" autocomplete="false" value="2" checked="checked">
        <label for="lead_contact_frequency_rules_lead_lists_1"><?php echo  $view['translator']->trans('mautic.lead.lead.field.list'); ?></label>
    </div>
</div>
