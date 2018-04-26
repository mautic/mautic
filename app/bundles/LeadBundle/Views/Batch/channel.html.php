<?php

$attr                         = $form->vars['attr'];
$attr['data-submit-callback'] = 'leadBatchSubmit';

echo $view['form']->start($form, ['attr' => $attr]);
?>
<table class="table" width="100%">
    <thead >
    <tr >
        <th>
            <input type="checkbox" id="contact_channels_subscribed_channels_0" name="check_all"
                   onclick="Mautic.togglePreferredChannel('all');" value="all">
        </th>
        <th>
            <?php echo $view['translator']->trans('mautic.lead.contact.channels'); ?>
        </th>
        <th><?php echo $view['translator']->trans('mautic.lead.preferred.frequency'); ?></th>
        <th><?php echo $view['translator']->trans('mautic.lead.preferred.channels'); ?></th>
    </tr>
    </thead>
    <tbody>

    <?php foreach ($form['subscribed_channels']->vars['choices'] as $channel): ?>
        <?php
        $contactMe     = isset($leadChannels[$channel->value]);
        $isContactable = $contactMe ? '' : 'text-muted';
        $hidden        = $contactMe ? '' : 'hide';
        $checked       = $contactMe ? 'checked' : '';
        $disabled      = isset($leadChannels[$channel->value]) ? '' : 'disabled';
        ?>
        <tr>
            <th style="vertical-align: top" class="col-md-1">
                <input type="checkbox" id="<?php echo $channel->value ?>"
                       name="contact_channels[subscribed_channels][]" class="control-label"
                       onclick="Mautic.togglePreferredChannel(this.value);"
                       value="<?php echo $view->escape($channel->value) ?>" <?php echo $checked; ?>>
            </th>
            <td class="col-md-1" style="vertical-align: top">
                <div id="is-contactable-<?php echo $channel->value ?>" class="<?php echo $isContactable; ?> fw-sb">
                    <?php echo $view['channel']->getChannelLabel($channel->value); ?>
                </div>
            </td>
            <td class="col-md-9" style="vertical-align: top">
                <div>
                    <div class="pull-left">
                        <?php
                        $attr = $form['frequency_number_'.$channel->value]->vars['attr'];
                        $attr['class'] .= ' pull-left';
                        ?>
                        <?php echo $view['form']->widget($form['frequency_number_'.$channel->value], ['attr' => $attr]); ?>
                        <?php echo $view['form']->label($form['frequency_time_'.$channel->value]); ?>
                        <div class="frequency-select"><?php echo $view['form']->widget($form['frequency_time_'.$channel->value]); ?></div>
                    </div>
                </div>
            </td>
            <td class="col-md-1" style="vertical-align: top;" align="center">
                <input type="radio" id="preferred_<?php echo $channel->value ?>"
                       name="contact_channels[preferred_channel]" class="contact"
                       value="<?php echo $view->escape($channel->value) ?>" <?php if ($form['preferred_channel']->vars['value'] == $channel->value) {
                            echo $checked;
                        } ?> <?php echo $disabled; ?>>

            </td>
        </tr>
        <tr style="border-top:none"><th style="border-top:none"></th>
            <td  style="border-top:none"></td>
            <td colspan="2" style="border-top:none">
                <div id="frequency_<?php echo $channel->value; ?>" <?php if (!empty($hidden)) :?>class="<?php echo $hidden; ?>"<?php endif; ?> >
                    <div>
                        <label class="text-muted fw-n"><?php echo $view['translator']->trans('mautic.lead.frequency.dates.label'); ?></label>
                    </div>
                    <div>
                        <?php echo $view['form']->widget($form['contact_pause_start_date_'.$channel->value]); ?>
                        <div style="float:left;">
                            <?php echo $view['form']->label($form['contact_pause_end_date_'.$channel->value]); ?>
                        </div>
                        <?php echo $view['form']->widget($form['contact_pause_end_date_'.$channel->value]); ?>
                    </div>
                </div>
                <div class="clearfix"></div>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php
unset($form['preferred_channel']);
unset($form['subscribed_channels']);
echo $view['form']->end($form);
