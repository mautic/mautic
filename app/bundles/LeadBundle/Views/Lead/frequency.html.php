<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$leadId   = $lead->getId();
$leadName = $lead->getPrimaryIdentifier();
?>

    <?php echo $view['form']->start($form); ?>
    <table class="table" width="100%" id="contact-timeline">
        <thead >
        <tr >
            <th>
                <div>All</div>
                <input type="checkbox" id="lead_contact_frequency_rules_doNotContactChannels_0" name="check_all"
                       onclick="Mautic.togglePreferredChannel(<?php echo $leadId; ?>,this.value);" value="all">
            </th>
            <th>
                <?php echo $view['translator']->trans('mautic.lead.preferred.channels'); ?>
            </th>
        </tr>
        </thead>
        <tbody >


        <?php foreach ($form['doNotContactChannels']->vars['choices'] as $channel): ?>
            <?php
            $contactMe     = isset($leadChannels[$channel->value]);
            $bgClass       = $contactMe ? 'text-success' : 'text-danger';
            $isContactable = $contactMe ? 'channel-enabled' : 'channel-disabled';
            $hidden        = $contactMe ? '' : 'hide';
            $checked       = $contactMe ? 'checked' : '';
            ?>
            <tr>
                <th style="vertical-align: top" class="col-md-1">
                    <input type="checkbox" id="<?php echo $channel->value ?>"
                           name="lead_contact_frequency_rules[doNotContactChannels][]" class="contact checkbox"
                           onclick="Mautic.togglePreferredChannel(<?php echo $leadId; ?>,this.value);"
                           value="<?php echo $channel->value ?>" <?php echo $checked; ?>>
                </th>
                <td class="col-md-11">
                    <div id="is-contactable-<?php echo $channel->value ?>" class="<?php echo $isContactable; ?> col-md-12 fw-sb">
                        <?php echo $view['translator']->trans('mautic.lead.contact.me.label', ['%channel%' => $channel->value]); ?>
                    </div>

                    <div id="frequency_<?php echo $channel->value; ?>" class="<?php echo $hidden; ?> frequency-values">
                        <div class="col-md-6 ">
                <div class="pull-left">
                    <?php echo $view['form']->label($form['frequency_number_'.$channel->value]); ?>
                            <?php echo $view['form']->widget($form['frequency_number_'.$channel->value]); ?>
                </div>
                            <?php echo $view['form']->label($form['frequency_time_'.$channel->value]); ?>
                            <span>
                    <?php echo $view['form']->widget($form['frequency_time_'.$channel->value]); ?>
                </span>
                        </div>
                        <div class="col-md-6">
                            <div>
                                <label class="text-muted fw-n"><?php echo $view['translator']->trans('mautic.lead.frequency.dates.label'); ?></label>
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
                        <div class="clearfix"></div>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <table cellspacing="10" width="100%">
    <tbody>
        <?php unset($form['doNotContactChannels']); ?>
        <tr class="frequency-table">
            <td class="row">
                <div class="col-md-6"><?php echo $view['form']->row($form['preferred_channel']); ?></div>
                <div class="col-md-6"><?php echo $view['form']->row($form['global_categories']); ?></div>
            </td>
        </tr>
        <tr class="frequency-table">
            <td class="row"><div class="col-md-12"><?php echo $view['form']->row($form['lead_lists']); ?></div></td>
        </tr>
        </tbody>
    </table>
    <?php echo $view['form']->end($form); ?>

