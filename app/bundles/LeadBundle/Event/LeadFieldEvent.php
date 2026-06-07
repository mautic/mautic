<?php

namespace Mautic\LeadBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\CoreBundle\Event\DependencyErrorEventInterface;
use Mautic\CoreBundle\Event\DependencyErrorEventTrait;
use Mautic\LeadBundle\Entity\LeadField;

class LeadFieldEvent extends CommonEvent implements DependencyErrorEventInterface
{
    use DependencyErrorEventTrait;

    /**
     * @param bool $isNew
     */
    public function __construct(LeadField &$field, $isNew = false)
    {
        $this->entity = &$field;
        $this->isNew  = $isNew;
    }

    /**
     * Returns the Field entity.
     *
     * @return LeadField
     */
    public function getField()
    {
        return $this->entity;
    }

    /**
     * Sets the LeadField entity.
     */
    public function setField(LeadField $field): void
    {
        $this->entity = $field;
    }
}
