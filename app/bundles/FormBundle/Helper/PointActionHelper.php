<?php

namespace Mautic\FormBundle\Helper;

final class PointActionHelper
{
    /**
     * @param array<string, mixed> $action
     */
    public static function validateFormSubmit($eventDetails, array $action): bool
    {
        $form         = $eventDetails->getForm();
        $formId       = $form->getId();
        $limitToForms = $action['properties']['forms'];

        // no points change
        return empty($limitToForms) || in_array($formId, $limitToForms);
    }
}
