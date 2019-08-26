<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

if (isset($field['defaultValue']) && '' !== $field['defaultValue']) {
    $hiddenDefault = $view->render(
        'MauticFormBundle:Field:hidden.html.php',
        [
            'field'         => $field,
            'inForm'        => (isset($inForm)) ? $inForm : false,
            'id'            => $id,
            'formId'        => (isset($formId)) ? $formId : 0,
            'type'          => 'checkbox',
            'formName'      => (isset($formName)) ? $formName : '',
            'contactFields' => (isset($contactFields)) ? $contactFields : [],
            'companyFields' => (isset($companyFields)) ? $companyFields : [],
        ]
    );

    echo str_replace('<input', '<input value="'.$field['defaultValue'].'"', $hiddenDefault);
}

echo $view->render(
    'MauticFormBundle:Field:group.html.php',
    [
        'field'         => $field,
        'inForm'        => (isset($inForm)) ? $inForm : false,
        'id'            => $id,
        'formId'        => (isset($formId)) ? $formId : 0,
        'type'          => 'checkbox',
        'formName'      => (isset($formName)) ? $formName : '',
        'contactFields' => (isset($contactFields)) ? $contactFields : [],
        'companyFields' => (isset($companyFields)) ? $companyFields : [],
    ]
);
