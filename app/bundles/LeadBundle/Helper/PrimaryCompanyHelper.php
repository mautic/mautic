<?php

namespace Mautic\LeadBundle\Helper;

use Mautic\LeadBundle\Entity\CompanyLeadRepository;
use Mautic\LeadBundle\Entity\Lead;

class PrimaryCompanyHelper
{
    public function __construct(
        private CompanyLeadRepository $companyLeadRepository,
    ) {
    }

    /**
     * @return array|null
     */
    public function getProfileFieldsWithPrimaryCompany(Lead $lead)
    {
        return $this->mergeInPrimaryCompany(
            $this->companyLeadRepository->getCompaniesByLeadId($lead->getId()),
            $lead->getProfileFields()
        );
    }

    /**
     * @return array
     */
    public function mergePrimaryCompanyWithProfileFields($contactId, array $profileFields)
    {
        return $this->mergeInPrimaryCompany(
            $this->companyLeadRepository->getCompaniesByLeadId($contactId),
            $profileFields
        );
    }

    /**
     * @return array
     */
    private function mergeInPrimaryCompany(array $companies, array $profileFields)
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
