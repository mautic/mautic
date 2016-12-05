<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PointBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\PointBundle\Entity\Trigger;

/**
 * Class TriggerEvent.
 */
class TriggerEvent extends CommonEvent
{
    /**
     * @var Trigger
     */
    protected $entity;

    /**
     * @var bool
     */
    protected $isNew;

    /**
     * @param Trigger $trigger
     * @param bool    $isNew
     */
    public function __construct(Trigger &$trigger, $isNew = false)
    {
        $this->entity = &$trigger;
        $this->isNew  = $isNew;
    }

    /**
     * Returns the Trigger entity.
     *
     * @return Trigger
     */
    public function getTrigger()
    {
        return $this->entity;
    }

    /**
     * Sets the Trigger entity.
     *
     * @param Trigger $trigger
     */
    public function setTrigger(Trigger $trigger)
    {
        $this->entity = $trigger;
    }
}
