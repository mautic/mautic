<?php

namespace Mautic\LeadBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\LeadBundle\Entity\Import;

class ImportEvent extends CommonEvent
{
    /**
     * @param bool $isNew
     */
    public function __construct(Import $entity, $isNew)
    {
        $this->entity = $entity;
        $this->isNew  = $isNew;
    }

    /**
     * Returns the Import entity.
     *
     * @return Import
     */
    public function getEntity()
    {
        return $this->entity;
    }
}
