<?php

namespace Mautic\ChannelBundle\Event;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\EventDispatcher\Event;

class ChannelBroadcastEvent extends Event
{
    /**
     * Number of contacts successfully processed and/or failed per channel.
     *
     * @var array
     */
    protected $results = [];

    /**
     * Min contact ID filter can be used for process parallelization.
     *
     * @var int
     */
    private $minContactIdFilter;

    /**
     * Max contact ID filter can be used for process parallelization.
     *
     * @var int
     */
    private $maxContactIdFilter;

    /**
     * How many contacts to load from the database.
     */
    private int $limit = 100;

    /**
     * How big batches to use to actually send.
     */
    private int $batch = 50;

    private ?int $maxThreads = null;

    private ?int $threadId = null;

    public function __construct(
        /**
         * Specific channel.
         */
        protected ?string $channel,
        /**
         * Specific ID of a specific channel.
         */
        protected string|int|null $id,
        protected OutputInterface $output,
    ) {
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
     * @param string $channelLabel
     * @param int    $successCount
     * @param int    $failedCount
     */
    public function setResults($channelLabel, $successCount, $failedCount = 0, array $failedRecipientsByList = []): void
    {
        $this->results[$channelLabel] = [
            'success'                => (int) $successCount,
            'failed'                 => (int) $failedCount,
            'failedRecipientsByList' => $failedRecipientsByList,
        ];
    }

    /**
     * @return array
     */
    public function getResults()
    {
        return $this->results;
    }

    public function checkContext($channel): bool
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

    /**
     * @param int $minContactIdFilter
     */
    public function setMinContactIdFilter($minContactIdFilter): void
    {
        $this->minContactIdFilter = $minContactIdFilter;
    }

    /**
     * @return int|null
     */
    public function getMinContactIdFilter()
    {
        return $this->minContactIdFilter;
    }

    /**
     * @param int $maxContactIdFilter
     */
    public function setMaxContactIdFilter($maxContactIdFilter): void
    {
        $this->maxContactIdFilter = $maxContactIdFilter;
    }

    /**
     * @return int|null
     */
    public function getMaxContactIdFilter()
    {
        return $this->maxContactIdFilter;
    }

    /**
     * @param int $limit
     */
    public function setLimit($limit): void
    {
        $this->limit = $limit;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * @param int $batch
     */
    public function setBatch($batch): void
    {
        $this->batch = $batch;
    }

    public function getBatch(): int
    {
        return $this->batch;
    }

    public function getMaxThreads(): ?int
    {
        return $this->maxThreads;
    }

    public function setMaxThreads(?int $maxThreads): void
    {
        $this->maxThreads = $maxThreads;
    }

    public function getThreadId(): ?int
    {
        return $this->threadId;
    }

    public function setThreadId(?int $threadId): void
    {
        $this->threadId = $threadId;
    }
}
