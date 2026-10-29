<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\EventListener;

use Mautic\LeadBundle\Event\ContactExportEmailSentEvent;
use Mautic\LeadBundle\Event\ContactExportPrepareFileEvent;
use Mautic\LeadBundle\Event\ContactExportSchedulerEvent;
use Mautic\LeadBundle\Event\ContactExportSendEmailEvent;
use Mautic\LeadBundle\Model\ContactExportSchedulerModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class ContactScheduledExportSubscriber
{
    public function __construct(
        private ContactExportSchedulerModel $contactExportSchedulerModel,
    ) {
    }

    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(event: ContactExportPrepareFileEvent::class)]
    public function onContactExportPrepareFile(ContactExportSchedulerEvent $event): void
    {
        $contactExportScheduler = $event->getContactExportScheduler();
        $filePath               = $this->contactExportSchedulerModel->processAndGetExportFilePath($contactExportScheduler);
        $event->setFilePath($filePath);
    }

    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(event: ContactExportSendEmailEvent::class)]
    public function onContactExportSendEmail(ContactExportSchedulerEvent $event): void
    {
        $contactExportScheduler = $event->getContactExportScheduler();
        $this->contactExportSchedulerModel->sendEmail($contactExportScheduler, $event->getFilePath());
    }

    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(event: ContactExportEmailSentEvent::class)]
    public function onContactExportEmailSent(ContactExportSchedulerEvent $event): void
    {
        $contactExportScheduler = $event->getContactExportScheduler();
        $this->contactExportSchedulerModel->deleteEntity($contactExportScheduler);
    }
}
