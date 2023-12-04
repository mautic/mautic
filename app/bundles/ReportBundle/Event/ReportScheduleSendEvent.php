<?php

namespace Mautic\ReportBundle\Event;

use Mautic\ReportBundle\Entity\Scheduler;
use Symfony\Contracts\EventDispatcher\Event;

class ReportScheduleSendEvent extends Event
{
    private \Mautic\ReportBundle\Entity\Scheduler $scheduler;

    /**
     * @var string
     */
    private $file;

    /**
     * @param string $file
     */
    public function __construct(Scheduler $scheduler, $file)
    {
        $this->scheduler = $scheduler;
        $this->file      = $file;
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
