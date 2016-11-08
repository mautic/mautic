<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$leadId        = $lead->getId();
$leadName      = $lead->getPrimaryIdentifier();
$channelNumber = 0;
?>
<?php echo $view['form']->start($form); ?>
            <h4><?php echo $view['translator']->trans('mautic.lead.preferred.channels'); ?></h4>
    <?php foreach ($form['doNotContactChannels']->vars['choices'] as $channel): ?>
                <div>
                    <?php echo $view['form']->widget($form['doNotContactChannels'][$channelNumber]); ++$channelNumber; ?>
                <label for="<?php echo $channel->value ?>" id="is-contactable-<?php echo $channel->value ?>" class=" col-md-12">
                    <?php echo $view['translator']->trans('mautic.lead.contact.me.label', ['%channel%' => $channel->value]); ?>
                </label>
                </div>
                <div id="frequency_<?php echo $channel->value; ?>" class="col-md-12">
                    <?php if ($showContactFrequency):?>
                    <div class="col-md-6">
                        <div class="pull-left">
                            <?php echo $view['form']->label($form['frequency_number_'.$channel->value]); ?>
                            <?php echo $view['form']->widget($form['frequency_number_'.$channel->value]); ?>
                        </div>
                        <?php echo $view['form']->label($form['frequency_time_'.$channel->value]); ?>
                        <span class="clearfix">
                    <?php echo $view['form']->widget($form['frequency_time_'.$channel->value]); ?>
                </span>
                    </div>
                    <?php else:
                        unset($form['frequency_time_'.$channel->value]);
                        unset($form['frequency_number_'.$channel->value]);
                    endif; ?>
                    <?php if ($showContactPauseDates):?>
                    <div class="col-md-6">
                        <div>
                            <label class="control-label"><?php echo $view['translator']->trans('mautic.lead.frequency.dates.label'); ?></label>
                        </div>
                        <div class="pull-right">
                            <?php echo $view['form']->label($form['contact_pause_start_date_'.$channel->value]); ?>
                            <?php echo $view['form']->widget($form['contact_pause_start_date_'.$channel->value]); ?>
                        </div>
                        <div class="pull-right">
                            <?php echo $view['form']->label($form['contact_pause_end_date_'.$channel->value]); ?>
                            <?php echo $view['form']->widget($form['contact_pause_end_date_'.$channel->value]); ?>
                        </div>
                    </div>
                    <?php
                    else:
                        unset($form['contact_pause_start_date_'.$channel->value]);
                        unset($form['contact_pause_end_date_'.$channel->value]);
                    endif; ?>
                </div>
        <hr class="mnr-md mnl-md">
    <?php endforeach; ?>
<?php if ($showContactPreferredChannels):?>
    <div id="preferred_channel"><?php echo $view['form']->row($form['preferred_channel']); ?></div>
<?php
else:
    unset($form['preferred_channel']);
endif; ?>
<?php if ($showContactSegments):?>
     <div id="contact-segments"> <div><?php echo  $view['form']->label($form['lead_lists']); ?></div>
    <?php
        $segmentNumber = count($form['lead_lists']->vars['choices']);
        for ($i = ($segmentNumber - 1); $i >= 0; --$i): ?>
        <?php
            if (in_array($form['lead_lists']->vars['choices'][$i]->value, $form['lead_lists']->vars['value'])) :
        ?>
                <div id="segment-<?php echo $i; ?>">
                    <?php echo $view['form']->widget($form['lead_lists'][$i]); ?>
                    <?php echo $view['form']->label($form['lead_lists'][$i]); ?>
                </div>
        <?php endif; ?>
        <?php endfor;
        unset($form['lead_lists']);
        ?>
<?php
else:
    unset($form['lead_lists']);
endif; ?>
     </div>
<?php if ($showContactCategories):?>
        <div id="global-categories"><?php echo $view['form']->row($form['global_categories']); ?></div>
<?php
else:
    unset($form['global_categories']);
endif; ?>
<?php echo $view['form']->end($form); ?>

