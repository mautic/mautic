<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\EventListener;

use Mautic\EmailBundle\Model\EmailModel;
use Mautic\LeadBundle\Event\ListBatchChangeEvent;
use Mautic\LeadBundle\Event\ListChangeEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class SegmentSubscriber
{
    public function __construct(
        private EmailModel $emailModel,
    ) {
    }

    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(event: ListChangeEvent::class)]
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(event: ListBatchChangeEvent::class)]
    public function onListChange(ListChangeEvent $event): void
    {
        $this->emailModel->invalidatePendingCountCacheForList($event->getList()->getId());
    }
}
