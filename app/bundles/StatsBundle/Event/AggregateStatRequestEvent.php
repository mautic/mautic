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
     * @param string $statName
     */
    public function __construct($statName, \DateTimeInterface $fromDateTime, \DateTimeInterface $toDateTime, FetchOptions $eventOptions)
    {
        $this->statName       = $statName;
        $this->fromDateTime   = $fromDateTime;
        $this->toDateTime     = $toDateTime;
        $this->options        = $eventOptions;
        $this->statCollection = new StatCollection();
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
     * @param string $context
     *
     * @return bool
     */
    public function checkContext($context)
    {
        return $this->statName === $context;
    }

    /**
     * @return bool
     */
    public function checkContexts(array $contexts)
    {
        return in_array($this->statName, $contexts, true);
    }

    /**
     * @param string $prefix
     *
     * @return bool
     */
    public function checkContextPrefix($prefix)
    {
        return 0 === strpos($this->statName, $prefix);
    }

    /**
     * @return bool
     */
    public function checkContextPrefixes(array $prefixes)
    {
        foreach ($prefixes as $string) {
            if (0 === strpos($this->statName, $string)) {
                return true;
            }
        }

        return false;
    }
}
