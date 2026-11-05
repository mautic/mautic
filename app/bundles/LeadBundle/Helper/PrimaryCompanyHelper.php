<?php

namespace Mautic\LeadBundle\Helper;

use Mautic\LeadBundle\Entity\CompanyLeadRepository;
use Mautic\LeadBundle\Entity\Lead;

final readonly class PrimaryCompanyHelper
{
    public function __construct(
        private CompanyLeadRepository $companyLeadRepository,
    ) {
    }

    public function getProfileFieldsWithPrimaryCompany(Lead $lead): array
    {
        return $this->mergeInPrimaryCompany(
            $this->companyLeadRepository->getCompaniesByLeadId($lead->getId()),
            $lead->getProfileFields()
        );
    }

    public function mergePrimaryCompanyWithProfileFields($contactId, array $profileFields): array
    {
        return $this->mergeInPrimaryCompany(
            $this->companyLeadRepository->getCompaniesByLeadId($contactId),
            $profileFields
        );
    }

    private function mergeInPrimaryCompany(array $companies, array $profileFields): array
    {
        foreach ($companies as $company) {
            if (empty($company['is_primary'])) {
                continue;
            }

            unset($company['id'], $company['score'], $company['date_added'], $company['date_associated'], $company['is_primary']);

            return array_merge($profileFields, $company);
        }

        return $profileFields;
    }
}
