<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Helper;

use Mautic\LeadBundle\Entity\CompanyLeadRepository;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;

class PrimaryCompanyHelper
{
    private $companyLeadRepository;

    /**
     * PrimaryCompanyHelper constructor.
     *
     * @param LeadRepository $companyLeadRepository
     */
    public function __construct(CompanyLeadRepository $companyLeadRepository)
    {
        $this->companyLeadRepository = $companyLeadRepository;
    }

    /**
     * @param Lead $lead
     *
     * @return array
     */
    public function getProfileFieldsWithPrimaryCompany(Lead $lead)
    {
        return $this->mergeInPrimaryCompany(
            $this->companyLeadRepository->getCompaniesByLeadId($lead->getId()),
            $lead->getProfileFields()
        );
    }

    /**
     * @param       $contactId
     * @param array $profileFields
     *
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
     * @param array $companies
     * @param array $profileFields
     *
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
