<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$labelAttr = $field['labelAttributes'];
$inputAttr = $field['inputAttributes'];

if (strpos($labelAttr, 'class') === false)
    $labelAttr .= ' class="mauticform-label"';

if (strpos($inputAttr, 'class') === false)
    $inputAttr .= ' class="mauticform-radiogrp-radio"';

if (!empty($inForm))
    $inputAttr .= 'disabled="disabled"';

$properties = $field['properties'];
$list  = $properties['list'];
$count = 0;

$containerClass  = ($field['isRequired']) ? ' mauticform-required' : '';
$containerClass .= (!empty($deleted)) ? ' bg-danger' : '';
$helpMessage    = $field['helpMessage'];

if ($field['isRequired']) {
    $validationMessage = $field['validationMessage'];
    if (empty($validationMessage))
        $validationMessage = $view['translator']->trans('mautic.form.field.generic.required', array(), 'validators');
}
?>

<?php $firstId = 'mauticform_radiogrp_radio_' . $field['alias'] . '_' . \Mautic\CoreBundle\Helper\InputHelper::alphanum($list[0]); ?>
<div class="mauticform-row mauticform-radiogrp<?php echo $containerClass; ?> mauticform-row-<?php echo $field['alias']; ?>" id="mauticform_<?php echo $id; ?>">
    <?php
    if (!empty($inForm))
        echo $view->render('MauticFormBundle:Builder:actions.html.php', array(
            'deleted' => (!empty($deleted)) ? $deleted : false,
            'id'      => $id
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
    <div class="mauticform-radiogrp-row">
        <?php $checked = ($field['defaultValue'] == $l) ? 'checked="checked"' : ''; ?>
        <input <?php echo $inputAttr . ' ' . $checked; ?> id="mauticform_radiogrp_radio_<?php echo $id; ?>" type="radio" name="mauticform[<?php echo $field['alias']; ?>]" value="<?php echo $view->escape($l); ?>" />
        <label class="mauticform-radiogrp-label" id="mauticform_radiogrp_label_<?php echo $id; ?>" for="mauticform_radiogrp_radio_<?php echo $id; ?>"><?php echo $view->escape($l); ?></label>
    </div>
    <?php endforeach; ?>
    <?php if (!empty($validationMessage)): ?>
        <span class="mauticform-errormsg" style="display: none;"><?php echo $validationMessage; ?></span>
    <?php endif; ?>
</div>