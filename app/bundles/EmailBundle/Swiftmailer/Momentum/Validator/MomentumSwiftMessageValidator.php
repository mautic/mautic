<?php

namespace Mautic\EmailBundle\Swiftmailer\Momentum\Validator;

use Mautic\EmailBundle\Swiftmailer\Momentum\Exception\MomentumSwiftMessageValidationException;

/**
 * Class MomentumSwiftMessageValidator.
 */
final class MomentumSwiftMessageValidator implements MomentumSwiftMessageValidatorInterface
{
    /**
     * @param \Swift_Mime_Message $message
     *
     * @throws MomentumSwiftMessageValidationException
     */
    public function validate(\Swift_Mime_Message $message)
    {
    }
}
