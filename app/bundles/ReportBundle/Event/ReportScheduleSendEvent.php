<?php

declare(strict_types=1);

namespace Mautic\ReportBundle\Event;

use Mautic\ReportBundle\Entity\Scheduler;
use Symfony\Contracts\EventDispatcher\Event;

final class ReportScheduleSendEvent extends Event
{
    public function __construct(
        private readonly Scheduler $scheduler,
        private readonly string $file,
    ) {
    }

    public function getScheduler(): Scheduler
    {
        return $this->scheduler;
    }

    public function getFile(): string
    {
        return $this->file;
    }
}
