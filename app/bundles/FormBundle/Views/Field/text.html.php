<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$type = (isset($type)) ? $type : 'text';

$labelAttr = 'id="mauticform_label_' . $field['alias'] . '" for="mauticform_input_' . $field['alias'] . '"';
$inputAttr = 'id="mauticform_input_' . $field['alias'] . '" name="mauticform['.$field['alias'].']" value="' . $field['defaultValue'] . '"';

if (!empty($inForm)) {
    $labelAttr = 'class="mauticform-label"';
    $inputAttr = 'disabled="disabled" class="mauticform-input"';
} else {
    $labelAttr .= ' ' . $field['labelAttributes'];

    if (strpos($labelAttr, 'class') === false)
        $labelAttr .= ' class="mauticform-label"';

    $inputAttr .= ' ' . $field['inputAttributes'];

    if (strpos($inputAttr, 'class') === false) {
        $inputAttr .= ' class="mauticform-input';
    }
}

$properties     = $field['properties'];
if (!empty($properties['placeholder']))
    $inputAttr .= ' placeholder="' . $properties['placeholder'] . '"';

$helpMessage    = $field['helpMessage'];

$containerClass = '';
if ($field['isRequired']) {
    $containerClass .= ' mauticform-required';
    $validationMessage = $field['validationMessage'];
    if (empty($validationMessage))
        $validationMessage = $view['translator']->trans('mautic.form.field.generic.required', array(), 'validators');
} elseif (!empty($required)) {
    $containerClass .= ' mauticform-required';
}

if (!empty($deleted))
    $containerClass .= ' bg-danger';
?>

<div class="mauticform-row mauticform-<?php echo $type . $containerClass; ?> mauticform-row-<?php echo $field['alias']; ?>" id="mauticform_<?php echo $id; ?>">
    <?php
    if (!empty($inForm))
        echo $view->render('MauticFormBundle:Builder:actions.html.php', array(
            'deleted' => (!empty($deleted)) ? $deleted : false,
            'id'      => $id,
            'formId'  => $formId
        ));
    ?>
    <?php if ($field['showLabel']): ?>
        <label <?php echo $labelAttr; ?>"><?php echo $view->escape($field['label']); ?></label>
    <?php endif; ?>
    <?php if (!empty($helpMessage)): ?>
    <span class="mauticform-helpmessage"><?php echo $helpMessage; ?></span>
    <?php endif; ?>
    <input <?php echo $inputAttr; ?> type="<?php echo $type; ?>" />
    <?php if (!empty($validationMessage)): ?>
        <span class="mauticform-errormsg" style="display: none;"><?php echo $validationMessage; ?></span>
    <?php endif; ?>
</div>
