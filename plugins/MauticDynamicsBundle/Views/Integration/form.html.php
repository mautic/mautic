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

<div class="well well-sm" style="margin-bottom:0 !important;">
    <p><?php echo $view['translator']->trans('mautic.plugin.dynamics.webhook'); ?></p>
    <div class="alert alert-warning">
        <?php echo $view['translator']->trans('mautic.plugin.dynamics.public_info'); ?>
    </div>
    <input type="text" readonly="readonly" value="<?php echo $mauticUrl?>" class="form-control" title="url"/>
</div>
