<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<div class="bundle-form">
    <div class="bundle-form-header">
        <h3><?php echo $actionHeader; ?></h3>
    </div>

    <?php echo $view['form']->start($form); ?>

    <?php echo $view['form']->widget($form['canvasSettings']['droppedX']); ?>
    <?php echo $view['form']->widget($form['canvasSettings']['droppedY']); ?>

    <?php echo $view['form']->row($form['name']); ?>

    <?php if (isset($form['triggerMode'])): ?>
    <?php echo $view['form']->row($form['triggerMode']); ?>

    <div<?php echo ($form['triggerMode']->vars['value'] != 'date') ? ' class="hide"' : ''; ?> id="triggerDate">
        <?php echo $view['form']->row($form['triggerDate']); ?>
    </div>

    <div<?php echo ($form['triggerMode']->vars['value'] != 'interval') ? ' class="hide"' : ''; ?> id="triggerInterval">
        <div class="row">
            <div class="col-sm-4">
                <?php echo $view['form']->row($form['triggerInterval']); ?>
            </div>
            <div class="col-sm-8">
                <?php echo $view['form']->row($form['triggerIntervalUnit']); ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php echo $view['form']->end($form); ?>
</div>