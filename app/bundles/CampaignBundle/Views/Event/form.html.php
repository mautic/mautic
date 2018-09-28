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

<div class="bundle-form">
    <div class="bundle-form-header mb-10">
        <h3><?php echo $eventHeader; ?></h3>
        <?php if (!empty($eventDescription)): ?>
        <h6 class="text-muted"><?php echo $eventDescription; ?></h6>
        <?php endif; ?>
    </div>

    <?php echo $view['form']->start($form); ?>

    <?php echo $view['form']->widget($form['canvasSettings']['droppedX']); ?>
    <?php echo $view['form']->widget($form['canvasSettings']['droppedY']); ?>

    <?php echo $view['form']->row($form['name']); ?>

    <?php if (isset($form['triggerMode'])): ?>
    <div<?php echo $hideTriggerMode ? ' class="hide"' : ''; ?>>
        <?php echo $view['form']->row($form['triggerMode']); ?>

        <div<?php echo ('date' != $form['triggerMode']->vars['data']) ? ' class="hide"' : ''; ?> id="triggerDate">
            <?php echo $view['form']->row($form['triggerDate']); ?>
        </div>

        <div<?php echo ('interval' != $form['triggerMode']->vars['data']) ? ' class="hide"' : ''; ?> id="triggerInterval">
            <div class="row">
                <div class="col-sm-4">
                    <?php echo $view['form']->row($form['triggerInterval']); ?>
                </div>
                <div class="col-sm-8">
                    <?php echo $view['form']->row($form['triggerIntervalUnit']); ?>
                </div>
            </div>
            <div id="interval_settings" class="hide">
                <hr />
                <div class="row">
                    <div class="col-sm-12">
                        <div style="display:inline-block; font-weight: 600;"><?php echo $view['translator']->trans('mautic.campaign.form.type.interval_schedule_at'); ?> </div>
                        <div style="width: 75px; display:inline-block; margin:0 10px 0 10px;"><?php echo $view['form']->widget($form['triggerHour']); ?></div>
                        <div style="display:inline-block; font-weight: 600;"> <?php echo $view['translator']->trans('mautic.campaign.form.type.interval_schedule_between_hours'); ?> </div>
                        <div style="width: 75px;display:inline-block; margin:0 10px 0 10px;"><?php echo $view['form']->widget($form['triggerRestrictedStartHour']); ?></div>
                        <div style="display:inline-block; font-weight: 600;"> <?php echo $view['translator']->trans('mautic.core.and'); ?> </div>
                        <div style="width: 75px; display:inline-block; margin:0 10px 0 10px;"><?php echo $view['form']->widget($form['triggerRestrictedStopHour']); ?></div>
                    </div>
                </div>
                <hr />
                <div class="row mt-5">
                    <div class="col-sm-12" style="font-weight: 600;"><?php echo $view['translator']->trans('mautic.campaign.form.type.interval_trigger_restricted_dow'); ?></div>
                </div>
                <div class="row">
                    <div class="col-sm-3">
                        <div class="checkbox">
                            <label><?php echo $view['form']->widget($form['triggerRestrictedDaysOfWeek'][0]); ?> <?php echo $view['translator']->trans($form['triggerRestrictedDaysOfWeek'][0]->vars['label']); ?></label>
                        </div>
                        <div class="checkbox">
                            <label><?php echo $view['form']->widget($form['triggerRestrictedDaysOfWeek'][1]); ?> <?php echo $view['translator']->trans($form['triggerRestrictedDaysOfWeek'][1]->vars['label']); ?></label>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="checkbox">
                            <label><?php echo $view['form']->widget($form['triggerRestrictedDaysOfWeek'][2]); ?> <?php echo $view['translator']->trans($form['triggerRestrictedDaysOfWeek'][2]->vars['label']); ?></label>
                        </div>
                        <div class="checkbox">
                            <label><?php echo $view['form']->widget($form['triggerRestrictedDaysOfWeek'][3]); ?> <?php echo $view['translator']->trans($form['triggerRestrictedDaysOfWeek'][3]->vars['label']); ?></label>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="checkbox">
                            <label><?php echo $view['form']->widget($form['triggerRestrictedDaysOfWeek'][4]); ?> <?php echo $view['translator']->trans($form['triggerRestrictedDaysOfWeek'][4]->vars['label']); ?></label>
                        </div>
                        <div class="checkbox">
                            <label><?php echo $view['form']->widget($form['triggerRestrictedDaysOfWeek'][5]); ?> <?php echo $view['translator']->trans($form['triggerRestrictedDaysOfWeek'][5]->vars['label']); ?></label>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="checkbox">
                            <label><?php echo $view['form']->widget($form['triggerRestrictedDaysOfWeek'][6]); ?> <?php echo $view['translator']->trans($form['triggerRestrictedDaysOfWeek'][6]->vars['label']); ?></label>
                        </div>
                        <div class="checkbox">
                            <label><?php echo $view['form']->widget($form['triggerRestrictedDaysOfWeek'][7]); ?> <?php echo $view['translator']->trans($form['triggerRestrictedDaysOfWeek'][7]->vars['label']); ?></label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php echo $view['form']->end($form); ?>
</div>