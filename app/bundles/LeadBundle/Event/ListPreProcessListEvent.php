<?php

namespace Mautic\LeadBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;

class ListPreProcessListEvent extends CommonEvent
{
    protected $result;

    /**
     * @param bool $isNew
     */
    public function __construct(
        protected array $list,
        $isNew = false,
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

    /**
     * @return bool
     */
    public function getResult()
    {
        return $this->result;
    }

    public function setResult($result): static
    {
        $this->result = $result;

        return $this;
    }
}
