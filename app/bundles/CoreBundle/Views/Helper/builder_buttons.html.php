<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

?>

<div class="row">
    <div class="col-xs-6">
        <button type="button" class="btn btn-primary btn-close-builder" onclick="<?php echo $onclick; ?>">
            <?php echo $view['translator']->trans('mautic.core.close.builder'); ?>
        </button>
        <button type="button" class="btn btn-primary btn-apply-builder">
            <?php echo $view['translator']->trans('mautic.core.form.apply'); ?>
        </button>
    </div>
    <div class="col-xs-6 text-right">
        <button type="button" class="btn btn-default btn-undo btn-nospin" data-toggle="tooltip" data-placement="left" title="<?php echo $view['translator']->trans('mautic.core.undo'); ?>">
            <span><i class="fa fa-undo"></i></span>
        </button>
        <button type="button" class="btn btn-default btn-redo btn-nospin" data-toggle="tooltip" data-placement="left" title="<?php echo $view['translator']->trans('mautic.core.redo'); ?>">
            <span><i class="fa fa-repeat"></i></span>
        </button>
    </div>
    <div class="col-xs-12 mt-15">
        <div id="builder-errors" class="alert alert-danger" role="alert" style="display: none;"></div>
    </div>
</div>
