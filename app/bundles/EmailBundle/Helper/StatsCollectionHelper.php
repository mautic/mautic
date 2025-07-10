<?php

namespace Mautic\EmailBundle\Helper;

use Mautic\EmailBundle\Stats\FetchOptions\EmailStatOptions;
use Mautic\EmailBundle\Stats\Helper\BouncedHelper;
use Mautic\EmailBundle\Stats\Helper\ClickedHelper;
use Mautic\EmailBundle\Stats\Helper\FailedHelper;
use Mautic\EmailBundle\Stats\Helper\OpenedHelper;
use Mautic\EmailBundle\Stats\Helper\SentHelper;
use Mautic\EmailBundle\Stats\Helper\UnsubscribedHelper;
use Mautic\EmailBundle\Stats\StatHelperContainer;
use Mautic\StatsBundle\Aggregate\Collection\StatCollection;

class StatsCollectionHelper
{
    public const GENERAL_STAT_PREFIX = 'email';

    public function __construct(
        private StatHelperContainer $helperContainer,
    ) {
    }

    /**
     * Fetch stats from listeners.
     *
     * @return mixed
     *
     * @throws \Mautic\EmailBundle\Stats\Exception\InvalidStatHelperException
     */
    public function fetchSentStats(\DateTime $fromDateTime, \DateTime $toDateTime, EmailStatOptions $options)
    {
        return $this->helperContainer->getHelper(SentHelper::NAME)->fetchStats($fromDateTime, $toDateTime, $options);
    }

    /**
     * Fetch stats from listeners.
     *
     * @return mixed
     *
     * @throws \Mautic\EmailBundle\Stats\Exception\InvalidStatHelperException
     */
    public function fetchOpenedStats(\DateTime $fromDateTime, \DateTime $toDateTime, EmailStatOptions $options)
    {
        return $this->helperContainer->getHelper(OpenedHelper::NAME)->fetchStats($fromDateTime, $toDateTime, $options);
    }

    /**
     * Fetch stats from listeners.
     *
     * @return mixed
     *
     * @throws \Mautic\EmailBundle\Stats\Exception\InvalidStatHelperException
     */
    public function fetchFailedStats(\DateTime $fromDateTime, \DateTime $toDateTime, EmailStatOptions $options)
    {
        return $this->helperContainer->getHelper(FailedHelper::NAME)->fetchStats($fromDateTime, $toDateTime, $options);
    }

    /**
     * Fetch stats from listeners.
     *
     * @return mixed
     *
     * @throws \Mautic\EmailBundle\Stats\Exception\InvalidStatHelperException
     */
    public function fetchClickedStats(\DateTime $fromDateTime, \DateTime $toDateTime, EmailStatOptions $options)
    {
        return $this->helperContainer->getHelper(ClickedHelper::NAME)->fetchStats($fromDateTime, $toDateTime, $options);
    }

    /**
     * Fetch stats from listeners.
     *
     * @return mixed
     *
     * @throws \Mautic\EmailBundle\Stats\Exception\InvalidStatHelperException
     */
    public function fetchBouncedStats(\DateTime $fromDateTime, \DateTime $toDateTime, EmailStatOptions $options)
    {
        return $this->helperContainer->getHelper(BouncedHelper::NAME)->fetchStats($fromDateTime, $toDateTime, $options);
    }

    /**
     * Fetch stats from listeners.
     *
     * @return mixed
     *
     * @throws \Mautic\EmailBundle\Stats\Exception\InvalidStatHelperException
     */
    public function fetchUnsubscribedStats(\DateTime $fromDateTime, \DateTime $toDateTime, EmailStatOptions $options)
    {
        return $this->helperContainer->getHelper(UnsubscribedHelper::NAME)->fetchStats($fromDateTime, $toDateTime, $options);
    }

    /**
     * Generate stats from Mautic's raw data.
     *
     * @throws \Mautic\EmailBundle\Stats\Exception\InvalidStatHelperException
     */
    public function generateStats(
        $statName,
        \DateTime $fromDateTime,
        \DateTime $toDateTime,
        EmailStatOptions $options,
        StatCollection $statCollection,
    ): void {
        $this->helperContainer->getHelper($statName)->generateStats($fromDateTime, $toDateTime, $options, $statCollection);
    }
}
