<?php

declare(strict_types=1);

namespace Mautic\ReportBundle\EventListener;

use Mautic\ReportBundle\Event\ReportScheduleSendEvent;
use Mautic\ReportBundle\Scheduler\Model\SendSchedule;

final readonly class SchedulerSubscriber
{
    public function __construct(
        private SendSchedule $sendSchedule,
    ) {
    }
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(
        event: ReportScheduleSendEvent::class,
        priority: 0
    )]
    public function onScheduleSend(ReportScheduleSendEvent $event): void
    {
        $scheduler = $event->getScheduler();
        $file      = $event->getFile();

        $this->sendSchedule->send($scheduler, $file);
    }
}
