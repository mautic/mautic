<?php

namespace Mautic\LeadBundle\Event;

use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Contracts\EventDispatcher\Event;

class ChannelSubscriptionChange extends Event
{
    /**
     * @param string $channel
     */
    public function __construct(private Lead $lead, private $channel, private int $oldStatus, private int $newStatus)
    {
    }

    /**
     * @return Lead
     */
    public function getLead()
    {
        return $this->lead;
    }

    /**
     * @return string
     */
    public function getChannel()
    {
        return $this->channel;
    }

    public function getOldStatus(): int
    {
        return $this->oldStatus;
    }

    /**
     * @return string
     */
    public function getOldStatusVerb()
    {
        return $this->getDncReasonVerb($this->oldStatus);
    }

    public function getNewStatus(): int
    {
        return $this->newStatus;
    }

    /**
     * @return string
     */
    public function getNewStatusVerb()
    {
        return $this->getDncReasonVerb($this->newStatus);
    }

    /**
     * @return string
     */
    private function getDncReasonVerb($reason)
    {
        return match (true) {
            DoNotContact::IS_CONTACTABLE === $reason => 'contactable',
            DoNotContact::BOUNCED === $reason        => 'bounced',
            DoNotContact::MANUAL === $reason         => 'manual',
            default                                  => 'unsubscribed',
        };
    }
}
