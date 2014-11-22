<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$value = (isset($value)) ? $value : "";
?>

<div class="lookup">
    <label class="control-label"><?php echo $view['translator']->trans('mautic.lead.field.form.properties.lookup'); ?></label>
    <div class="input-group">
        <input autocomplete="off" name="leadfield[properties][list]" class="form-control" value="<?php echo $value; ?>" type="text" />
        <span class="input-group-addon" data-toggle="tooltip" data-container="body"
              data-placement="top" data-original-title="<?php echo $view['translator']->trans('mautic.lead.field.help.lookup'); ?>">
            <i class="fa fa-question-circle"></i>
        </span>
    </div>
</div>