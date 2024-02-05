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
        $isNew = false
    ) {
        $this->isNew = $isNew;
    }

    /**
     * Returns the List entity.
     *
     * @return array
     */
    public function getList()
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

    /**
     * @return $this
     */
    public function setResult($result)
    {
        $this->result = $result;

        return $this;
    }
}
