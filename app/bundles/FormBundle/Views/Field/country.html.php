<?php

echo $view->render(
    'MauticFormBundle:Field:select.html.php',
    [
        'field'        => $field,
        'fields'       => $fields ?? [],
        'mappedFields' => $mappedFields,
        'inForm'       => $inForm ?? false,
        'list'         => \Mautic\LeadBundle\Helper\FormFieldHelper::getCountryChoices(),
        'id'           => $id,
        'formId'       => $formId ?? 0,
        'formName'     => $formName ?? '',
    ]
);
