<?php

declare(strict_types=1);

namespace MauticPlugin\MauticSocialBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use MauticPlugin\MauticSocialBundle\Entity\Monitoring;

abstract class AbstractMonitorEvent extends CommonEvent
{
    public function __construct(Monitoring $monitoring, bool $isNew = false)
    {
        $this->entity = $monitoring;
        $this->isNew  = $isNew;
    }

    /**
     * Returns the Monitoring entity.
     *
     * @return Monitoring
     */
    public function getMonitoring()
    {
        return $this->entity;
    }

    /**
     * Sets the Monitoring entity.
     */
    public function setMonitoring(Monitoring $monitoring): void
    {
        $this->entity = $monitoring;
    }
}
