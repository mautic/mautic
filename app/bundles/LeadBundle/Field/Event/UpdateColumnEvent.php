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

    public function getLeadField(): LeadField
    {
        return $this->leadField;
    }

    public function shouldProcessInBackground(): bool
    {
        return $this->shouldProcessInBackground;
    }
}
