<?php

declare(strict_types=1);

echo $view->render(
    'MauticFormBundle:Field:text.html.php',
    [
        'field'    => $field,
        'fields'   => isset($fields) ? $fields : [],
        'inForm'   => (isset($inForm)) ? $inForm : false,
        'type'     => 'companyLookup',
        'id'       => $id,
        'deleted'  => !empty($deleted),
        'formId'   => (isset($formId)) ? $formId : 0,
        'formName' => (isset($formName)) ? $formName : '',
    ]
);
