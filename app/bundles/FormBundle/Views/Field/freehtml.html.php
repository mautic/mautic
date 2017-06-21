<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$defaultInputClass = $containerType = 'freehtml';
include __DIR__.'/field_helper.php';

if ($inBuilder) {
    $htmlContent = $view['content']->showScriptTags($properties['text']);
} else {
    $htmlContent = $properties['text'];
}
// $e = new \Exception;
// var_dump($e->getTraceAsString());die;
$label = (!$field['showLabel']) ? '' :
    <<<HTML

                <h3 $labelAttr>
                    {$field['label']}
                </h3>
HTML;

$html = <<<HTML

            <div $containerAttr>{$label}
                <div $inputAttr>
                    {$htmlContent}
                </div>
            </div>

HTML;

echo $html;
