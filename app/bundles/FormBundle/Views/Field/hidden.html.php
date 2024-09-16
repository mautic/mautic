<?php

$defaultInputClass = $containerType = 'hidden';

include __DIR__.'/field_helper.php';

if (!empty($inForm)):
$html = <<<HTML
<div $containerAttr>
    <label class="text-muted">{$field['label']}</label>
</div>
HTML;

else:
$html = <<<HTML

                <input $inputAttr type="hidden" />
HTML;
endif;

echo $html;
