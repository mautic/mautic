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
$js            = <<<'JS'
function togglePreferredChannel(channel){
  const status = document.getElementById(channel).checked;
  
  const fields = [
    'lead_contact_frequency_rules_lead_channels_frequency_number_' + channel,
    'lead_contact_frequency_rules_lead_channels_frequency_time_' + channel,
    'lead_contact_frequency_rules_lead_channels_contact_pause_start_date_' + channel,
    'lead_contact_frequency_rules_lead_channels_contact_pause_end_date_' + channel
  ];
      
  // disable the input fields if the main checkbox is disabled
  for (let index = 0; index < fields.length; index++) {
    const field = document.getElementById(fields[index]);
    if (field) {
      field.disabled = !status;
    }
  }
}
JS;

?>
<script><?php echo $js; ?></script>
<div class="row text-left">
    <?php echo $view['form']->start($form); ?>
    <div class="col-xs-12 col-md-8 col-md-offset-2">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h1 class="panel-title"><?php echo $view['translator']->trans('mautic.lead.message.preferences'); ?></h1>
            </div>
            <div class="panel-body">
                <div class="the-price">
                    <h4> <?php echo $leadName; ?></h4>
                    <small> <?php
                        echo $view['translator']->trans('mautic.lead.message.preferences.descr'); ?></small>
                </div>
                <table class="table table-striped">
                    <?php if ($showContactFrequency):?>
                    <?php foreach ($form['lead_channels']['subscribed_channels']->vars['choices'] as $key => $channel):
                        $contactMe   = isset($leadChannels[$channel->value]);
                        $checked     = $contactMe ? 'checked' : '';
                        $channelName = strtolower($view['channel']->getChannelLabel($channel->value));
                        ?>
                    <tr>
                        <td>
                            <div class="text-left">
                                <input type="hidden" id="<?php echo $channel->value; ?>-hidden"
                                       name="lead_contact_frequency_rules[lead_channels][subscribed_channels][<?php echo $key; ?>]"
                                       value="">
                                <input type="checkbox" id="<?php echo $channel->value; ?>"
                                       name="lead_contact_frequency_rules[lead_channels][subscribed_channels][<?php echo $key; ?>]"
                                       onclick="togglePreferredChannel(this.value);"
                                       value="<?php echo $view->escape($channel->value); ?>" <?php echo $checked; ?>>
                                <label for="<?php echo $channel->value; ?>" id="is-contactable-<?php echo $channel->value; ?>">
                                    <?php echo $view['translator']->trans('mautic.lead.contact.me.label', ['%channel%' => $channelName]); ?>
                                </label>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div id="frequency_<?php echo $channel->value; ?>" class="text-left row">
                                <?php
                                if ($showContactFrequency):?>
                                    <div class="col-md-6">
                                        <label class="text-muted"><?php echo $view['translator']->trans($form['lead_channels']['frequency_number_'.$channel->value]->vars['label']); ?></label>
                                        <?php echo $view['form']->widget($form['lead_channels']['frequency_number_'.$channel->value]); ?>
                                        <?php echo $view['form']->label($form['lead_channels']['frequency_time_'.$channel->value]); ?>
                                        <?php echo $view['form']->widget($form['lead_channels']['frequency_time_'.$channel->value]); ?>
                                    </div>
                                <?php else:
                                    unset($form['lead_channels']['frequency_time_'.$channel->value]);
                                    unset($form['lead_channels']['frequency_number_'.$channel->value]);
                                endif; ?>
                                <?php if ($showContactPauseDates):?>
                                    <div class="col-md-6">
                                        <label class="text-muted"><?php echo $view['translator']->trans('mautic.lead.frequency.dates.label'); ?></label>
                                        <?php echo $view['form']->widget($form['lead_channels']['contact_pause_start_date_'.$channel->value]); ?>
                                        <?php echo $view['form']->label($form['lead_channels']['contact_pause_end_date_'.$channel->value]); ?>
                                        <?php echo $view['form']->widget($form['lead_channels']['contact_pause_end_date_'.$channel->value]); ?>
                                    </div>
                                    <?php
                                else:
                                    unset($form['lead_channels']['contact_pause_start_date_'.$channel->value]);
                                    unset($form['lead_channels']['contact_pause_end_date_'.$channel->value]);
                                endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </table>
                <?php if ($showContactPreferredChannels):?>
                <hr />
                <div id="preferred_channel" class="text-left"><?php echo $view['form']->row($form['lead_channels']['preferred_channel']); ?></div>
                <?php
                else:
                    unset($form['lead_channels']['preferred_channel']);
                endif; ?>
                <?php if ($showContactSegments && count($form['lead_lists'])):?>
                <hr />
                <div id="contact-segments"> <div class="text-left"><?php echo  $view['form']->label($form['lead_lists']); ?></div>
                    <?php
                    foreach ($form['lead_lists'] as $key=>$leadList) {
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
                    <?php
                else:
                    unset($form['lead_lists']);
                endif; ?>
                <?php if ($showContactCategories && count($form['global_categories'])):?>
                <hr />
                <div id="global-categories" class="text-left">
                    <div><?php echo  $view['form']->label($form['global_categories']); ?></div>
                    <?php $categoryNumber = count($form['global_categories']->vars['choices']);
                    for ($i = ($categoryNumber - 1); $i >= 0; --$i): ?>
                        <div id="category-<?php echo $i; ?>" class="text-left">
                            <?php echo $view['form']->widget($form['global_categories'][$i]); ?>
                            <?php echo $view['form']->label($form['global_categories'][$i]); ?>
                        </div>
                    <?php
                    endfor;
                    unset($form['global_categories']);
                    ?>
                </div>
                <?php
                else:
                    unset($form['global_categories']);
                endif;
                ?>
            </div>
            <div class="panel-footer text-left">
                <?php echo $view['form']->row($form['buttons']['save']); unset($form['buttons']['cancel']); ?></div>
        </div>
    </div>

    <?php
    unset($form['lead_channels']);
    echo $view['form']->end($form); ?>
</div>
