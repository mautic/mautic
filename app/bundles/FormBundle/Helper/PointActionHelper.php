<?php

namespace Mautic\FormBundle\Helper;

class PointActionHelper
{
    public static function validateFormSubmit($eventDetails, $action): bool
    {
        $form         = $eventDetails->getForm();
        $formId       = $form->getId();
        $limitToForms = $action['properties']['forms'];

        // no points change
        return empty($limitToForms) || in_array($formId, $limitToForms);
    }
}
