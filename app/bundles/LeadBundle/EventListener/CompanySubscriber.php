<?php

namespace Mautic\LeadBundle\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\LeadBundle\Entity\CompanyLeadRepository;
use Mautic\LeadBundle\Event as Events;
use Mautic\LeadBundle\Event\CompanyPostDeleteEvent;
use Mautic\LeadBundle\Event\CompanyPostSaveEvent;
use Mautic\LeadBundle\Event\CompanyPreSaveEvent;
use Mautic\LeadBundle\Event\CompanySoftDeleteEvent;
use Mautic\LeadBundle\Model\CompanyModel;

final readonly class CompanySubscriber
{
    public function __construct(
        private IpLookupHelper $ipLookupHelper,
        private AuditLogModel $auditLogModel,
        private EntityManagerInterface $entityManager,
        private CoreParametersHelper $coreParametersHelper,
        private CompanyLeadRepository $companyLeadRepository,
        private CompanyModel $companyModel,
    ) {
    }

    /**
     * Add a company entry to the audit log.
     */
    #[AsEventListener(event: CompanyPostSaveEvent::class)]
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
    #[AsEventListener(event: CompanyPostDeleteEvent::class)]
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
        $this->clearCompanyInLeadsCompanyFields($company->getName());
    }

    #[AsEventListener(event: CompanySoftDeleteEvent::class)]
    public function onCompanySoftDelete(Events\CompanyEvent $event): void
    {
        if ($details = $event->getChanges()) {
            $log = [
                'bundle'    => 'lead',
                'object'    => 'company',
                'objectId'  => $event->getCompany()->getId(),
                'action'    => 'update',
                'details'   => $details,
                'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
            ];
            $this->auditLogModel->writeToLog($log);
        }

        if ($this->coreParametersHelper->get('update_company_mapping_data_in_background', false)) {
            return;
        }
        $this->companyModel->changePrimaryCompanyToLatest($event->getCompany()->getId());
        $this->companyLeadRepository->deleteCompanyLeads($event->getCompany()->getId());
        $this->companyModel->permanentDeleteCompany($event->getCompany());
    }

    #[AsEventListener(event: CompanyPreSaveEvent::class)]
    public function onCompanyPreSave(Events\CompanyEvent $event): void
    {
        if ($this->coreParametersHelper->get('update_company_mapping_data_in_background', false)) {
            return;
        }
        if ($event->getCompany()->isNew() || empty($event->getCompany()->getChanges()['fields']['companyname'])) {
            return;
        }
        $this->companyLeadRepository->updateLeadsPrimaryCompanyName($event->getCompany());
    }

    private function clearCompanyInLeadsCompanyFields(?string $companyName): void
    {
        if (null === $companyName) {
            return;
        }
        $connection = $this->entityManager->getConnection();
        $connection->executeStatement(
            'UPDATE '.MAUTIC_TABLE_PREFIX.'leads SET company = NULL WHERE company = :companyName',
            ['companyName' => $companyName]
        );
    }
}
