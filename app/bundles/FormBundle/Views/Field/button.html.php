<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
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
