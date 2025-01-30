<?php

namespace Mautic\StatsBundle\Event;

use Mautic\StatsBundle\Aggregate\Collection\StatCollection;
use Mautic\StatsBundle\Event\Options\FetchOptions;
use Symfony\Contracts\EventDispatcher\Event;

class AggregateStatRequestEvent extends Event
{
    private StatCollection $statCollection;

    /**
     * @param string $statName
     */
    public function __construct(
        private $statName,
        private \DateTimeInterface $fromDateTime,
        private \DateTimeInterface $toDateTime,
        private FetchOptions $options,
    ) {
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
        return str_starts_with($this->statName, $prefix);
    }

    public function checkContextPrefixes(array $prefixes): bool
    {
        foreach ($prefixes as $string) {
            if (str_starts_with($this->statName, $string)) {
                return true;
            }
        }

        return false;
    }
}
