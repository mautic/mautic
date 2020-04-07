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

<div class="row condition-row">
    <div class="col-xs-12">
        <?php echo $view['form']->row($form['campaigns']); ?>
    </div>
</div>

<div class="row condition-row">
    <div class="col-xs-5">
        <?php echo $view['form']->row($form['dataAddedLimit']); ?>
    </div>
    <div class="col-xs-3">
        <?php echo $view['form']->row($form['expr']); ?>
    </div>
    <div class="col-xs-4">
        <?php echo $view['form']->row($form['dateAdded']); ?>
    </div>
</div>
