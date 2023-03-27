<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\EventListener;

use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\LeadBundle\Event\ContactExportSchedulerEvent;
use Mautic\LeadBundle\LeadEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ContactExportSchedulerAuditLogSubscriber implements EventSubscriberInterface
{
    private AuditLogModel $auditLogModel;
    private IpLookupHelper $ipLookupHelper;

    public function __construct(AuditLogModel $auditLogModel, IpLookupHelper $ipLookupHelper)
    {
        $this->auditLogModel  = $auditLogModel;
        $this->ipLookupHelper = $ipLookupHelper;
    }

    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            LeadEvents::POST_CONTACT_EXPORT_SCHEDULED  => 'onContactExportScheduled',
            LeadEvents::POST_CONTACT_EXPORT_SEND_EMAIL => 'onContactExportEmailSent',
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
