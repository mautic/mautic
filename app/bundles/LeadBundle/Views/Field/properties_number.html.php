<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

use Symfony\Component\Form\Extension\Core\DataTransformer\NumberToLocalizedStringTransformer;

$roundMode = (isset($roundMode)) ? $roundMode : '';
$precision = (isset($precision)) ? $precision : '';

$options = [
    NumberToLocalizedStringTransformer::ROUND_UP        => 'mautic.lead.field.form.number.roundup',
    NumberToLocalizedStringTransformer::ROUND_DOWN      => 'mautic.lead.field.form.number.rounddown',
    NumberToLocalizedStringTransformer::ROUND_HALF_UP   => 'mautic.lead.field.form.number.roundhalfup',
    NumberToLocalizedStringTransformer::ROUND_HALF_EVEN => 'mautic.lead.field.form.number.roundhalfeven',
    NumberToLocalizedStringTransformer::ROUND_HALF_DOWN => 'mautic.lead.field.form.number.roundhalfdown',
];
?>

<div class="number">
    <div class="row">
        <div class="form-group col-xs-12 col-sm-8 col-md-6">
            <label class="control-label"><?php echo $view['translator']->trans('mautic.lead.field.form.properties.numberrounding'); ?></label>
            <div class="input-group">
                <select class="form-control not-chosen" autocomplete="false" name="leadfield[properties][roundmode]">
                    <?php foreach ($options as $v => $l): ?>
                    <option value="<?php echo $view->escape($v); ?>"<?php if ($roundMode == $v) {
    echo ' selected="selected"';
} ?>><?php echo $view['translator']->trans($l); ?></option>
                    <?php endforeach; ?>
                </select>

                <span class="input-group-addon" data-toggle="tooltip" data-container="body"
                      data-placement="top" data-original-title="<?php echo $view['translator']->trans('mautic.lead.field.help.numberrounding'); ?>">
                    <i class="fa fa-question-circle"></i>
                </span>
            </div>
        </div>
        <div class="form-group col-xs-12 col-sm-8 col-md-6">
            <label class="control-label"><?php echo $view['translator']->trans('mautic.lead.field.form.properties.numberprecision'); ?></label>
            <div class="input-group">
                <input autocomplete="false" name="leadfield[properties][precision]" class="form-control" value="<?php echo $view->escape($precision); ?>" type="number" />
                <span class="input-group-addon" data-toggle="tooltip" data-container="body"
                      data-placement="top" data-original-title="<?php echo $view['translator']->trans('mautic.lead.field.help.numberprecision'); ?>">
                    <i class="fa fa-question-circle"></i>
                </span>
            </div>
        </div>
    </div>
</div>
