<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$defaultInputFormClass = ' not-chosen';
$defaultInputClass     = 'selectbox';
$containerType         = 'select';

include __DIR__.'/field_helper.php';

if (!empty($properties['multiple'])) {
    $inputAttr .= ' multiple="multiple"';
}

$label = (!$field['showLabel']) ? '' : <<<HTML

                <label $labelAttr>{$field['label']}</label>
HTML;

$help = (empty($field['helpMessage'])) ? '' : <<<HTML

                <span class="mauticform-helpmessage">{$field['helpMessage']}</span>
HTML;

$emptyOption = '';
if ((!empty($properties['empty_value']) || empty($field['defaultValue']) && empty($properties['multiple']))):
    $emptyOption = <<<HTML

                    <option value="">{$properties['empty_value']}</option>
HTML;
endif;

$options = (!empty($emptyOption)) ? [$emptyOption] : [];

$optionBuilder = function (array $list) use (&$optionBuilder, $field, $view) {
    $html = '';
    foreach ($list as $listValue => $listLabel):
        if (is_array($listLabel)) {
            // This is an option group
            $html .= <<<HTML

                    <optgroup label="$listValue">
                    {$optionBuilder($listLabel)}
                    </optgroup>

HTML;

            continue;
        }

    $selected  = ($listValue === $field['defaultValue']) ? ' selected="selected"' : '';
    $html .= <<<HTML
                    <option value="{$view->escape($listValue)}"{$selected}>{$view->escape($listLabel)}</option>
HTML;
    endforeach;

    return $html;
};

$optionsHtml = $optionBuilder($list);
$html        = <<<HTML

            <div $containerAttr>{$label}{$help}
                <select $inputAttr>$optionsHtml
                </select>
                <span class="mauticform-errormsg" style="display: none;">$validationMessage</span>
            </div>

HTML;

echo $html;
