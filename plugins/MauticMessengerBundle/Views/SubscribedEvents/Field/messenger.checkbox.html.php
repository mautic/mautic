<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$defaultInputClass = $containerType = 'msgCheckbox';
include __DIR__.'/../../../../../app/bundles/FormBundle/Views/Field/field_helper.php';

$label = (!$field['showLabel']) ? '' :
    <<<HTML

                <h3 $labelAttr>
                    {$field['label']}
                </h3>
HTML;
$scr  = str_replace('http://', 'https://', $view['router']->url('messenger_checkbox_plugin_js'));
$html = <<<HTML

            <div $containerAttr>{$label}
                <div $inputAttr>
                <div class="messengerCheckboxPlugin"></div>
                    <script type="text/javascript" src="{$scr}?formname={$formName}"></script>
                </div>
            </div>

HTML;

echo $html;
