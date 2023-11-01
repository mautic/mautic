<?php

namespace Mautic\FormBundle\Helper;

/**
 * Class PointActionHelper.
 */
class PointActionHelper
{
    /**
     * @param $eventDetails
     * @param $action
     *
     * @return int
     */
    public static function validateFormSubmit($eventDetails, $action)
    {
        $form         = $eventDetails->getForm();
        $formId       = $form->getId();
        $limitToForms = $action['properties']['forms'];

        if (!empty($limitToForms) && !in_array($formId, $limitToForms)) {
            //no points change
            return false;
        }

        return true;
    }
}
