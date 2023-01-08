<?php

/*
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<div class="row">
    <div class="col-md-6">
        <div class="row">
            <div class="form-group col-xs-12">
                <?php echo $view['form']->row($form['hashtag']); ?>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="row">
            <div class="form-group col-xs-12">
                <?php echo $view['form']->row($form['checknames']); ?>
            </div>
        </div>
    </div>
</div>