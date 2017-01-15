<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
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
