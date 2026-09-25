<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\EventListener;

use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\LeadBundle\Event\ContactExportEmailSentEvent;
use Mautic\LeadBundle\Event\ContactExportScheduledEvent;
use Mautic\LeadBundle\Event\ContactExportSchedulerEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class ContactExportSchedulerAuditLogSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private AuditLogModel $auditLogModel,
        private IpLookupHelper $ipLookupHelper,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ContactExportScheduledEvent::class  => 'onContactExportScheduled',
            ContactExportEmailSentEvent::class => 'onContactExportEmailSent',
        ];
    }

    public function onContactExportScheduled(ContactExportSchedulerEvent $event): void
    {
        $this->auditLogModel->writeToLog(
            [
                'bundle'    => 'lead',
                'object'    => 'ContactExportScheduler',
                'objectId'  => $event->getContactExportScheduler()->getId(),
                'action'    => 'create',
                'details'   => $event->getContactExportScheduler()->getChanges(),
                'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
            ]
        );
    }

    public function onContactExportEmailSent(ContactExportSchedulerEvent $event): void
    {
        $this->auditLogModel->writeToLog(
            [
                'bundle'    => 'lead',
                'object'    => 'ContactExportScheduler',
                'objectId'  => $event->getContactExportScheduler()->getId(),
                'action'    => 'sendEmail',
                'details'   => $event->getContactExportScheduler()->getChanges(),
                'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
            ]
        );
    }
}
