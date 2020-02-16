<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class DoNotContactRemoveEvent extends Event
{
    public const REMOVE_DONOT_CONTACT = 'mautic.lead.remove_donot_contact';

    private $lead;

    private $channel;

    private $persist;

    public function __construct($lead, $channel, $persist = true)
    {
        $this->lead    = $lead;
        $this->channel = $channel;
        $this->persist = $persist;
    }

    /**
     * @return mixed
     */
    public function getLead()
    {
        return $this->lead;
    }

    /**
     * @return mixed
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @return bool
     */
    public function getPersist()
    {
        return $this->persist;
    }
}
