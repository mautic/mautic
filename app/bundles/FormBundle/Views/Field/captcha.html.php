<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$required = true;
$type     = 'text';
if (empty($field['properties']['captcha'])) {
    $required = false;
    if (empty($inForm)) {
        // Use as a honeypot
        $field['containerAttributes'] .= ' style="display: none;"';
    } else {
        // Hide the input
        $type = 'hidden';
    }
}

echo $view->render(
    'MauticFormBundle:Field:text.html.php',
    [
        'field'    => $field,
        'inForm'   => (isset($inForm)) ? $inForm : false,
        'type'     => $type,
        'id'       => $id,
        'required' => $required,
        'formId'   => (isset($formId)) ? $formId : 0,
        'formName' => (isset($formName)) ? $formName : '',
    ]
);
