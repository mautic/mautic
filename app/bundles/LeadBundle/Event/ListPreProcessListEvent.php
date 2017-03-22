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
 * Class ListPreProcessListEvent.
 */
class ListPreProcessListEvent extends CommonEvent
{
    /**
     * @param LeadList $list
     * @param bool     $isNew
     */
    public function __construct($list, $isNew = false)
    {
        $this->list  = $list;
        $this->isNew = $isNew;
    }

    /**
     * Returns the List entity.
     *
     * @return List
     */
    public function getList()
    {
        return $this->list;
    }

    /**
     * Sets the List entity.
     *
     * @param List $list
     */
    public function setList(LeadList $list)
    {
        $this->list = $list;
    }
}
