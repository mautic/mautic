<?php

use Mautic\FormBundle\Collection\FieldCollection;
use Mautic\FormBundle\Exception\FieldNotFoundException;

/** @var \Mautic\FormBundle\Collection\MappedObjectCollection $mappedFields */

// Defaults
$appendAttribute = function (&$attributes, $attributeName, $append) {
    if (false === stripos($attributes, "{$attributeName}=")) {
        $attributes .= ' '.$attributeName.'="'.$append.'"';
    } else {
        $attributes = str_ireplace($attributeName.'="', $attributeName.'="'.$append.' ', $attributes);
    }
};

if (!isset($defaultInputFormClass)) {
    $defaultInputFormClass = '';
}

if (!isset($defaultLabelClass)) {
    $defaultLabelClass = 'label';
}

if (!isset($formName)) {
    $formName = '';
}

$properties = $field['properties'];

$defaultInputClass = 'mauticform-'.$defaultInputClass;
$defaultLabelClass = 'mauticform-'.$defaultLabelClass;

$name = '';
if (empty($ignoreName)) {
    $inputName = 'mauticform['.$field['alias'].']';
    if (!empty($properties['multiple'])) {
        $inputName .= '[]';
    }
    $name = ' name="'.$inputName.'"';
}

if (in_array($field['type'], ['checkboxgrp', 'radiogrp', 'textarea'])) {
    $value = '';
} else {
    $value = (isset($field['defaultValue'])) ? ' value="'.$field['defaultValue'].'"' : ' value=""';
}

if (empty($ignoreId)) {
    $inputId = 'id="mauticform_input'.$formName.'_'.$field['alias'].'"';
    $labelId = 'id="mauticform_label'.$formName.'_'.$field['alias'].'" for="mauticform_input'.$formName.'_'.$field['alias'].'"';
} else {
    $inputId = $labelId = '';
}

$inputAttr = $inputId.$name.$value;
$labelAttr = $labelId;

if (!empty($properties['placeholder'])) {
    $inputAttr .= ' placeholder="'.$properties['placeholder'].'"';
}

// Label and input
if (!empty($inForm)) {
    if (in_array($field['type'], ['button', 'pagebreak'])) {
        $defaultInputFormClass .= ' btn btn-default';
    }
    $labelAttr .= ' class="'.$defaultLabelClass.'"';
    $inputAttr .= ' disabled="disabled" class="'.$defaultInputClass.$defaultInputFormClass.'"';
} else {
    if ($field['labelAttributes']) {
        $labelAttr .= ' '.htmlspecialchars_decode($field['labelAttributes']);
    }

    $appendAttribute($labelAttr, 'class', $defaultLabelClass);

    if ($field['inputAttributes']) {
        $inputAttr .= ' '.htmlspecialchars_decode($field['inputAttributes']);
    }

    $appendAttribute($inputAttr, 'class', $defaultInputClass);
}

// Container
$containerAttr = 'id="mauticform'.$formName.'_'.$id.'" '.htmlspecialchars_decode($field['containerAttributes']);

if (!isset($containerClass)) {
    $containerClass = $containerType;
}
$order                 = (isset($field['order'])) ? $field['order'] : 0;
$defaultContainerClass = 'mauticform-row mauticform-'.$containerClass.' mauticform-field-'.$order;

if ($field['parent'] && isset($fields[$field['parent']])) {
    $values = implode('|', $field['conditions']['values']);

    if (!empty($field['conditions']['any']) && 'notIn' != $field['conditions']['expr']) {
        $values = '*';
    }

    $containerAttr .= " data-mautic-form-show-on=\"{$fields[$field['parent']]->getAlias()}:".$values.'" data-mautic-form-expr="'.$field['conditions']['expr'].'"';

    $defaultContainerClass .= '  mauticform-field-hidden';
}

// Field is required
$validationMessage = '';
if (isset($field['isRequired']) && $field['isRequired']) {
    $required = true;
    $defaultContainerClass .= ' mauticform-required';
    $validationMessage = $field['validationMessage'];
    if (empty($validationMessage)) {
        $validationMessage = $view['translator']->trans('mautic.form.field.generic.required', [], 'validators');
    }

    $containerAttr .= " data-validate=\"{$field['alias']}\" data-validation-type=\"{$field['type']}\"";

    if (!empty($properties['multiple'])) {
        $containerAttr .= ' data-validate-multiple="true"';
    }
} elseif (!empty($required)) {
    // Forced to be required
    $defaultContainerClass .= ' mauticform-required';
}

$appendAttribute($containerAttr, 'class', $defaultContainerClass);

// Setup list parsing
if (isset($list) || isset($properties['syncList']) || isset($properties['list']) || isset($properties['optionlist'])) {
    $parseList     = [];
    $isBooleanList = false;

    if (!empty($properties['syncList']) && !empty($field['mappedField']) && !empty($field['mappedObject']) && $mappedFields->offsetExists($field['mappedObject'])) {
        /** @var FieldCollection $fieldCollection */
        $fieldCollection = $mappedFields->offsetGet($field['mappedObject']);

        try {
            $mappedField     = $fieldCollection->getFieldByKey($field['mappedField']);
            $mappedFieldType = $mappedField->getType();
            switch (true) {
                case !empty($mappedField->getProperties()['list']):
                    $parseList = $mappedField->getProperties()['list'];
                    break;
                case 'boolean' === $mappedFieldType:
                    $parseList = [
                        0 => $mappedField->getProperties()['no'],
                        1 => $mappedField->getProperties()['yes'],
                    ];
                    $isBooleanList = true;
                    break;
                case 'country' === $mappedFieldType:
                    $list = \Mautic\LeadBundle\Helper\FormFieldHelper::getCountryChoices();
                    break;
                case 'region' === $mappedFieldType:
                    $list = \Mautic\LeadBundle\Helper\FormFieldHelper::getRegionChoices();
                    break;
                case 'timezone' === $mappedFieldType:
                    $list = \Mautic\LeadBundle\Helper\FormFieldHelper::getTimezonesChoices();
                    break;
                case 'locale':
                    $list = \Mautic\LeadBundle\Helper\FormFieldHelper::getLocaleChoices();
                    break;
            }
        } catch (FieldNotFoundException $e) {
        }
    }

    if (empty($parseList)) {
        if (isset($list)) {
            $parseList = $list;
        } elseif (!empty($properties['optionlist'])) {
            $parseList = $properties['optionlist'];
        } elseif (!empty($properties['list'])) {
            $parseList = $properties['list'];
        }

        if (isset($parseList['list'])) {
            $parseList = $parseList['list'];
        }
    }

    if ($field['mappedField'] && $mappedFields->offsetExists($field['mappedObject'])) {
        /** @var FieldCollection $fieldCollection */
        $fieldCollection = $mappedFields->offsetGet($field['mappedObject']);

        try {
            $mappedField = $fieldCollection->getFieldByKey($field['mappedField']);
            if (in_array($mappedField->getType(), ['datetime', 'date'])) {
                foreach ($parseList as $key => $aTemp) {
                    if ($date = ('datetime' === $mappedField->getType() ? $view['date']->toFull($aTemp['label']) : $view['date']->toDate($aTemp['label']))) {
                        $parseList[$key]['label'] = $date;
                    }
                }
            }
        } catch (FieldNotFoundException $e) {
        }
    }

    $list = $isBooleanList
        ?
        \Mautic\FormBundle\Helper\FormFieldHelper::parseBooleanList($parseList)
        :
        \Mautic\FormBundle\Helper\FormFieldHelper::parseList($parseList);

    $firstListValue = reset($list);
}
