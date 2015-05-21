<?php
/**
 * @package     Mautic
 * @copyright   2015 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

// Defaults

if (!isset($defaultInputFormClass))
    $defaultInputFormClass = '';

if (!isset($defaultLabelClass)) {
    $defaultLabelClass = 'label';
}

$defaultInputClass = 'mauticform-' . $defaultInputClass;
$defaultLabelClass = 'mauticform-' . $defaultLabelClass;

$name  = (empty($ignoreName)) ? ' name="mauticform['.$field['alias'].']"' : '';
$value = (isset($field['defaultValue'])) ? ' value="' . $field['defaultValue'] . '"' : '';
if (empty($ignoreId)) {
    $inputId = 'id="mauticform_input_' . $field['alias'] . '"';
    $labelId = 'id="mauticform_label_' . $field['alias'] . '" for="mauticform_input_' . $field['alias'] . '"';
} else {
    $inputId = $labelId = '';
}

$inputAttr = $inputId . $name . $value;
$labelAttr = $labelId;

$properties = $field['properties'];
if (!empty($properties['placeholder']))
    $inputAttr .= ' placeholder="' . $properties['placeholder'] . '"';

// Label and input
if (!empty($inForm)) {
    $labelAttr .= ' class="' . $defaultLabelClass . '"';
    $inputAttr .= ' disabled="disabled" class="' . $defaultInputClass . $defaultInputFormClass . '"';

} else {
    $labelAttr .= ' ' . $field['labelAttributes'];
    if (strpos($labelAttr, 'class') === false) {
        $labelAttr .= ' class="' . $defaultLabelClass . '"';
    } else {
        $labelAttr = str_ireplace('class="', 'class="' . $defaultLabelClass . ' ', $labelAttr);
    }

    $inputAttr .= ' ' . $field['inputAttributes'];
    if (strpos($inputAttr, 'class') === false) {
        $inputAttr .= ' class="' . $defaultInputClass . '"';
    } else {
        $inputAttr = str_ireplace('class="', 'class="' . $defaultInputClass . ' ', $inputAttr);
    }
}

// Container
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

$containerAttr  = 'class="mauticform-row mauticform-' . $containerType . $containerClass . '" id="mauticform_' . $id . '"';