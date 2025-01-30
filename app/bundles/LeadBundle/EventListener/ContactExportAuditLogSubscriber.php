<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\EventListener;

use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\LeadBundle\Event\ContactExportEvent;
use Mautic\LeadBundle\LeadEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ContactExportAuditLogSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private AuditLogModel $auditLogModel,
        private IpLookupHelper $ipLookupHelper,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LeadEvents::POST_CONTACT_EXPORT  => 'onContactExport',
        ];
    }

    public function onContactExport(ContactExportEvent $event): void
    {
        $this->auditLogModel->writeToLog(
            [
                'bundle'    => 'lead',
                'object'    => $event->getObject(),
                'objectId'  => 0,
                'action'    => 'create',
                'details'   => $event->getArgs(),
                'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
            ]
        );
    }
}
