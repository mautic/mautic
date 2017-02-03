<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ChannelBundle\Event;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class ChannelBroadcastEvent.
 */
class ChannelBroadcastEvent extends Event
{
    /**
     * Specific channel.
     *
     * @var null
     */
    protected $channel;

    /**
     * Specific ID of a specific channel.
     *
     * @var null
     */
    protected $id;

    /**
     * Number of contacts successfully processed and/or failed per channel.
     *
     * @var int
     */
    protected $results = [];

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * MaintenanceEvent constructor.
     *
     * @param int  $daysOld
     * @param bool $dryRun
     */
    public function __construct($channel, $channelId, OutputInterface $output)
    {
        $this->channel = $channel;
        $this->id      = $channelId;
        $this->output  = $output;
    }

    /**
     * @return mixed
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param     $channelLabel
     * @param int $successCount
     * @param int $failedCount
     */
    public function setResults($channelLabel, $successCount, $failedCount = 0)
    {
        $this->results[$channelLabel] = [
            'success' => (int) $successCount,
            'failed'  => (int) $failedCount,
        ];
    }

    /**
     * @return int
     */
    public function getResults()
    {
        return $this->results;
    }

    /**
     * @param $channel
     *
     * @return bool
     */
    public function checkContext($channel)
    {
        if ($this->channel && $this->channel !== $channel) {
            return false;
        }

        return true;
    }

    /**
     * @return OutputInterface
     */
    public function getOutput()
    {
        return $this->output;
    }
}
