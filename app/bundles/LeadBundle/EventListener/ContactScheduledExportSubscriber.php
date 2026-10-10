<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\EventListener;

use Mautic\LeadBundle\Event\ContactExportEmailSentEvent;
use Mautic\LeadBundle\Event\ContactExportPrepareFileEvent;
use Mautic\LeadBundle\Event\ContactExportSchedulerEvent;
use Mautic\LeadBundle\Event\ContactExportSendEmailEvent;
use Mautic\LeadBundle\Model\ContactExportSchedulerModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class ContactScheduledExportSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ContactExportSchedulerModel $contactExportSchedulerModel,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ContactExportPrepareFileEvent::class    => 'onContactExportPrepareFile',
            ContactExportSendEmailEvent::class      => 'onContactExportSendEmail',
            ContactExportEmailSentEvent::class => 'onContactExportEmailSent',
        ];
    }

    public function onContactExportPrepareFile(ContactExportSchedulerEvent $event): void
    {
        $contactExportScheduler = $event->getContactExportScheduler();
        $filePath               = $this->contactExportSchedulerModel->processAndGetExportFilePath($contactExportScheduler);
        $event->setFilePath($filePath);
    }

    public function onContactExportSendEmail(ContactExportSchedulerEvent $event): void
    {
        $contactExportScheduler = $event->getContactExportScheduler();
        $this->contactExportSchedulerModel->sendEmail($contactExportScheduler, $event->getFilePath());
    }

    public function onContactExportEmailSent(ContactExportSchedulerEvent $event): void
    {
        $contactExportScheduler = $event->getContactExportScheduler();
        $this->contactExportSchedulerModel->deleteEntity($contactExportScheduler);
    }
}
