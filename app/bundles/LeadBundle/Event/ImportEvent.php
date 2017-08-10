<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\LeadBundle\Entity\Import;

/**
 * Class ImportEvent.
 */
class ImportEvent extends CommonEvent
{
    /**
     * @param Import $entity
     * @param bool   $isNew
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
