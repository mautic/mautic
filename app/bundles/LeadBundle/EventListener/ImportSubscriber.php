<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\EventListener;

use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\LeadBundle\Event\ImportEvent;
use Mautic\LeadBundle\Event\ImportPostDeleteEvent;
use Mautic\LeadBundle\Event\ImportPostSaveEvent;

final readonly class ImportSubscriber
{
    public function __construct(
        private IpLookupHelper $ipLookupHelper,
        private AuditLogModel $auditLogModel,
    ) {
    }
    /**
     * Add an entry to the audit log.
     */
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(
        event: ImportPostSaveEvent::class,
        priority: 0
    )]
    public function onImportPostSave(ImportEvent $event): void
    {
        $entity = $event->getEntity();
        if ($details = $event->getChanges()) {
            $log = [
                'bundle'    => 'lead',
                'object'    => 'import',
                'objectId'  => $entity->getId(),
                'action'    => ($event->isNew()) ? 'create' : 'update',
                'details'   => $details,
                'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
            ];
            $this->auditLogModel->writeToLog($log);
        }
    }
    /**
     * Add a delete entry to the audit log.
     */
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(
        event: ImportPostDeleteEvent::class,
        priority: 0
    )]
    public function onImportDelete(ImportEvent $event): void
    {
        $entity = $event->getEntity();
        $log    = [
            'bundle'    => 'lead',
            'object'    => 'import',
            'objectId'  => $entity->deletedId,
            'action'    => 'delete',
            'details'   => ['originalFile' => $entity->getOriginalFile()],
            'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
        ];
        $this->auditLogModel->writeToLog($log);

        // In case of batch delete, this method call remove the uploaded file
        $entity->removeFile();
    }
}
