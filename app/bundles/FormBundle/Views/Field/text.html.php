<?php

$containerType     = (isset($type)) ? $type : 'text';
$defaultInputClass = (isset($inputClass)) ? $inputClass : 'input';

include __DIR__.'/field_helper.php';

$label = (!$field['showLabel']) ? '' : <<<HTML

                <label $labelAttr>{$field['label']}</label>
HTML;

$help = (empty($field['helpMessage'])) ? '' : <<<HTML

                <span class="mauticform-helpmessage">{$field['helpMessage']}</span>
HTML;

if ('textarea' == $containerType):
$textInput = <<<HTML

                <textarea $inputAttr>{$field['defaultValue']}</textarea>
HTML;

else:
$textInput = <<<HTML

                <input {$inputAttr} type="$containerType" />
HTML;
endif;

$html = <<<HTML

            <div $containerAttr>{$label}{$help}{$textInput}
                <span class="mauticform-errormsg" style="display: none;">$validationMessage</span>
            </div>

HTML;

echo $html;
