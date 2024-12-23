<?php

declare(strict_types=1);

namespace Mautic\PointBundle\Event;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\PointBundle\Entity\GroupContactScore;

final class GroupScoreChangeEvent
{
    public function __construct(
        private GroupContactScore $groupContactScore,
        private int $oldScore,
        private int $newScore,
    ) {
    }

    public function getGroupContactScore(): GroupContactScore
    {
        return $this->groupContactScore;
    }

    public function getContact(): Lead
    {
        return $this->groupContactScore->getContact();
    }

    public function getNewScore(): int
    {
        return $this->newScore;
    }

    public function getOldScore(): int
    {
        return $this->oldScore;
    }
}
