<?php

namespace Mautic\EmailBundle\Swiftmailer\Momentum\Validator\SwiftMessageValidator;

use Mautic\EmailBundle\Swiftmailer\Momentum\Exception\Validator\SwiftMessageValidator\SwiftMessageValidationException;

/**
 * Interface SwiftMessageValidatorInterface.
 */
interface SwiftMessageValidatorInterface
{
    /**
     * @throws SwiftMessageValidationException
     */
    public function validate(\Swift_Mime_SimpleMessage $message);
}
