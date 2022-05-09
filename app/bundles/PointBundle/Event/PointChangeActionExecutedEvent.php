<?php

declare(strict_types=1);

namespace Mautic\PointBundle\Event;

use Mautic\CoreBundle\Entity\IntIdInterface;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PointBundle\Entity\Point;
use Symfony\Contracts\EventDispatcher\Event;

class PointChangeActionExecutedEvent extends Event
{
    private Point $pointAction;

    private Lead $lead;

    /**
     * The entity that was affected.
     */
    private IntIdInterface $eventDetails;

    private ?bool $changePoints;

    /**
     * @var mixed[]
     */
    private array $completedActions;

    /**
     * @param mixed[] $completedActions
     */
    public function __construct(Point $pointAction, Lead $lead, IntIdInterface $eventDetails, array $completedActions = [])
    {
        $this->pointAction      = $pointAction;
        $this->lead             = $lead;
        $this->eventDetails     = $eventDetails;
        $this->completedActions = $completedActions;
    }

    public function canChangePoints(): ?bool
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

    public function setStatusFromLogsForInternalId(int $internalId): void
    {
        $this->changePoints = !isset($this->completedActions[$this->pointAction->getId()][$internalId]);
    }

    public function getPointAction(): Point
    {
        return $this->pointAction;
    }

    public function getLead(): Lead
    {
        return $this->lead;
    }

    public function getEventDetails(): IntIdInterface
    {
        return $this->eventDetails;
    }

    /**
     * @return int[]
     */
    public function getCompletedActions(): array
    {
        return $this->completedActions;
    }
}
