<?php

namespace Mautic\PointBundle\Event;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\PointBundle\Entity\Point;
use Symfony\Component\EventDispatcher\Event;

class PointChangeActionExecutedEvent extends Event
{
    /**
     * @var Point
     */
    private $pointAction;

    /**
     * @var Lead
     */
    private $lead;

    /**
     * @var mixed
     */
    private $eventDetails;

    /**
     * @var bool
     */
    private $changePoints;

    /**
     * @var array<mixed>
     */
    private $completedActions;

    /**
     * PointChangeActionExecutedEvent constructor.
     *
     * @param mixed        $eventDetails
     * @param array<mixed> $completedActions
     */
    public function __construct(Point $pointAction, Lead $lead, $eventDetails, $completedActions = [])
    {
        $this->pointAction      = $pointAction;
        $this->lead             = $lead;
        $this->eventDetails     = $eventDetails;
        $this->completedActions = $completedActions;
    }

    /**
     * @return bool
     */
    public function canChangePoints()
    {
        return $this->changePoints;
    }

    public function setSucceded(): void
    {
        $this->changePoints = true;
    }

    public function setFailed(): void
    {
        $this->changePoints = false;
    }

    public function setStatusFromLogs(): void
    {
        $this->changePoints = !(isset($this->completedActions[$this->pointAction->getId()]));
    }

    /**
     * @param int|string $internalId
     */
    public function setStatusFromLogsForInternalId($internalId): void
    {
        $this->changePoints = !isset($this->completedActions[$this->pointAction->getId()][$internalId]);
    }

    /**
     * @return Point
     */
    public function getPointAction()
    {
        return $this->pointAction;
    }

    /**
     * @return Lead
     */
    public function getLead()
    {
        return $this->lead;
    }

    /**
     * @return mixed
     */
    public function getEventDetails()
    {
        return $this->eventDetails;
    }

    /**
     * @return array<int|string>
     */
    public function getCompletedActions()
    {
        return $this->completedActions;
    }
}
