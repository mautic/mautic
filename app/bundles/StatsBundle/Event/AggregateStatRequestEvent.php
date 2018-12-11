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
use Mautic\StatsBundle\Event\Options\FetchOptions;
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
     * @var StatCollection
     */
    private $statCollection;

    /**
     * @var FetchOptions
     */
    private $options;

    /**
     * AggregateStatRequestEvent constructor.
     *
     * @param string             $statName
     * @param \DateTimeInterface $fromDateTime
     * @param \DateTimeInterface $toDateTime
     * @param FetchOptions       $eventOptions
     */
    public function __construct($statName, \DateTimeInterface $fromDateTime, \DateTimeInterface $toDateTime, FetchOptions $eventOptions)
    {
        $this->statName       = $statName;
        $this->fromDateTime   = $fromDateTime;
        $this->toDateTime     = $toDateTime;
        $this->options        = $eventOptions;
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
     * @return FetchOptions
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @return StatCollection
     */
    public function getStatCollection()
    {
        return $this->statCollection;
    }

    /**
     * @param string|array $context
     *
     * @return bool
     */
    public function checkContext($context)
    {
        if (is_array($context)) {
            return in_array($this->statName, $context);
        }

        return $context === $this->statName;
    }

    /**
     * @param string|array $prefix
     *
     * @return bool
     */
    public function checkContextPrefix($prefix)
    {
        if (is_array($prefix)) {
            foreach ($prefix as $string) {
                if (strpos($this->statName, $string) === 0) {
                    return true;
                }
            }

            return false;
        }

        return strpos($this->statName, $prefix) === 0;
    }
}
