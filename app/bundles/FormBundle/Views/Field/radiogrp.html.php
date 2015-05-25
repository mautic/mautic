<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$defaultInputClass = 'radiogrp-radio';
$containerType     = 'radiogrp';
$ignoreId          = true;

include __DIR__ . '/field_helper.php';

$list  = (isset($properties['optionlist'])) ? $properties['optionlist']['list'] : $properties['list'];

$optionLabelAttr = (isset($properties['labelAttributes'])) ? $properties['labelAttributes'] : '';
$wrapDiv         = true;

$defaultOptionLabelClass = 'mauticform-radiogrp-label';
if (stripos($optionLabelAttr, 'class') === false) {
    $optionLabelAttr .= ' class="' . $defaultOptionLabelClass . '"';
} else {
    $optionLabelAttr = str_ireplace('class="', 'class="' . $defaultOptionLabelClass . ' ', $optionLabelAttr);
    $wrapDiv         = false;
}

$count = 0;
$firstId = 'mauticform_radiogrp_radio_' . $field['alias'] . '_' . \Mautic\CoreBundle\Helper\InputHelper::alphanum($list[0]);

?>
<div <?php echo $containerAttr; ?>>
    <?php
    if (!empty($inForm))
        echo $view->render('MauticFormBundle:Builder:actions.html.php', array(
            'deleted' => (!empty($deleted)) ? $deleted : false,
            'id'      => $id,
            'formId'  => $formId
        ));
    ?>
    <?php if ($field['showLabel']): ?>
    <label <?php echo $labelAttr; ?> for="<?php echo $firstId; ?>"><?php echo $view->escape($field['label']); ?></label>
    <?php endif; ?>
    <?php if (!empty($helpMessage)): ?>
    <span class="mauticform-helpmessage"><?php echo $helpMessage; ?></span>
    <?php endif; ?>
    <?php foreach($list as $l): ?>
    <?php $id = $field['alias'] . '_' . \Mautic\CoreBundle\Helper\InputHelper::alphanum($l); ?>

    <?php if ($wrapDiv): ?>
    <div class="mauticform-radiogrp-row">
    <?php endif; ?>

        <label id="mauticform_radiogrp_label_<?php echo $id; ?>" for="mauticform_radiogrp_radio_<?php echo $id; ?>" <?php echo $optionLabelAttr; ?>>
            <?php $checked = ($field['defaultValue'] == $l) ? 'checked="checked"' : ''; ?>
            <input <?php echo $inputAttr . ' ' . $checked; ?> id="mauticform_radiogrp_radio_<?php echo $id; ?>" type="radio" value="<?php echo $view->escape($l); ?>" />
            <?php echo $view->escape($l); ?>
        </label>

    <?php if ($wrapDiv): ?>
    </div>
    <?php endif;?>

    <?php endforeach; ?>
    <?php if (!empty($validationMessage)): ?>
        <span class="mauticform-errormsg" style="display: none;"><?php echo $validationMessage; ?></span>
    <?php endif; ?>
</div>