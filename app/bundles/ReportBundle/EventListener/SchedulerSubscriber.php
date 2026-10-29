<?php

declare(strict_types=1);

namespace Mautic\ReportBundle\EventListener;

use Mautic\ReportBundle\Event\ReportScheduleSendEvent;
use Mautic\ReportBundle\Scheduler\Model\SendSchedule;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final readonly class SchedulerSubscriber
{
    public function __construct(
        private SendSchedule $sendSchedule,
    ) {
    }

    #[AsEventListener(
        event: ReportScheduleSendEvent::class,
    )]
    public function onScheduleSend(ReportScheduleSendEvent $event): void
    {
        $scheduler = $event->getScheduler();
        $file      = $event->getFile();

        $this->sendSchedule->send($scheduler, $file);
    }
}
