<?php
/**
 * @package     Mautic
 * @copyright   2015 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

// Defaults

if (!isset($defaultInputFormClass)) {
    $defaultInputFormClass = '';
}

if (!isset($defaultLabelClass)) {
    $defaultLabelClass = 'label';
}

if (!isset($formName)) {
    $formName = '';
}

$defaultInputClass = 'mauticform-'.$defaultInputClass;
$defaultLabelClass = 'mauticform-'.$defaultLabelClass;

$name  = (empty($ignoreName)) ? ' name="mauticform['.$field['alias'].']"' : '';
$value = (isset($field['defaultValue'])) ? ' value="'.$field['defaultValue'].'"' : ' value=""';
if (empty($ignoreId)) {
    $inputId = 'id="mauticform_input_'.$formName.'_'.$field['alias'].'"';
    $labelId = 'id="mauticform_label_'.$formName.'_'.$field['alias'].'" for="mauticform_input_'.$formName.'_'.$field['alias'].'"';
} else {
    $inputId = $labelId = '';
}

$inputAttr = $inputId.$name.$value;
$labelAttr = $labelId;

$properties = $field['properties'];
if (!empty($properties['placeholder'])) {
    $inputAttr .= ' placeholder="'.$properties['placeholder'].'"';
}

// Label and input
if (!empty($inForm)) {
    $labelAttr .= ' class="'.$defaultLabelClass.'"';
    $inputAttr .= ' disabled="disabled" class="'.$defaultInputClass.$defaultInputFormClass.'"';

} else {
    if ($field['labelAttributes'])
        $labelAttr .= ' '.htmlspecialchars_decode($field['labelAttributes']);

    if (stripos($labelAttr, 'class') === false) {
        $labelAttr .= ' class="'.$defaultLabelClass.'"';
    } else {
        $labelAttr = str_ireplace('class="', 'class="'.$defaultLabelClass.' ', $labelAttr);
    }

    if ($field['inputAttributes'])
        $inputAttr .= ' '.htmlspecialchars_decode($field['inputAttributes']);

    if (stripos($inputAttr, 'class') === false) {
        $inputAttr .= ' class="'.$defaultInputClass.'"';
    } else {
        $inputAttr = str_ireplace('class="', 'class="'.$defaultInputClass.' ', $inputAttr);
    }
}

// Container
$containerAttr         = 'id="mauticform_'.$formName.'_'.$id.'" '.htmlspecialchars_decode($field['containerAttributes']);
if (!isset($containerClass))
    $containerClass = $containerType;
$defaultContainerClass = 'mauticform-row mauticform-'.$containerClass;
$validationMessage     = '';
if (isset($field['isRequired']) && $field['isRequired']) {
    $defaultContainerClass .= ' mauticform-required';
    $validationMessage = $field['validationMessage'];
    if (empty($validationMessage)) {
        $validationMessage = $view['translator']->trans('mautic.form.field.generic.required', array(), 'validators');
    }
} elseif (!empty($required)) {
    $defaultContainerClass .= ' mauticform-required';
}

if (!empty($deleted)) {
    $defaultContainerClass .= ' bg-danger';
}

if (stripos($containerAttr, 'class') === false) {
    $containerAttr .= ' class="'.$defaultContainerClass.'"';
} else {
    $containerAttr = str_ireplace('class="', 'class="'.$defaultContainerClass.' ', $containerAttr);
}
