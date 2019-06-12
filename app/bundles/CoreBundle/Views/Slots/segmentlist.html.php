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
    <?php if ($showContactSegments && isset($form['lead_lists']) && count($form['lead_lists'])):?>
        <div class="contact-segments">
            <div class="text-left">
                <label class="control-label"><?php echo isset($label_text) ? $label_text : $view['translator']->trans('mautic.lead.form.list'); ?></label>
            </div>
            <?php
            $segmentNumber = count($form['lead_lists']->vars['choices']);
            for ($i = ($segmentNumber - 1); $i >= 0; --$i) : ?>
                <div id="segment-<?php echo $i; ?>" class="text-left">
                    <?php echo $view['form']->widget($form['lead_lists'][$i]); ?>
                    <?php echo $view['form']->label($form['lead_lists'][$i]); ?>
                </div>
                <?php
            endfor;
            unset($form['lead_lists']);
            ?>
        </div>
    <?php else:
        unset($form['lead_lists']);
    endif;
else :
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
<?php endif; ?>
