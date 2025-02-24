<?php

namespace Mautic\FormBundle\Helper;

class PointActionHelper
{
    public static function validateFormSubmit($eventDetails, $action): bool
    {
        $form         = $eventDetails->getForm();
        $formId       = $form->getId();
        $limitToForms = $action['properties']['forms'];

        if (!empty($limitToForms) && !in_array($formId, $limitToForms)) {
            // no points change
            return false;
        }

        return true;
    }
}
