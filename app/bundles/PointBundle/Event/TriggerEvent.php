<?php

namespace Mautic\PointBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\PointBundle\Entity\Trigger;

class TriggerEvent extends CommonEvent
{
    /**
     * @var Trigger
     */
    protected $entity;

    /**
     * @param bool $isNew
     */
    public function __construct(
        Trigger &$trigger,
        protected $isNew = false,
    ) {
        $this->entity = &$trigger;
    }

    /**
     * @return Trigger
     */
    public function getTrigger()
    {
        return $this->entity;
    }

    public function setTrigger(Trigger $trigger): void
    {
        $this->entity = $trigger;
    }
}
