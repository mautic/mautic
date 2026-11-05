<?php

namespace Mautic\FormBundle\Helper;

use Mautic\FormBundle\Entity\Submission;

final class PointActionHelper
{
    /**
     * @param array<string, mixed> $action
     */
    public static function validateFormSubmit(Submission $eventDetails, array $action): bool
    {
        $form         = $eventDetails->getForm();
        $formId       = $form->getId();
        $limitToForms = $action['properties']['forms'];

        // no points change
        return empty($limitToForms) || in_array($formId, $limitToForms);
    }
}
