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
        private Scheduler $scheduler,
        private $file
    ) {
    }

    /**
     * @return Scheduler
     */
    public function getScheduler()
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
