<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$labelAttr = 'id="mauticform_label_' . $field['alias'] . '" for="mauticform_input_' . $field['alias'] . '" ' . $field['labelAttributes'];
if (strpos($labelAttr, 'class') === false)
    $labelAttr .= ' class="mauticform-label"';

$inputAttr = 'id="mauticform_input_' . $field['alias'] . '" '. $field['inputAttributes'];
if (strpos($inputAttr, 'class') === false)
    $inputAttr .= ' class="mauticform-selectbox"';

if (!empty($inForm))
    $inputAttr .= ' disabled="disabled"';

$properties = $field['properties'];

$name = 'mauticform['.$field['alias'].']';
if (!empty($properties['multiple'])) {
    $inputAttr .= ' multiple="multiple"';
    $name .= '[]';
}
$inputAttr .= ' name="'.$name.'"';;

if (!isset($list))
    $list = $properties['list'];

$containerClass  = ($field['isRequired']) ? ' mauticform-required' : '';
$containerClass .= (!empty($deleted)) ? ' bg-danger' : '';
$helpMessage     = $field['helpMessage'];

if ($field['isRequired']) {
    $validationMessage = $field['validationMessage'];
    if (empty($validationMessage))
        $validationMessage = $view['translator']->trans('mautic.form.field.generic.required', array(), 'validators');
}
?>

<div class="mauticform-row mauticform-select<?php echo $containerClass; ?> mauticform-row-<?php echo $field['alias']; ?>" id="mauticform_<?php echo $id; ?>">
    <?php
    if (!empty($inForm))
        echo $view->render('MauticFormBundle:Builder:actions.html.php', array(
            'deleted' => (!empty($deleted)) ? $deleted : false,
            'id'      => $id
        ));
    ?>
    <?php if ($field['showLabel']): ?>
    <label <?php echo $labelAttr; ?>"><?php echo $view->escape($field['label']); ?></label>
    <?php endif; ?>
    <?php if (!empty($helpMessage)): ?>
    <span class="mauticform-helpmessage"><?php echo $helpMessage; ?></span>
    <?php endif; ?>

    <select <?php echo $inputAttr; ?>>
        <?php if (!empty($properties['empty_value'])): ?>
        <option value=""><?php echo $properties['empty_value']; ?></option>
        <?php endif; ?>
        <?php foreach ($list as $l): ?>
        <?php $selected = ($l === $field['defaultValue']) ? ' selected="selected"' : ''; ?>
        <option value="<?php echo $view->escape($l); ?>"<?php echo $selected; ?>><?php echo $view->escape($l); ?></option>
        <?php endforeach; ?>
    </select>
    <?php if (!empty($validationMessage)): ?>
    <span class="mauticform-errormsg" style="display: none;"><?php echo $validationMessage; ?></span>
    <?php endif; ?>
</div>
