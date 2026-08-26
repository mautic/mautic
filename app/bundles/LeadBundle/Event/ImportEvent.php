<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\LeadBundle\Entity\Import;

final class ImportEvent extends CommonEvent
{
    public function __construct(Import $entity, bool $isNew)
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
