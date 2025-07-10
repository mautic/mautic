<?php

namespace Mautic\LeadBundle\EventListener;

use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\LeadBundle\Event as Events;
use Mautic\LeadBundle\LeadEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CompanySubscriber implements EventSubscriberInterface
{
    public function __construct(
        private IpLookupHelper $ipLookupHelper,
        private AuditLogModel $auditLogModel,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LeadEvents::COMPANY_POST_SAVE   => ['onCompanyPostSave', 0],
            LeadEvents::COMPANY_POST_DELETE => ['onCompanyDelete', 0],
        ];
    }

    /**
     * Add a company entry to the audit log.
     */
    public function onCompanyPostSave(Events\CompanyEvent $event): void
    {
        $company = $event->getCompany();
        if ($details = $event->getChanges()) {
            $log = [
                'bundle'    => 'lead',
                'object'    => 'company',
                'objectId'  => $company->getId(),
                'action'    => ($event->isNew()) ? 'create' : 'update',
                'details'   => $details,
                'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
            ];
            $this->auditLogModel->writeToLog($log);
        }
    }

    /**
     * Add a company delete entry to the audit log.
     */
    public function onCompanyDelete(Events\CompanyEvent $event): void
    {
        $company = $event->getCompany();
        $log     = [
            'bundle'    => 'lead',
            'object'    => 'company',
            'objectId'  => $company->deletedId,
            'action'    => 'delete',
            'details'   => ['name', $company->getPrimaryIdentifier()],
            'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
        ];
        $this->auditLogModel->writeToLog($log);
    }
}
