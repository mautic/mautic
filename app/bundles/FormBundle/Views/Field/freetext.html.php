<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$defaultInputClass = $containerType = 'freetext';
include __DIR__.'/field_helper.php';

$text = html_entity_decode($properties['text']);

$formButtons = (!empty($inForm)) ? $view->render('MauticFormBundle:Builder:actions.html.php',
    array(
        'deleted'  => (!empty($deleted)) ? $deleted : false,
        'id'       => $id,
        'formId'   => $formId,
        'formName' => $formName
    )) : '';

$label = (!$field['showLabel']) ? '' :
<<<HTML

                <h3 $labelAttr id="mauticform_label_{$field['alias']} for="mauticform_input_{$formName}_{$field['alias']}">
                    {$view->escape($field['label'])}
                </h3>
HTML;


$html = <<<HTML

            <div $containerAttr>{$formButtons}{$label}
                <div $inputAttr id="mauticform_input_{$formName}_{$field['alias']}">
                    $text
                </div>
            </div>

HTML;

echo $html;
