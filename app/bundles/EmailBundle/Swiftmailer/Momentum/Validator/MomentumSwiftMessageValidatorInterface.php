<?php

namespace Mautic\EmailBundle\Swiftmailer\Momentum\Validator;

use Mautic\EmailBundle\Swiftmailer\Momentum\Exception\MomentumSwiftMessageValidationException;

/**
 * Interface MomentumSwiftMessageValidatorInterface.
 */
interface MomentumSwiftMessageValidatorInterface
{
    /**
     * @param \Swift_Mime_Message $message
     *
     * @throws MomentumSwiftMessageValidationException
     */
    public function validate(\Swift_Mime_Message $message);
}
