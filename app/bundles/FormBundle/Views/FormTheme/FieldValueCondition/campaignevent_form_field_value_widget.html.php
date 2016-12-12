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
    <div class="col-xs-12">
        <?php echo $view['form']->row($form['form']); ?>
    </div>
</div>
<div class="row">
    <div class="col-xs-4">
        <?php echo $view['form']->row($form['field']); ?>
    </div>
    <div class="col-xs-4">
        <?php echo $view['form']->row($form['operator']); ?>
    </div>
    <div class="col-xs-4">
        <?php echo $view['form']->row($form['value']); ?>
    </div>
</div>
