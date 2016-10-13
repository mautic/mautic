<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$containerType     = 'pagebreak-wrapper';
$defaultInputClass = 'pagebreak';

include __DIR__.'/field_helper.php';

$backButtonAttr = $nextButtonAttr = $inputAttr;
$classPrefix    = 'btn btn-default mautic-pagebreak-';

$appendAttribute($backButtonAttr, 'class', $classPrefix.'back');
$appendAttribute($nextButtonAttr, 'class', $classPrefix.'next'.((!empty($inForm)) ? ' mr-lg ' : ''));

if (empty($inForm)) {
    $containerAttr .= ' data-mautic-form-pagebreak="'.$fieldPage.'"';

    // Hidden by default and only visible if JS makes it so
    $appendAttribute($containerAttr, 'style', 'display: none;');
}

if (empty(trim($field['properties']['prev_page_label']))) {
    $appendAttribute($backButtonAttr, 'style', 'display: none;');
}

$html = <<<HTML

            <div $containerAttr>
                <button type="button" $backButtonAttr data-mautic-form-pagebreak-button="prev">{$field['properties']['prev_page_label']}</button>
                <button type="button" $nextButtonAttr data-mautic-form-pagebreak-button="next">{$field['properties']['next_page_label']}</button>
            </div>

HTML;

echo $html;
