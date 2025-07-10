<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Event;

use Mautic\EmailBundle\Entity\Stat;
use Symfony\Contracts\EventDispatcher\Event;

final class EmailStatEvent extends Event
{
    /**
     * @param Stat[] $stats
     */
    public function __construct(
        private array $stats,
    ) {
    }

    /**
     * @return Stat[]
     */
    public function getStats(): array
    {
        return $this->stats;
    }
}
