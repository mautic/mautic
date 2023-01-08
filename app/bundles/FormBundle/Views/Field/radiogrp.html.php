<?php

echo $view->render(
    'MauticFormBundle:Field:group.html.php',
    [
        'field'         => $field,
        'fields'        => isset($fields) ? $fields : [],
        'inForm'        => (isset($inForm)) ? $inForm : false,
        'id'            => $id,
        'formId'        => (isset($formId)) ? $formId : 0,
        'formName'      => (isset($formName)) ? $formName : '',
        'type'          => 'radio',
        'contactFields' => (isset($contactFields)) ? $contactFields : [],
        'companyFields' => (isset($companyFields)) ? $companyFields : [],
    ]
);
