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
<?php if (isset($form)) : ?>
    <?php if ($showContactPreferredChannels):?>
        <div class="preferred_channel text-left"><?php echo $view['form']->row($form['lead_channels']['preferred_channel']); ?></div>
        <?php
    else:
        unset($form['lead_channels']['preferred_channel']);
    endif;
else :
?>
<div class="preferred_channel text-left">
    <div class="row">
        <div class="form-group col-xs-12 ">
            <label class="control-label">
                <?php echo $view['translator']->trans('mautic.lead.list.frequency.preferred.channel'); ?>
            </label>
            <div class="choice-wrapper">
                <select class="form-control">
                    <option value="email" selected="selected"><?php echo $view['translator']->trans('mautic.email.email'); ?></option>
                </select></div>
        </div>
    </div>
</div>
<?php endif; ?>

