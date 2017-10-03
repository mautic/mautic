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
use Mautic\LeadBundle\Entity\LeadList;

/**
 * Class LeadListEvent.
 */
class LeadListEvent extends CommonEvent
{
    /**
     * @param LeadList $list
     * @param bool     $isNew
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
     *
     * @param List $list
     */
    public function setList(LeadList $list)
    {
        $this->entity = $list;
    }
}
