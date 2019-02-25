<?php
/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\LeadBundle\Entity\LeadNote;

/**
 * Class LeadDNCGetList.
 */
class LeadDNCGetListEvent extends CommonEvent
{
    /**
     * @param array $dncList
     */
    public function __construct(array $dncList)
    {
        $this->dncList = $dncList;
    }

    /**
     * Returns the LeadNote entity.
     *
     * @return LeadNote
     */
    public function getDNCList()
    {
        return $this->dncList;
    }

    /**
     * Sets the LeadNote entity.
     *
     * @param LeadNote $note
     */
    public function setDNCEntities(array $dncList)
    {
        $this->dncList = $dncList;
    }
}
