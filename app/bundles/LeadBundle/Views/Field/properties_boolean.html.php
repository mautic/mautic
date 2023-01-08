<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$yes = (isset($yes)) ? $yes : $view['translator']->trans('mautic.core.form.yes');
$no  = (isset($no)) ? $no : $view['translator']->trans('mautic.core.form.no');
?>

<div class="boolean">
    <label class="control-label"><?php echo $view['translator']->trans('mautic.lead.field.form.properties.boolean'); ?></label>
    <div class="row">
        <div class="form-group col-xs-12 col-sm-8 col-md-6">
            <div class="input-group">
                <span class="input-group-addon text-danger">
                    <i class="fa fa-lg fa-fw fa-times"></i>
                </span>
                <input type="text" autocomplete="false" class="form-control" name="leadfield[properties][no]" value="<?php echo $view->escape($no); ?>" onkeyup="Mautic.updateLeadFieldBooleanLabels(this, 0);">
            </div>
        </div>
        <div class="form-group col-xs-12 col-sm-8 col-md-6">
            <div class="input-group">
                <span class="input-group-addon text-success">
                    <i class="fa fa-lg fa-fw fa-check"></i>
                </span>
                <input type="text" autocomplete="false" class="form-control" name="leadfield[properties][yes]" value="<?php echo $view->escape($yes); ?>" onkeyup="Mautic.updateLeadFieldBooleanLabels(this, 1);">
            </div>
        </div>
    </div>
</div>
