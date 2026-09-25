<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;

final class ListPreProcessListEvent extends CommonEvent
{
    private ?bool $result = null;

    public function __construct(
        private array $list,
        bool $isNew = false,
    ) {
        $this->isNew = $isNew;
    }

    /**
     * Returns the List entity.
     */
    public function getList(): array
    {
        return $this->list;
    }

    /**
     * Sets the lead list entity.
     */
    public function setList(array $list): void
    {
        $this->list = $list;
    }

    public function getResult(): ?bool
    {
        return $this->result;
    }

    public function setResult(bool $result): static
    {
        $this->result = $result;

        return $this;
    }
}
