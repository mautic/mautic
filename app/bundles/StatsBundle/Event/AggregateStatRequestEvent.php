<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\StatsBundle\Event;

use Mautic\StatsBundle\Aggregate\Collection\StatCollection;
use Symfony\Component\EventDispatcher\Event;

class AggregateStatRequestEvent extends Event
{
    /**
     * @var string
     */
    private $statName;

    /**
     * @var \DateTimeInterface
     */
    private $fromDateTime;

    /**
     * @var \DateTimeInterface
     */
    private $toDateTime;

    /**
     * @var int|null
     */
    private $itemId;

    /**
     * @var StatCollection
     */
    private $statCollection;

    /**
     * StatRequestEvent constructor.
     *
     * @param string             $statName
     * @param \DateTimeInterface $fromDateTime
     * @param \DateTimeInterface $toDateTime
     * @param int|null           $itemId
     */
    public function __construct($statName, \DateTimeInterface $fromDateTime, \DateTimeInterface $toDateTime, $itemId)
    {
        $this->statName     = $statName;
        $this->fromDateTime = $fromDateTime;
        $this->toDateTime   = $toDateTime;
        $this->itemId       = $itemId;

        $this->statCollection = new StatCollection($statName);
    }

    /**
     * Note if the listener handled collecting these stats.
     */
    public function statsCollected()
    {
        $this->stopPropagation();
    }

    /**
     * @return string
     */
    public function getStatName()
    {
        return $this->statName;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getFromDateTime()
    {
        return $this->fromDateTime;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getToDateTime()
    {
        return $this->toDateTime;
    }

    /**
     * @return int|null
     */
    public function getItemId()
    {
        return $this->itemId;
    }

    /**
     * @return StatCollection
     */
    public function getStatCollection()
    {
        return $this->statCollection;
    }
}
