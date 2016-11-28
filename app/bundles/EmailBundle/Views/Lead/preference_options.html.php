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
       var status = document.getElementById(channel).checked;
       if(status)
           {
                document.getElementById('lead_contact_frequency_rules_frequency_number_' + channel).disabled = false;
                document.getElementById('lead_contact_frequency_rules_frequency_time_' + channel).disabled = false;
                document.getElementById('lead_contact_frequency_rules_contact_pause_start_date_' + channel).disabled = false;
                document.getElementById('lead_contact_frequency_rules_contact_pause_end_date_' + channel).disabled = false;
            } else {
                document.getElementById('lead_contact_frequency_rules_frequency_number_' + channel).disabled = true;
                document.getElementById('lead_contact_frequency_rules_frequency_time_' + channel).disabled = true;
                document.getElementById('lead_contact_frequency_rules_contact_pause_start_date_' + channel).disabled = true;
                document.getElementById('lead_contact_frequency_rules_contact_pause_end_date_' + channel).disabled = true;
            }
        }
JS;

?>
<script><?php echo $js; ?></script>
<div class="container">
    <div class="row text-left">
        <?php echo $view['form']->start($form); ?>
        <div class="col-xs-12 col-md-6 col-md-offset-3">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h1 class="panel-title"><?php echo $view['translator']->trans('mautic.lead.message.preferences'); ?></h1>
                </div>
                <div class="panel-body">
                    <div class="the-price">
                        <h4> <?php echo $leadName?></h4>
                        <small> <?php
                            echo $view['translator']->trans('mautic.lead.message.preferences.descr'); ?></small>
                    </div>
                    <table class="table">
                        <?php foreach ($form['doNotContactChannels']->vars['choices'] as $channel):
                            $contactMe = isset($leadChannels[$channel->value]);
                            $checked   = $contactMe ? 'checked' : '';
                            ?>
                        <tr>
                            <td>
                                <div class="text-left">
                                    <input type="checkbox" id="<?php echo $channel->value ?>"
                                           name="lead_contact_frequency_rules[doNotContactChannels][]"
                                           onclick="togglePreferredChannel(this.value);"
                                           value="<?php echo $channel->value ?>" <?php echo $checked; ?>>
                                    <label for="<?php echo $channel->value ?>" id="is-contactable-<?php echo $channel->value ?>">
                                        <?php echo $view['translator']->trans('mautic.lead.contact.me.label', ['%channel%' => $channel->value]); ?>
                                    </label>
                                </div>
                            </td>
                        </tr>
                        <tr class="active">
                            <td>
                                <div id="frequency_<?php echo $channel->value; ?> text-left">
                                    <?php
                                    if ($showContactFrequency):?>
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
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if ($showContactPreferredChannels):?>
                        <tr>
                            <td>
                                <div id="preferred_channel" class="text-left"><?php echo $view['form']->row($form['preferred_channel']); ?></div>
                            </td>
                        </tr>
                            <?php
                        else:
                            unset($form['preferred_channel']);
                        endif; ?>
                        <?php if ($showContactSegments && !empty($form['lead_lists']->vars['value'])):?>
                        <tr class="active">
                            <td>
                                <div id="contact-segments"> <div class="text-left"><?php echo  $view['form']->label($form['lead_lists']); ?></div>
                                    <?php
                                    $segmentNumber = count($form['lead_lists']->vars['choices']);
                                    for ($i = ($segmentNumber - 1); $i >= 0; --$i): ?>
                                        <?php
                                        if (in_array($form['lead_lists']->vars['choices'][$i]->value, $form['lead_lists']->vars['value'])) :
                                            ?>
                                            <div id="segment-<?php echo $i; ?>" class="text-left">
                                                <?php echo $view['form']->widget($form['lead_lists'][$i]); ?>
                                                <?php echo $view['form']->label($form['lead_lists'][$i]); ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php endfor;
                                    unset($form['lead_lists']);
                                    ?>

                                </div>
                            </td>
                        </tr>
                            <?php
                        else:
                            unset($form['lead_lists']);
                        endif; ?>
                        <?php if ($showContactCategories && !empty($form['global_categories']->vars['value'])):?>
                        <tr>
                            <td>
                                <div id="global-categories" class="text-left">
                                    <div><?php echo  $view['form']->label($form['global_categories']); ?></div>
                                    <?php $categoryNumber = count($form['global_categories']->vars['choices']);
                                    for ($i = ($categoryNumber - 1); $i >= 0; --$i): ?>
                                        <?php
                                        if (in_array($form['global_categories']->vars['choices'][$i]->value, $form['global_categories']->vars['value'])) :
                                            ?>
                                            <div id="category-<?php echo $i; ?>" class="text-left">
                                                <?php echo $view['form']->widget($form['global_categories'][$i]); ?>
                                                <?php echo $view['form']->label($form['global_categories'][$i]); ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php endfor;
                                    unset($form['global_categories']);
                                    ?></div>
                            </td>
                        </tr>
                            <?php
                        else:
                            unset($form['global_categories']);
                        endif; ?>
                    </table>
                </div>
                <div class="panel-footer text-left">
                    <?php echo $view['form']->row($form['buttons']['save']); unset($form['buttons']['cancel']) ?></div>
            </div>
        </div>

        <?php
        unset($form['doNotContactChannels']);
        echo $view['form']->end($form); ?>

    </div>
</div>
