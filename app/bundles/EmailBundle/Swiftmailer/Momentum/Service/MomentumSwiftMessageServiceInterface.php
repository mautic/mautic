<?php

namespace Mautic\EmailBundle\Swiftmailer\Momentum\Service;

use Mautic\EmailBundle\Swiftmailer\Momentum\Exception\MomentumSwiftMessageValidationException;

/**
 * Interface MomentumSwiftMessageServiceInterface.
 */
interface MomentumSwiftMessageServiceInterface
{
    /**
     * @param \Swift_Mime_Message $message
     *
     * @return array
     */
    public function getMomentumMessage(\Swift_Mime_Message $message);

    /**
     * @param \Swift_Mime_Message $message
     *
     * @throws MomentumSwiftMessageValidationException
     */
    public function validate(\Swift_Mime_Message $message);
}
