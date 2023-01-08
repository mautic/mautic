<?php

$defaultInputClass = $containerType = 'freehtml';
include __DIR__.'/field_helper.php';

if ($inBuilder) {
    $htmlContent = $view['content']->showScriptTags($properties['text']);
} else {
    $htmlContent = $properties['text'];
}

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
