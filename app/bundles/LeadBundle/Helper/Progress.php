<?php

namespace Mautic\LeadBundle\Helper;

use Mautic\CoreBundle\Helper\ProgressBarHelper;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

final class Progress
{
    /**
     * Total number of items representing 100%.
     */
    private int $total = 0;

    /**
     * Currently proccessed items.
     */
    private int $done = 0;

    private ?ProgressBar $bar = null;

    public function __construct(
        private readonly ?OutputInterface $output = null,
    ) {
    }

    /**
     * Returns count of all items.
     */
    public function getTotal(): int
    {
        return $this->total;
    }

    /**
     * Set total value.
     *
     * @param int $total
     */
    public function setTotal($total): static
    {
        $this->total = (int) $total;

        if ($this->output) {
            $this->bar = ProgressBarHelper::init($this->output, $this->total);
            $this->bar->start();
        }

        return $this;
    }

    /**
     * Returns count of processed items.
     */
    public function getDone(): int
    {
        return $this->done;
    }

    /**
     * Set total value.
     */
    public function setDone($done): static
    {
        $this->done = (int) $done;

        if ($this->bar) {
            $this->bar->setProgress($this->done);

            if ($this->isFinished()) {
                $this->bar->finish();
                $this->output->writeln('');
            }
        }

        return $this;
    }

    /**
     * Increase done count by 1.
     */
    public function increase(): static
    {
        $this->setDone($this->done + 1);

        return $this;
    }

    /**
     * Checked if the progress is 100 or more %.
     */
    public function isFinished(): bool
    {
        return $this->done >= $this->total;
    }

    /**
     * Bind Progress from simple array.
     */
    public function bindArray(array $progress): static
    {
        if (isset($progress[0])) {
            $this->setDone($progress[0]);
        }

        if (isset($progress[1])) {
            $this->setTotal($progress[1]);
        }

        return $this;
    }

    /**
     * Convert this object to a simple array.
     */
    public function toArray(): array
    {
        return [
            $this->done,
            $this->total,
        ];
    }

    /**
     * Counts percentage of the progress.
     *
     * @return int
     */
    public function toPercent(): float|int
    {
        return ($this->total) ? ceil(($this->done / $this->total) * 100) : 100;
    }
}
