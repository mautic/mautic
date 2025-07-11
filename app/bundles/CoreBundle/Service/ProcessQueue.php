<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Service;

use Symfony\Component\Process\Process;

final class ProcessQueue
{
    /**
     * @var \SplQueue<Process>
     */
    private \SplQueue $pending;

    /**
     * @var \SplObjectStorage<Process,Process>
     */
    private \SplObjectStorage $processing;

    /**
     * @var \SplObjectStorage<Process,Process>
     */
    private \SplObjectStorage $processed;

    public function __construct(
        private int $processLimit = 10,
    ) {
        $this->pending      = new \SplQueue();
        $this->processing   = new \SplObjectStorage();
        $this->processed    = new \SplObjectStorage();
    }

    /**
     * @param Process<mixed> $process
     */
    public function enqueue(Process $process): void
    {
        $this->pending->enqueue($process);
    }

    public function refresh(): void
    {
        // Remove finished processes from the processing queue
        foreach ($this->processing as $process) {
            if ($process->isRunning()) {
                continue;
            }
            $this->processing->detach($process);
            $this->processed->attach($process);
        }

        // Add new processes to the processing queue
        for ($i = $this->processing->count(); $i < $this->processLimit; ++$i) {
            if ($this->pending->isEmpty()) {
                break;
            }
            $process = $this->pending->dequeue();
            $process->start();
            $this->processing->attach($process);
        }
    }

    public function isProcessing(): bool
    {
        return $this->getProcessingCount() > 0;
    }

    public function getProcessedCount(): int
    {
        return $this->processed->count();
    }

    public function getProcessingCount(): int
    {
        return $this->processing->count();
    }

    /**
     * @return \SplObjectStorage<Process,Process>
     */
    public function getProcessed(): \SplObjectStorage
    {
        return $this->processed;
    }
}
