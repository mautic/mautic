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

/**
 * Class LeadDNCGetEntitiesEvent.
 */
class LeadDNCGetEntitiesEvent extends CommonEvent
{
    /**
     * @var array
     */
    protected $dncEntities;

    /**
     * @param array $dncEntities
     */
    public function __construct(array $dncEntities)
    {
        $this->dncEntities = $dncEntities;
    }

    /**
     * Returns the array of DoNotContact entities.
     *
     * @return array
     */
    public function getDNCEntities()
    {
        return $this->dncEntities;
    }

    /**
     * Sets the  array of DoNotContact entities.
     *
     * @param array $dncEntities
     */
    public function setDNCEntities(array $dncEntities)
    {
        $this->dncEntities = $dncEntities;

        return $this;
    }
}
