<?php

declare(strict_types=1);

namespace Mautic\ReportBundle\EventListener;

use Mautic\CoreBundle\Event\EntityPostDelete;
use Mautic\CoreBundle\Event\EntityPostSave;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\ReportBundle\Entity\Report;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final readonly class ReportSubscriber
{
    public function __construct(
        private IpLookupHelper $ipLookupHelper,
        private AuditLogModel $auditLogModel,
    ) {
    }

    /**
     * Add an entry to the audit log.
     */
    #[AsEventListener(
        event: EntityPostSave::class,
    )]
    public function onReportPostSave(EntityPostSave $event): void
    {
        $report = $event->getEntity();
        if (!$report instanceof Report) {
            return;
        }
        if ($details = $event->getChanges()) {
            $log = [
                'bundle'    => 'report',
                'object'    => 'report',
                'objectId'  => $report->getId(),
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
    #[AsEventListener(
        event: EntityPostDelete::class,
    )]
    public function onReportDelete(EntityPostDelete $event): void
    {
        $report = $event->getEntity();
        if (!$report instanceof Report) {
            return;
        }
        $log = [
            'bundle'    => 'report',
            'object'    => 'report',
            'objectId'  => $report->deletedId,
            'action'    => 'delete',
            'details'   => ['name' => $report->getName()],
            'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
        ];
        $this->auditLogModel->writeToLog($log);
    }
}
