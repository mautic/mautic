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
 * Class LeadDNCGetEntities.
 */
class LeadDNCGetEntitiesEvent extends CommonEvent
{
    /**
     * @param array $dncEntities
     */
    public function __construct(array $dncEntities)
    {
        $this->dncEntities = $dncEntities;
    }

    /**
     * Returns the LeadNote entity.
     *
     * @return LeadNote
     */
    public function getDNCEntities()
    {
        return $this->dncEntities;
    }

    /**
     * Sets the LeadNote entity.
     *
     * @param LeadNote $note
     */
    public function setDNCEntities(array $dncEntities)
    {
        $this->dncEntities = $dncEntities;
    }
}
