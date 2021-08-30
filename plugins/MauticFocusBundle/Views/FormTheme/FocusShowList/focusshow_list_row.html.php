<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
echo $view['assets']->includeScript('plugins/MauticFocusBundle/Assets/js/focus.js');

?>


<div class="row">
    <div class="col-xs-12">
        <?php echo $view['form']->row($form['focus']); ?>
    </div>
    <div class="col-xs-12 mt-lg">
        <div class="mt-3">
            <?php echo $view['form']->row($form['newFocusButton']); ?>
            <?php echo $view['form']->row($form['editFocusButton']); ?>
        </div>
    </div>
</div>