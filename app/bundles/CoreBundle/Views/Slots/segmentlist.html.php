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
            foreach ($form['lead_lists'] as $key => $leadList) {
                ?>
                <div id="segment-<?php echo $key; ?>" class="text-left">
                    <?php echo $view['form']->widget($leadList); ?>
                    <?php echo $view['form']->label($leadList); ?>
                </div>
                <?php
            }
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
