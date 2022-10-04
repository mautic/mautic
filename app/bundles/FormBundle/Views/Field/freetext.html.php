<?php

$defaultInputClass = $containerType = 'freetext';
include __DIR__.'/field_helper.php';

$label = (!$field['showLabel']) ? '' :
    <<<HTML
    
                <h3 $labelAttr>
                    {$field['label']}
                </h3>
HTML;

$html = <<<HTML

            <div $containerAttr>{$label}
                <div $inputAttr>
                    {$properties['text']}
                </div>
            </div>

HTML;

echo $html;
