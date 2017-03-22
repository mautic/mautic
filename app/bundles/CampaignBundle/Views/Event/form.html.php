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

        <div<?php echo ($form['triggerMode']->vars['data'] != 'date') ? ' class="hide"' : ''; ?> id="triggerDate">
            <?php echo $view['form']->row($form['triggerDate']); ?>
        </div>

        <div<?php echo ($form['triggerMode']->vars['data'] != 'interval') ? ' class="hide"' : ''; ?> id="triggerInterval">
            <div class="row">
                <div class="col-sm-4">
                    <?php echo $view['form']->row($form['triggerInterval']); ?>
                </div>
                <div class="col-sm-8">
                    <?php echo $view['form']->row($form['triggerIntervalUnit']); ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php echo $view['form']->end($form); ?>
</div>