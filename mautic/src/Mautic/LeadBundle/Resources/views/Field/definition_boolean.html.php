<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$yes = (isset($yes)) ? $yes : $view['translator']->trans('mautic.core.form.yes');
$no  = (isset($no)) ? $no : $view['translator']->trans('mautic.core.form.no');
?>

<div class="boolean">
    <label class="control-label"><?php echo $view['translator']->trans('mautic.lead.field.form.definition.boolean'); ?></label>
    <div class="row">
        <div class="form-group col-sm-12 col-md-8 col-lg-6">
            <div class="input-group">
                <span class="input-group-addon">
                    <i class="fa fa-lg fa-fw fa-check"></i>
                </span>
                <input type="text" autocomplete="off" class="form-control" name="leadfield[properties][yes]" value="<?php echo $yes; ?>">
            </div>
        </div>
        <div class="form-group col-sm-12 col-md-8 col-lg-6">
            <div class="input-group">
                <span class="input-group-addon">
                    <i class="fa fa-lg fa-fw fa-times"></i>
                </span>
                <input type="text" autocomplete="off" class="form-control" name="leadfield[properties][no]" value="<?php echo $no; ?>">
            </div>
        </div>
    </div>
</div>