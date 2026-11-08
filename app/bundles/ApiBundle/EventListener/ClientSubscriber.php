<?php

declare(strict_types=1);

namespace Mautic\ApiBundle\EventListener;

use Mautic\ApiBundle\Entity\Client;
use Mautic\CoreBundle\Event\EntityPostDelete;
use Mautic\CoreBundle\Event\EntityPostSave;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

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
    #[AsEventListener]
    public function onClientPostSave(EntityPostSave $event): void
    {
        $client = $event->getEntity();
        if (!$client instanceof Client) {
            return;
        }

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
    #[AsEventListener]
    public function onClientDelete(EntityPostDelete $event): void
    {
        $client = $event->getEntity();
        if (!$client instanceof Client) {
            return;
        }

        $log = [
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
