<?php

$defaultInputClass = 'button';
$containerType     = 'button-wrapper';
include __DIR__.'/field_helper.php';

$buttonType = (isset($properties['type'])) ? $properties['type'] : 'submit';

$html = <<<HTML

            <div $containerAttr>
                <button type="$buttonType" name="mauticform[{$field['alias']}]" $inputAttr value="1">{$field['label']}</button>
            </div>
HTML;

echo $html;
