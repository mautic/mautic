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

<div class="row">
    <div class="col-xs-<?php echo isset($form['email_type']) ? 7 : 12; ?>">
        <?php echo $view['form']->row($form['email']); ?>
    </div>
    <?php if (isset($form['email_type'])): ?>
        <div class="col-xs-5">
            <?php echo $view['form']->row($form['email_type']); ?>
        </div>
    <?php endif; ?>
    <?php if (isset($form['priority'])): ?>
        <div id="priority" class="col-xs-5 queue_hide">
            <?php echo $view['form']->row($form['priority']); ?>
        </div>
    <?php endif; ?>
    <?php if (isset($form['attempts'])): ?>
        <div id="attempts" class="col-xs-5 queue_hide">
            <?php echo $view['form']->row($form['attempts']); ?>
        </div>
    <?php endif; ?>
</div>
<div class="row">
    <div class="col-xs-12 mt-lg">
        <div class="mt-3">
            <?php echo $view['form']->row($form['newEmailButton']); ?>
            <?php echo $view['form']->row($form['editEmailButton']); ?>
            <?php echo $view['form']->row($form['previewEmailButton']); ?>
        </div>
    </div>
</div>