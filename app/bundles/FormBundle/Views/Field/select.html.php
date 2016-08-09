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

if (!empty($properties['multiple'])) {
    $inputAttr .= ' multiple="multiple"';
}

if (!isset($list)) {
    $list = $properties['list'];
}

if (isset($list['list'])) {
    $list = $list['list'];
}

$formButtons = (!empty($inForm)) ? $view->render('MauticFormBundle:Builder:actions.html.php',
    [
        'id'       => $id,
        'formId'   => $formId,
        'formName' => $formName
    ]) : '';


$label = (!$field['showLabel']) ? '' : <<<HTML

                <label $labelAttr>{$view->escape($field['label'])}</label>
HTML;


$help = (empty($field['helpMessage'])) ? '' : <<<HTML

                <span class="mauticform-helpmessage">{$field['helpMessage']}</span>
HTML;

$emptyOption = (empty($properties['empty_value'])) ? '' : <<<HTML

                    <option value="">{$properties['empty_value']}</option>
HTML;

$options = (!empty($emptyOption)) ? array($emptyOption) :  array();

foreach ($list as $l):
$selected = ($l === $field['defaultValue']) ? ' selected="selected"' : '';
$options[] = <<<HTML

                    <option value="{$view->escape($l)}"{$selected}>{$view->escape($l)}</option>
HTML;
endforeach;

$optionsHtml = implode('', $options);
$html = <<<HTML

            <div $containerAttr>{$formButtons}{$label}{$help}
                <select $inputAttr>$optionsHtml
                </select>
                <span class="mauticform-errormsg" style="display: none;">$validationMessage</span>
            </div>

HTML;

echo $html;
