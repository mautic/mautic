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

<div class="row condition-row">
    <div class="col-xs-4">
        <?php echo $view['form']->row($form['field']); ?>
    </div>
    <div class="col-xs-4">
        <?php echo $view['form']->row($form['operator']); ?>
    </div>
    <div class="col-xs-4">
        <?php echo $view['form']->row($form['value']); ?>
    </div>
</div>

<div class="row condition-custom-date-row" style="display: none;">
    <div class="col-sm-offset-4 col-sm-4">
        <div class="row">
            <div class="form-group col-xs-12 ">
                <div class="input-group">
                    <span class="input-group-addon preaddon">
                        <i class="symbol-hashtag"></i>
                    </span>
                    <input autocomplete="false" type="number" id="lead-field-custom-date-interval" class="form-control" value="1" onchange="Mautic.updateLeadFieldValueOptions(mQuery('#campaignevent_properties_value'), true)">
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="row">
            <div class="form-group col-xs-12 ">
                <select id="lead-field-custom-date-unit" class="form-control chosen" autocomplete="false" onchange="Mautic.updateLeadFieldValueOptions(mQuery('#campaignevent_properties_value'), true)">
                    <?php foreach (["i", "h", "d", "m", "y"] as $interval): ?>
                    <?php $selected = ("d" === $interval) ? " selected" : ""; ?>
                    <option<?php echo $selected; ?> value="<?php echo $interval;?>"><?php echo $view['translator']->trans('mautic.campaign.event.intervalunit.choice.'.$interval); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>
</div>