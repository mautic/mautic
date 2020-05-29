<?php

echo $view->render(
    'MauticFormBundle:Field:text.html.php',
    [
        'field'    => $field,
        'fields'   => $fields ?? [],
        'inForm'   => $inForm ?? false,
        'type'     => 'email',
        'id'       => $id,
        'deleted'  => !empty($deleted),
        'formId'   => $formId ?? 0,
        'formName' => $formName ?? '',
    ]
);
