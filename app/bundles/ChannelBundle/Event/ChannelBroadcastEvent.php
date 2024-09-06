<?php

namespace Mautic\ChannelBundle\Event;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\EventDispatcher\Event;

class ChannelBroadcastEvent extends Event
{
    /**
     * Number of contacts successfully processed and/or failed per channel.
     */
    protected array $results = [];

    /**
     * Min contact ID filter can be used for process parallelization.
     */
    private ?int $minContactIdFilter = null;

    /**
     * Max contact ID filter can be used for process parallelization.
     */
    private ?int $maxContactIdFilter = null;

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
        protected string|int $id,
        protected OutputInterface $output
    ) {
    }

    public function getChannel(): ?string
    {
        return $this->channel;
    }

    public function getId(): string|int
    {
        return $this->id;
    }

    public function setResults(string $channelLabel, int $successCount, int $failedCount = 0, array $failedRecipientsByList = []): void
    {
        $this->results[$channelLabel] = [
            'success'                => (int) $successCount,
            'failed'                 => (int) $failedCount,
            'failedRecipientsByList' => $failedRecipientsByList,
        ];
    }

    public function getResults(): array
    {
        return $this->results;
    }

    public function checkContext(string $channel): bool
    {
        if ($this->channel && $this->channel !== $channel) {
            return false;
        }

        return true;
    }

    public function getOutput(): OutputInterface
    {
        return $this->output;
    }

    public function setMinContactIdFilter(int $minContactIdFilter): void
    {
        $this->minContactIdFilter = $minContactIdFilter;
    }

    public function getMinContactIdFilter(): ?int
    {
        return $this->minContactIdFilter;
    }

    public function setMaxContactIdFilter(int $maxContactIdFilter): void
    {
        $this->maxContactIdFilter = $maxContactIdFilter;
    }

    public function getMaxContactIdFilter(): ?int
    {
        return $this->maxContactIdFilter;
    }

    public function setLimit(int $limit): void
    {
        $this->limit = $limit;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function setBatch(int $batch): void
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
