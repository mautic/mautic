<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class GetStatDataEvent extends Event
{
    /**
     * @var array<string,mixed[]>
     */
    private array $results = [];

    /**
     * @param mixed[] $data
     */
    public function addResult(array $data): void
    {
        $this->results = $data;
    }

    /**
     * @return mixed[]
     */
    public function getResults(): array
    {
        return $this->results;
    }
}
