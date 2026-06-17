<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Command;

use Mautic\CoreBundle\Helper\ExitCode;
use Mautic\LeadBundle\Entity\CompanyLeadRepository;
use Mautic\LeadBundle\Entity\CompanyRepository;
use Mautic\LeadBundle\Model\CompanyModel;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: DeleteCompanyLeads::COMMAND_NAME,
    description: 'Delete Company referance from leads and update leads with new primary company.'
)]
final class DeleteCompanyLeads
{
    public const COMMAND_NAME = 'mautic:company:delete_company_leads';

    public function __construct(
        private CompanyLeadRepository $companyLeadRepository,
        private CompanyRepository $companyRepository,
        private CompanyModel $companyModel,
    ) {
    }

    public function __invoke(
        OutputInterface $output,
        #[Option(description: 'Company id to delete references.', shortcut: 'i')]
        ?int $companyId = null,
    ): int {
        if ($companyId) {
            $this->processDeleteCompany($companyId, $output);

            return ExitCode::SUCCESS;
        }

        foreach ($this->companyRepository->getDeletedCompanies() as $company) {
            $this->processDeleteCompany($company->getId(), $output);
        }

        return ExitCode::SUCCESS;
    }

    private function processDeleteCompany(int $companyId, OutputInterface $output): void
    {
        $output->writeln("<info>Updating with new primary company for company id $companyId which has been deleted.</info>");
        $this->companyModel->changePrimaryCompanyToLatest($companyId);

        $output->writeln("<info>Deleting all the company lead mapping for company id $companyId which has been deleted.</info>");
        $this->companyLeadRepository->deleteCompanyLeads($companyId);

        $output->writeln("<info>Deleting company for company id $companyId permanently.</info>");
        $this->companyModel->deleteCompanyPermanently($companyId);
    }
}
