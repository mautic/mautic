<?php

declare(strict_types=1);

namespace Mautic\PointBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\PointBundle\Entity\Trigger;

final class TriggerEvent extends CommonEvent
{
    /**
     * @var Trigger
     */
    protected $entity;

    public function __construct(
        Trigger &$trigger,
        bool $isNew = false,
    ) {
        $this->entity = &$trigger;
        $this->isNew = $isNew;
    }

    
    public function getTrigger(): Trigger
    {
        return $this->entity;
    }

    public function setTrigger(Trigger $trigger): void
    {
        $this->entity = $trigger;
    }
}
