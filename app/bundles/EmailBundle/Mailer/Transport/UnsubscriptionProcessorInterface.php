<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Mailer\Transport;

use Mautic\EmailBundle\MonitoredEmail\Exception\UnsubscriptionNotFound;
use Mautic\EmailBundle\MonitoredEmail\Message;
use Mautic\EmailBundle\MonitoredEmail\Processor\Unsubscription\UnsubscribedEmail;

/**
 * Interface InterfaceUnsubscriptionProcessor.
 */
interface UnsubscriptionProcessorInterface
{
    /**
     * Get the email address that unsubscribed.
     *
     * @throws UnsubscriptionNotFound
     */
    public function processUnsubscription(Message $message): UnsubscribedEmail;
}
