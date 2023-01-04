<?php

declare(strict_types=1);

namespace Mautic\PointBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\PointBundle\Entity\League;

class LeagueEvent extends CommonEvent
{
    /**
     * @param bool $isNew
     */
    public function __construct(League &$league, $isNew = false)
    {
        $this->entity = &$league;
        $this->isNew  = $isNew;
    }

    /**
     * Returns the League entity.
     *
     * @return League
     */
    public function getLeague()
    {
        return $this->entity;
    }

    /**
     * Sets the League entity.
     */
    public function setLeague(League $league)
    {
        $this->entity = $league;
    }
}
