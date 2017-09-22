<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

use Mautic\CoreBundle\Helper\InputHelper;

$containerType     = "{$type}grp";
$defaultInputClass = "{$containerType}-{$type}";
$ignoreId          = true;
$ignoreName        = ($type == 'checkbox');

include __DIR__.'/field_helper.php';

$optionLabelAttr = (isset($properties['labelAttributes'])) ? $properties['labelAttributes'] : '';
$wrapDiv         = true;

$defaultOptionLabelClass = 'mauticform-'.$containerType.'-label';
if (stripos($optionLabelAttr, 'class') === false) {
    $optionLabelAttr .= ' class="'.$defaultOptionLabelClass.'"';
} else {
    $optionLabelAttr = str_ireplace('class="', 'class="'.$defaultOptionLabelClass.' ', $optionLabelAttr);
    $wrapDiv         = false;
}

$count   = 0;
$firstId = 'mauticform_'.$containerType.'_'.$type.'_'.$field['alias'].'_'.InputHelper::alphanum(InputHelper::transliterate($firstListValue)).'1';

$label = (!$field['showLabel']) ? '' : <<<HTML

                <label $labelAttr for="$firstId">{$field['label']}</label>
HTML;

$help = (empty($field['helpMessage'])) ? '' : <<<HTML

                <span class="mauticform-helpmessage">{$field['helpMessage']}</span>
HTML;

$options = [];
$counter = 0;
foreach ($list as $listValue => $listLabel):

$id               = $field['alias'].'_'.InputHelper::alphanum(InputHelper::transliterate($listValue)).$counter;
$checked          = ($field['defaultValue'] === $listValue) ? 'checked="checked"' : '';
$checkboxBrackets = ($type == 'checkbox') ? '[]' : '';

$option = <<<HTML

                    <label id="mauticform_{$containerType}_label_{$id}" for="mauticform_{$containerType}_{$type}_{$id}" {$optionLabelAttr}>
                        <input {$inputAttr}{$checked} name="mauticform[{$field['alias']}]{$checkboxBrackets}" id="mauticform_{$containerType}_{$type}_{$id}" type="{$type}" value="{$view->escape($listValue)}" />
                        $listLabel
                    </label>
HTML;

if ($wrapDiv):
$option = <<<HTML

                <div class="mauticform-{$containerType}-row">$option
                </div>
HTML;
endif;

$options[] = $option;
++$counter;
endforeach;

$optionHtml = implode('', $options);

$html = <<<HTML

            <div $containerAttr>{$label}{$help}{$optionHtml}
                <span class="mauticform-errormsg" style="display: none;">$validationMessage</span>
            </div>

HTML;

echo $html;
