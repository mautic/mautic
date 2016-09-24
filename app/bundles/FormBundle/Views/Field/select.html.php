<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$defaultInputFormClass = ' not-chosen';
$defaultInputClass     = 'selectbox';
$containerType         = 'select';

include __DIR__.'/field_helper.php';

$parseList = [];
if (!empty($properties['syncList']) && !empty($field['leadField']) && isset($contactFields[$field['leadField']])) {
    $leadFieldType = $contactFields[$field['leadField']]['type'];
    switch (true) {
        case (!empty($contactFields[$field['leadField']]['properties']['list'])):
            $parseList = $contactFields[$field['leadField']]['properties']['list'];
            break;
        case ('country' == $leadFieldType):
            $list = \Mautic\LeadBundle\Helper\FormFieldHelper::getCountryChoices();
            break;
        case ('region' == $leadFieldType):
            $list = \Mautic\LeadBundle\Helper\FormFieldHelper::getRegionChoices();
            break;
        case ('timezone' == $leadFieldType):
            $list = \Mautic\LeadBundle\Helper\FormFieldHelper::getTimezonesChoices();
            break;
        case ('locale'):
            $list = \Mautic\LeadBundle\Helper\FormFieldHelper::getLocaleChoices();
            break;
    }
}

if (empty($parseList)) {
    if (isset($list)) {
        $parseList = $list;
    } elseif (!empty($properties['list'])) {
        $parseList = $properties['list'];
    }

    if (isset($parseList['list'])) {
        $parseList = $parseList['list'];
    }
}

if (!empty($properties['multiple'])) {
    $inputAttr .= ' multiple="multiple"';
}

$list = \Mautic\FormBundle\Helper\FormFieldHelper::parseList($parseList);
$firstListValue  = reset($list);

$label = (!$field['showLabel']) ? '' : <<<HTML

                <label $labelAttr>{$field['label']}</label>
HTML;


$help = (empty($field['helpMessage'])) ? '' : <<<HTML

                <span class="mauticform-helpmessage">{$field['helpMessage']}</span>
HTML;

$emptyOption = '';
if (!empty($properties['empty_value']) || empty($field['defaultValue'])):
    $emptyOption = <<<HTML

                    <option value="">{$properties['empty_value']}</option>
HTML;
endif;

$options = (!empty($emptyOption)) ? array($emptyOption) :  array();

foreach ($list as $listValue => $listLabel):
$selected = ($listValue === $field['defaultValue']) ? ' selected="selected"' : '';
$options[] = <<<HTML

                    <option value="{$view->escape($listValue)}"{$selected}>{$view->escape($listLabel)}</option>
HTML;
endforeach;

$optionsHtml = implode('', $options);
$html = <<<HTML

            <div $containerAttr>{$label}{$help}
                <select $inputAttr>$optionsHtml
                </select>
                <span class="mauticform-errormsg" style="display: none;">$validationMessage</span>
            </div>

HTML;

echo $html;
