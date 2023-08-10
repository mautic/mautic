<?php

namespace Mautic\LeadBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\LeadBundle\Entity\LeadList;

class LeadListEvent extends CommonEvent
{
    /**
     * @param bool $isNew
     */
    public function __construct(LeadList $list, $isNew = false)
    {
        $this->entity = $list;
        $this->isNew  = $isNew;
    }

    /**
     * Returns the List entity.
     *
     * @return LeadList
     */
    public function getList()
    {
        return $this->entity;
    }

    /**
     * Sets the List entity.
     */
    public function setList(LeadList $list)
    {
        $this->entity = $list;
    }
}
