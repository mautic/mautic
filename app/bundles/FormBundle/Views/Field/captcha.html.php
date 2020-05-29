<?php

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
        'fields'   => $fields ?? [],
        'inForm'   => $inForm ?? false,
        'type'     => $type,
        'id'       => $id,
        'required' => $required,
        'formId'   => $formId ?? 0,
        'formName' => $formName ?? '',
    ]
);
