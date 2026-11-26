<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Event;

use Mautic\CoreBundle\Event\AbstractEntityEvent;
use Symfony\Contracts\EventDispatcher\Event;

abstract class SaveBatchLeadsEvent extends Event
{
    public function __construct(
        /**
         * @var AbstractEntityEvent[]
         */
        private readonly array $leadsEvents,
    ) {
    }

    /**
     * @return AbstractEntityEvent[]
     */
    public function getLeadsEvents(): array
    {
        return $this->leadsEvents;
    }
}
