<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\EventListener;

use Mautic\EmailBundle\Model\EmailModel;
use Mautic\LeadBundle\Event\ListBatchChangeEvent;
use Mautic\LeadBundle\Event\ListChangeEvent;

final readonly class SegmentSubscriber
{
    public function __construct(
        private EmailModel $emailModel,
    ) {
    }
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(
        event: ListChangeEvent::class,
        priority: 0
    )]
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(
        event: ListBatchChangeEvent::class,
        priority: 0
    )]
    public function onListChange(ListChangeEvent $event): void
    {
        $this->emailModel->invalidatePendingCountCacheForList($event->getList()->getId());
    }
}
