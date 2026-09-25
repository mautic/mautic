<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\EventListener;

use Mautic\EmailBundle\Model\EmailModel;
use Mautic\LeadBundle\Event\ListBatchChangeEvent;
use Mautic\LeadBundle\Event\ListChangeEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class SegmentSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private EmailModel $emailModel,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ListChangeEvent::class       => ['onListChange', 0],
            ListBatchChangeEvent::class => ['onListChange', 0],
        ];
    }

    public function onListChange(ListChangeEvent $event): void
    {
        $this->emailModel->invalidatePendingCountCacheForList($event->getList()->getId());
    }
}
