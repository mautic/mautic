<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\EventListener;

use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\LeadBundle\Event\ContactExportEmailSentEvent;
use Mautic\LeadBundle\Event\ContactExportScheduledEvent;
use Mautic\LeadBundle\Event\ContactExportSchedulerEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final readonly class ContactExportSchedulerAuditLogSubscriber
{
    public function __construct(
        private AuditLogModel $auditLogModel,
        private IpLookupHelper $ipLookupHelper,
    ) {
    }

    #[AsEventListener(event: ContactExportScheduledEvent::class)]
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

    #[AsEventListener(event: ContactExportEmailSentEvent::class)]
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
