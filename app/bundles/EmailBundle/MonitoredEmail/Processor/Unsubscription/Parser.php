<?php

namespace Mautic\EmailBundle\MonitoredEmail\Processor\Unsubscription;

use Mautic\EmailBundle\MonitoredEmail\Exception\UnsubscriptionNotFound;
use Mautic\EmailBundle\MonitoredEmail\Message;

class Parser
{
    public function __construct(
        protected Message $message,
    ) {
    }

    /**
     * @throws UnsubscriptionNotFound
     */
    public function parse(): UnsubscribedEmail
    {
        $unsubscriptionEmail = null;
        foreach ($this->message->to as $to => $name) {
            if (str_contains($to, '+unsubscribe')) {
                $unsubscriptionEmail = $to;

                break;
            }
        }

        if (!$unsubscriptionEmail) {
            throw new UnsubscriptionNotFound();
        }

        return new UnsubscribedEmail($this->message->fromAddress, $unsubscriptionEmail);
    }
}
