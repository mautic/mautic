<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

// NOTE - The contents of this view will replace the 'update-panel' <div> of MauticCoreBundle:update:index.html.php
?>

<div class="col-sm-offset-2 col-sm-8">
    <div id="main-update-panel" class="panel panel-default">
        <div class="panel-heading">
            <h2 class="panel-title">
                <?php echo $view['translator']->trans('mautic.core.update.in.progress'); ?>
            </h2>
        </div>
        <div class="panel-body">
            <table class="table table-hover table-striped table-bordered addon-list" id="updateTable">
                <thead>
                <tr>
                    <th><?php echo $view['translator']->trans('mautic.core.update.heading.step'); ?></th>
                    <th><?php echo $view['translator']->trans('mautic.core.update.heading.status'); ?></th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td><?php echo $view['translator']->trans('mautic.core.update.step.downloading.package'); ?></td>
                    <td id="update-step-downloading-status"><span class="hidden-xs"><?php echo $view['translator']->trans('mautic.core.update.step.in.progress'); ?></span><i class="pull-right fa fa-spinner fa-spin"></i></td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
