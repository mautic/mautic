<?php

namespace Mautic\LeadBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\LeadBundle\Entity\Lead;

class LeadUtmTagsEvent extends CommonEvent
{
    /**
     * @param mixed[] $utmtags
     */
    public function __construct(
        Lead $lead,
        protected array $utmtags
    ) {
        $this->entity  = $lead;
    }

    public function getLead(): Lead
    {
        return $this->entity;
    }

    /**
     * Returns the new points.
     *
     * @return mixed[]
     */
    public function getUtmTags(): array
    {
        return $this->utmtags;
    }
}
