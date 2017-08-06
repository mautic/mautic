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
        <h3 class="panel-title"><?php echo $view['translator']->trans('mautic.config.tab.pageconfig'); ?></h3>
    </div>
    <div class="panel-body">
        <?php foreach ($form->children as $name => $f): ?>
            <?php if ('track_contact_by_ip' == $name && 'track_by_fingerprint' == $name) {
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