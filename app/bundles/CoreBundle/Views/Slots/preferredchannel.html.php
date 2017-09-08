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
<div class="preferred_channel text-left">
    <div class="row">
        <div class="form-group col-xs-12 ">
            <label class="control-label" for="lead_contact_frequency_rules_preferred_channel" data-toggle="tooltip"
                   data-container="body" data-placement="top" title="My preferred channel">
                <?php echo $view['translator']->trans('mautic.lead.list.frequency.preferred.channel'); ?>
                <i class="fa fa-question-circle"></i></label>
            <div class="choice-wrapper">
                <select id="lead_contact_frequency_rules_preferred_channel"
                        name="lead_contact_frequency_rules[preferred_channel]" class="form-control"
                        autocomplete="false">
                    <option value="email" selected="selected"><?php echo $view['translator']->trans('mautic.email.email'); ?></option>
                </select></div>
        </div>
    </div>
</div>
