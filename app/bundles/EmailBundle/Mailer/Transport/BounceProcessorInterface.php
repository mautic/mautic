<?php

namespace Mautic\EmailBundle\Mailer\Transport;

use Mautic\EmailBundle\MonitoredEmail\Exception\BounceNotFound;
use Mautic\EmailBundle\MonitoredEmail\Message;
use Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\BouncedEmail;

/**
 * Interface InterfaceBounceProcessor.
 */
interface BounceProcessorInterface
{
    /**
     * Get the email address that bounced.
     *
     * @throws BounceNotFound
     */
    public function processBounce(Message $message): BouncedEmail;
}
