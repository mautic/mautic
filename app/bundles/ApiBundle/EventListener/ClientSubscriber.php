<?php

declare(strict_types=1);

namespace Mautic\ApiBundle\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Mautic\ApiBundle\Event as Events;
use Mautic\ApiBundle\Event\ClientPostDeleteEvent;
use Mautic\ApiBundle\Event\ClientPostSaveEvent;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;

final readonly class ClientSubscriber
{
    public function __construct(
        private IpLookupHelper $ipLookupHelper,
        private AuditLogModel $auditLogModel,
    ) {
    }

    /**
     * Add a client change entry to the audit log.
     */
    #[AsEventListener(event: ClientPostSaveEvent::class)]
    public function onClientPostSave(Events\ClientEvent $event): void
    {
        $client = $event->getClient();
        if (!$details = $event->getChanges()) {
            return;
        }

        $log = [
            'bundle'    => 'api',
            'object'    => 'client',
            'objectId'  => $client->getId(),
            'action'    => ($event->isNew()) ? 'create' : 'update',
            'details'   => $details,
            'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
        ];
        $this->auditLogModel->writeToLog($log);
    }

    /**
     * Add a role delete entry to the audit log.
     */
    #[AsEventListener(event: ClientPostDeleteEvent::class)]
    public function onClientDelete(Events\ClientEvent $event): void
    {
        $client = $event->getClient();
        $log    = [
            'bundle'    => 'api',
            'object'    => 'client',
            'objectId'  => $client->deletedId,
            'action'    => 'delete',
            'details'   => ['name' => $client->getName()],
            'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
        ];
        $this->auditLogModel->writeToLog($log);
    }
}
