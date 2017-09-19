<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view['assets']->addScript('app/bundles/PageBundle/Assets/js/prefcenter.js');
$channelNumber = 0;
?>
<?php if (isset($form)) : ?>
<table class="table table-striped">
    <?php foreach ($form['subscribed_channels']->vars['choices'] as $channel):
        $contactMe   = isset($leadChannels[$channel->value]);
        $checked     = $contactMe ? 'checked' : '';
        $channelName = strtolower($view['channel']->getChannelLabel($channel->value));
        ?>
        <tr>
            <td>
                <div class="text-left">
                    <input type="checkbox" id="<?php echo $channel->value ?>"
                           name="lead_contact_frequency_rules[subscribed_channels][]"
                           onclick="togglePreferredChannel(this.value);"
                           value="<?php echo $channel->value ?>" <?php echo $checked; ?>>
                    <label for="<?php echo $channel->value ?>" id="is-contactable-<?php echo $channel->value ?>" data-channel="<?php echo $channelName; ?>">
                        <?php echo $view['translator']->trans('mautic.lead.contact.me.label', ['%channel%' => $channelName]); ?>
                    </label>
                </div>
            </td>
        </tr>
        <tr>
            <td>
                <div id="frequency_<?php echo $channel->value; ?>" class="text-left">
                    <?php
                    if ($showContactFrequency):?>
                        <div class="col-md-6">
                            <label class="text-muted label1"><?php echo $view['translator']->trans($form['frequency_number_'.$channel->value]->vars['label']); ?></label>
                            <?php echo $view['form']->widget($form['frequency_number_'.$channel->value]); ?>
                            <?php echo $view['form']->label($form['frequency_time_'.$channel->value]); ?>
                            <?php echo $view['form']->widget($form['frequency_time_'.$channel->value]); ?>
                        </div>
                    <?php else:
                        unset($form['frequency_time_'.$channel->value]);
                        unset($form['frequency_number_'.$channel->value]);
                    endif; ?>
                    <?php if ($showContactPauseDates):?>
                        <div class="col-md-6">
                            <label class="text-muted label3"><?php echo $view['translator']->trans('mautic.lead.frequency.dates.label'); ?></label>
                            <?php echo $view['form']->widget($form['contact_pause_start_date_'.$channel->value]); ?>
                            <?php echo $view['form']->label($form['contact_pause_end_date_'.$channel->value]); ?>
                            <?php echo $view['form']->widget($form['contact_pause_end_date_'.$channel->value]); ?>
                        </div>
                        <?php
                    else:
                        unset($form['contact_pause_start_date_'.$channel->value]);
                        unset($form['contact_pause_end_date_'.$channel->value]);
                    endif; ?>
                </div>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
<?php else : ?>
<table class="table table-striped">
    <tbody>
    <tr>
        <td>
            <div class="text-left">
                <input type="checkbox" checked="">
                <label class="control-label">
                    <?php echo $view['translator']->trans('mautic.lead.contact.me.label'); ?></label>
            </div>
        </td>
    </tr>
    <tr>
        <td>
            <div id="frequency_email" class="text-left">
                <div class="col-xs-6">
                    <label class="text-muted label1"><?php echo $view['translator']->trans('mautic.lead.list.frequency.number'); ?></label>
                    <input type="text" class="frequency form-control">
                    <label class="text-muted fw-n frequency-label label2"><?php echo $view['translator']->trans('mautic.lead.list.frequency.times'); ?></label>
                    <select class="form-control">
                        <option value="" selected="selected"></option>
                    </select></div>
                <div class="col-xs-6">
                    <label class="text-muted label3"><?php echo  $view['translator']->trans('mautic.lead.frequency.dates.label'); ?></label>
                    <input type="date" class="form-control">
                    <label class="frequency-label text-muted fw-n label4"><?php echo  $view['translator']->trans('mautic.lead.frequency.contact.end.date'); ?></label>
                    <input type="date" class="form-control">
                </div>
            </div>
        </td>
    </tr>
    </tbody>
</table>
<?php endif; ?>

