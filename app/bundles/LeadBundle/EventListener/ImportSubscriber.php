<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\EventListener;

use Mautic\CoreBundle\Event\EntityPostDelete;
use Mautic\CoreBundle\Event\EntityPostSave;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\LeadBundle\Entity\Import;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

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
    #[AsEventListener]
    public function onImportPostSave(EntityPostSave $event): void
    {
        $entity = $event->getEntity();
        if (!$entity instanceof Import) {
            return;
        }

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
    #[AsEventListener]
    public function onImportDelete(EntityPostDelete $event): void
    {
        $entity = $event->getEntity();
        if (!$entity instanceof Import) {
            return;
        }

        $log = [
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
