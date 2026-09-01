<?php

declare(strict_types=1);

namespace Mautic\ReportBundle\EventListener;

use Mautic\ReportBundle\Event\ReportScheduleSendEvent;
use Mautic\ReportBundle\Scheduler\Model\SendSchedule;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class SchedulerSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private SendSchedule $sendSchedule,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ReportScheduleSendEvent::class => ['onScheduleSend', 0],
        ];
    }

    public function onScheduleSend(ReportScheduleSendEvent $event): void
    {
        $scheduler = $event->getScheduler();
        $file      = $event->getFile();

        $this->sendSchedule->send($scheduler, $file);
    }
}
