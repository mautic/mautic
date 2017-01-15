<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<div class="panel panel-primary">
    <div class="panel-heading">
        <h3 class="panel-title"><?php echo $view['translator']->trans('mautic.config.tab.smsconfig'); ?></h3>
    </div>
    <div class="panel-body">
        <?php foreach ($form->children as $key => $f): ?>
        <?php if (in_array($key, ['sms_frequency_number', 'sms_frequency_time'])) {
    continue;
} ?>
        <div class="row">
            <div class="col-md-6">
                <?php echo $view['form']->row($f); ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="panel panel-primary">
    <div class="panel-heading">
        <h3 class="panel-title"><?php echo $view['translator']->trans('mautic.config.tab.frequency_rules'); ?></h3>
    </div>
    <div class="panel-body">
        <div class="row">
            <div class="col-md-12">
                <?php echo $view['form']->row($form->children['sms_frequency_number']); ?>
            </div>
            <div class="col-md-12">
                <?php echo $view['form']->row($form->children['sms_frequency_time']); ?>
            </div>
        </div>
    </div>
</div>
