<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$defaultInputClass = 'button';
$containerType = 'button-wrapper';
include __DIR__ . '/field_helper.php';

$buttonType = (isset($properties['type'])) ? $properties['type'] : 'submit';

$formButtons = (!empty($inForm)) ? $view->render('MauticFormBundle:Builder:actions.html.php',
    [
        'id'             => $id,
        'formId'         => $formId,
        'formName'       => $formName,
        'disallowDelete' => true
    ]) : '';



$html = <<<HTML

            <div $containerAttr>$formButtons
                <button type="$buttonType" name="mauticform[{$field['alias']}]" $inputAttr value="1">{$field['label']}</button>
            </div>
HTML;

echo $html;
