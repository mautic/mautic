<?php

namespace Mautic\LeadBundle\Helper;

use Mautic\CoreBundle\Helper\ProgressBarHelper;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

class Progress
{
    /**
     * Total number of items representing 100%.
     *
     * @var int
     */
    protected $total = 0;

    /**
     * Currently proccessed items.
     *
     * @var int
     */
    protected $done = 0;

    /**
     * @var ProgressBar|null
     */
    protected $bar;

    public function __construct(
        protected ?OutputInterface $output = null
    ) {
    }

    /**
     * Returns count of all items.
     *
     * @return int
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * Set total value.
     *
     * @param int $total
     *
     * @return Progress
     */
    public function setTotal($total)
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
     *
     * @return int
     */
    public function getDone()
    {
        return $this->done;
    }

    /**
     * Set total value.
     *
     * @return Progress
     */
    public function setDone($done)
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
     *
     * @return Progress
     */
    public function increase()
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
     *
     * @return Progress
     */
    public function bindArray(array $progress)
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
    public function toPercent()
    {
        return ($this->total) ? ceil(($this->done / $this->total) * 100) : 100;
    }
}
