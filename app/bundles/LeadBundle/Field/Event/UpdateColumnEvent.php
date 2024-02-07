<?php

namespace Mautic\LeadBundle\Field\Event;

use Mautic\LeadBundle\Entity\LeadField;
use Symfony\Contracts\EventDispatcher\Event;

class UpdateColumnEvent extends Event
{
    public function __construct(
        private LeadField $leadField,
        private bool $shouldProcessInBackground
    ) {
    }

    /**
     * @return LeadField
     */
    public function getLeadField()
    {
        return $this->leadField;
    }

    /**
     * @return bool
     */
    public function shouldProcessInBackground()
    {
        return $this->shouldProcessInBackground;
    }
}
