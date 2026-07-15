<?php

namespace Mautic\ReportBundle\Event;

use Mautic\ReportBundle\Entity\Scheduler;
use Symfony\Contracts\EventDispatcher\Event;

class ReportScheduleSendEvent extends Event
{
    /**
     * @param string $file
     */
    public function __construct(
        private readonly Scheduler $scheduler,
        private $file,
    ) {
    }

    public function getScheduler(): Scheduler
    {
        return $this->scheduler;
    }

    /**
     * @return string
     */
    public function getFile()
    {
        return $this->file;
    }
}
