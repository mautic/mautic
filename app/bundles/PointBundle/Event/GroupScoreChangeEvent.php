<?php

declare(strict_types=1);

namespace Mautic\PointBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PointBundle\Entity\GroupContactScore;

class GroupScoreChangeEvent extends CommonEvent
{
    protected int $oldScore;
    protected int $newScore;

    public function __construct(GroupContactScore &$groupContactScore, int $oldScore, int $newScore)
    {
        $this->entity      = &$groupContactScore;
        $this->oldScore    = $oldScore;
        $this->newScore    = $newScore;
    }

    /**
     * Returns the GroupContactScore enitity.
     *
     * @return GroupContactScore
     */
    public function getGroupContactScore()
    {
        return $this->entity;
    }

    public function getContact(): Lead
    {
        return $this->entity->getContact();
    }

    /**
     * Returns the new score.
     */
    public function getNewScore(): int
    {
        return $this->newScore;
    }

    /**
     * Returns the old score.
     */
    public function getOldScore(): int
    {
        return $this->oldScore;
    }
}
