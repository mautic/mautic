<?php

namespace Mautic\LeadBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Event\EntityPostDelete;
use Mautic\CoreBundle\Event\EntityPostSave;
use Mautic\CoreBundle\Event\EntityPreSave;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\CompanyLeadRepository;
use Mautic\LeadBundle\Event as Events;
use Mautic\LeadBundle\Event\CompanySoftDeleteEvent;
use Mautic\LeadBundle\Model\CompanyModel;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

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
    #[AsEventListener]
    public function onCompanyPostSave(EntityPostSave $event): void
    {
        $company = $event->getEntity();
        if (!$company instanceof Company) {
            return;
        }

        if ($details = $event->getChanges()) {
            $log = [
                'bundle'    => 'lead',
                'object'    => 'company',
                'objectId'  => $company->getId(),
                'action'    => ($event->isNew()) ? 'create' : 'update',
                'details'   => $details,
                // No IP for a form submitter Mautic doesn't track
                'ipAddress' => $this->ipLookupHelper->isVisitorUntracked() ? '' : $this->ipLookupHelper->getIpAddressFromRequest(),
            ];
            $this->auditLogModel->writeToLog($log);
        }
    }

    /**
     * Add a company delete entry to the audit log.
     */
    #[AsEventListener]
    public function onCompanyDelete(EntityPostDelete $event): void
    {
        $company = $event->getEntity();
        if (!$company instanceof Company) {
            return;
        }

        $log = [
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

    #[AsEventListener(
        event: CompanySoftDeleteEvent::class,
    )]
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

    #[AsEventListener]
    public function onCompanyPreSave(EntityPreSave $event): void
    {
        $company = $event->getEntity();
        if (!$company instanceof Company) {
            return;
        }

        if ($this->coreParametersHelper->get('update_company_mapping_data_in_background', false)) {
            return;
        }
        if ($company->isNew() || empty($company->getChanges()['fields']['companyname'])) {
            return;
        }
        $this->companyLeadRepository->updateLeadsPrimaryCompanyName($company);
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
