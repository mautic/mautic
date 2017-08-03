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

<div class="panel panel-primary">
    <div class="panel-heading">
        <h3 class="panel-title"><?php echo $view['translator']->trans('mautic.config.tab.importconfig'); ?></h3>
    </div>
    <div class="panel-body">
        <div class="col-xs-12">
            <?php echo $view['form']->row($form['background_import_if_more_rows_than']); ?>
        </div>
</div>
</div>

<div class="panel panel-primary">
    <div class="panel-heading">
        <h3 class="panel-title"><?php echo $view['translator']->trans('mautic.config.tab.segments.mapping'); ?></h3>
    </div>
    <div class="panel-body">
        <div class="col-xs-12">
            <?php echo $view['form']->row($form['segments_mapping_created']); ?>
        </div>
        <div class="col-xs-12">
            <?php echo $view['form']->row($form['segments_mapping_identified']); ?>
        </div>
    </div>
</div>