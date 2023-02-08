<?php

use Mautic\FormBundle\Collection\MappedObjectCollection;

echo $view->render(
    'MauticFormBundle:Field:group.html.php',
    [
        'field'         => $field,
        'fields'        => $fields ?? [],
        'inForm'        => $inForm ?? false,
        'id'            => $id,
        'formId'        => $formId ?? 0,
        'formName'      => $formName ?? '',
        'type'          => 'radio',
        'mappedFields'  => $mappedFields ?? new MappedObjectCollection(),
    ]
);
