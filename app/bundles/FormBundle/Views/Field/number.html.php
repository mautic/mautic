<?php

echo $view->render(
    'MauticFormBundle:Field:text.html.php',
    [
        'field'    => $field,
        'fields'   => $fields ?? [],
        'inForm'   => $inForm ?? false,
        'type'     => 'number',
        'id'       => $id,
        'formId'   => $formId ?? 0,
        'formName' => $formName ?? '',
    ]
);
