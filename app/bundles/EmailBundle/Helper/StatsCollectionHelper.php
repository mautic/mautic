<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Helper;

use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Stats\FetchOptions\EmailStatOptions;
use Mautic\EmailBundle\Stats\Helper\BouncedHelper;
use Mautic\EmailBundle\Stats\Helper\ClickedHelper;
use Mautic\EmailBundle\Stats\Helper\FailedHelper;
use Mautic\EmailBundle\Stats\Helper\FilterTrait;
use Mautic\EmailBundle\Stats\Helper\OpenedHelper;
use Mautic\EmailBundle\Stats\Helper\SentHelper;
use Mautic\EmailBundle\Stats\Helper\UnsubscribedHelper;
use Mautic\EmailBundle\Stats\StatHelperContainer;
use Mautic\StatsBundle\Aggregate\Collection\StatCollection;

class StatsCollectionHelper
{
    use FilterTrait;

    const GENERAL_STAT_PREFIX = 'email';

    /**
     * @var StatHelperContainer
     */
    private $helperContainer;

    /**
     * StatsCollectionHelper constructor.
     */
    public function __construct(StatHelperContainer $helperContainer)
    {
        $this->helperContainer = $helperContainer;
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
     * @param $statName
     *
     * @throws \Mautic\EmailBundle\Stats\Exception\InvalidStatHelperException
     */
    public function generateStats(
        $statName,
        \DateTime $fromDateTime,
        \DateTime $toDateTime,
        EmailStatOptions $options,
        StatCollection $statCollection
    ) {
        $this->helperContainer->getHelper($statName)->generateStats($fromDateTime, $toDateTime, $options, $statCollection);
    }
}
