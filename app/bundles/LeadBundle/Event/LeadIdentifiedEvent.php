<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Event;

use Mautic\LeadBundle\Entity\Lead;

final class LeadIdentifiedEvent extends LeadEvent
{
    /**
     * @param mixed[]|bool $changes changes already read from the saved contact, as the entity resets them once read
     */
    public function __construct(Lead $lead, bool $isNew, array|bool $changes)
    {
        parent::__construct($lead, $isNew);
        $this->changes = $changes;
    }
}
