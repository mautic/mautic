<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
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
