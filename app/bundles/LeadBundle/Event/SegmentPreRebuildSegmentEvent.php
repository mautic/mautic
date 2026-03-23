<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;

/**
 * Event dispatched before a company segment is rebuilt.
 */
class SegmentPreRebuildSegmentEvent extends CommonEvent
{
    private bool $result = false;

    /**
     * @param array<string, int|array<mixed>> $list
     */
    public function __construct(
        protected array $list,
        bool $isNew = false,
    ) {
        $this->isNew = $isNew;
    }

    /**
     * Returns the segment filters.
     *
     * @return array<mixed>
     */
    public function getList(): array
    {
        return $this->list;
    }

    public function getResult(): bool
    {
        return $this->result;
    }

    public function setResult(bool $result): self
    {
        $this->result = $result;

        return $this;
    }
}
