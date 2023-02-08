<?php

use Mautic\FormBundle\Collection\MappedObjectCollection;

if (isset($field['defaultValue']) && '' !== $field['defaultValue']) {
    $hiddenDefault = $view->render(
        'MauticFormBundle:Field:hidden.html.php',
        [
            'field'         => $field,
            'fields'        => $fields ?? [],
            'inForm'        => $inForm ?? false,
            'id'            => $id,
            'formId'        => $formId ?? 0,
            'type'          => 'checkbox',
            'formName'      => $formName ?? '',
            'mappedFields'  => $mappedFields ?? new MappedObjectCollection(),
        ]
    );

    echo str_replace('<input', '<input value="'.$field['defaultValue'].'"', $hiddenDefault);
}

echo $view->render(
    'MauticFormBundle:Field:group.html.php',
    [
        'field'         => $field,
        'inForm'        => $inForm ?? false,
        'id'            => $id,
        'formId'        => $formId ?? 0,
        'type'          => 'checkbox',
        'formName'      => $formName ?? '',
        'mappedFields'  => $mappedFields ?? new MappedObjectCollection(),
        'fields'        => $fields ?? null,
    ]
);
