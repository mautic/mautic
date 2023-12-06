<?php

namespace Mautic\StatsBundle\Event;

use Mautic\StatsBundle\Aggregate\Collection\StatCollection;
use Mautic\StatsBundle\Event\Options\FetchOptions;
use Symfony\Contracts\EventDispatcher\Event;

class AggregateStatRequestEvent extends Event
{
    /**
     * @var string
     */
    private $statName;

    private \DateTimeInterface $fromDateTime;

    private \DateTimeInterface $toDateTime;

    private \Mautic\StatsBundle\Aggregate\Collection\StatCollection $statCollection;

    private \Mautic\StatsBundle\Event\Options\FetchOptions $options;

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
    public function statsCollected(): void
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
     */
    public function checkContext($context): bool
    {
        return $this->statName === $context;
    }

    public function checkContexts(array $contexts): bool
    {
        return in_array($this->statName, $contexts, true);
    }

    /**
     * @param string $prefix
     */
    public function checkContextPrefix($prefix): bool
    {
        return 0 === strpos($this->statName, $prefix);
    }

    public function checkContextPrefixes(array $prefixes): bool
    {
        foreach ($prefixes as $string) {
            if (0 === strpos($this->statName, $string)) {
                return true;
            }
        }

        return false;
    }
}
