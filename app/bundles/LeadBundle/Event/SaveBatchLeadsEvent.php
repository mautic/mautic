<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class SaveBatchLeadsEvent extends Event
{
    public function __construct(
        /**
         * @var LeadEvent[]
         */
        protected array $leadsEvents,
    ) {
    }

    /**
     * @return LeadEvent[]
     */
    public function getLeadsEvents(): array
    {
        return $this->leadsEvents;
    }
}
