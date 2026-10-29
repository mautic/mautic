<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\LeadBundle\Event\ContactExportEvent;

final readonly class ContactExportAuditLogSubscriber
{
    public function __construct(
        private AuditLogModel $auditLogModel,
        private IpLookupHelper $ipLookupHelper,
    ) {
    }

    #[AsEventListener(event: ContactExportEvent::class)]
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
