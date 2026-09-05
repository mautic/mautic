<?php

namespace Mautic\LeadBundle\Helper;

use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\CompanyLeadRepository;
use Mautic\LeadBundle\Exception\UniqueFieldNotFoundException;
use Mautic\LeadBundle\Model\CompanyModel;

final readonly class IdentifyCompanyHelper
{
    public function __construct(
        private CompanyModel $companyModel,
        private CompanyLeadRepository $companyLeadRepository,
    ) {
    }

    /**
     * @param mixed $lead
     */
    public function identifyLeadsCompany(array $data, $lead): array
    {
        $addContactToCompany = true;

        $parameters = $this->normalizeParameters($data);

        if (!$this->hasCompanyParameters($parameters)) {
            return [null, false, null];
        }

        try {
            $companies = $this->companyModel->checkForDuplicateCompanies($parameters);
        } catch (UniqueFieldNotFoundException) {
            return [null, false, null];
        }

        if ([] !== $companies) {
            $companyEntity = end($companies);
            $companyData   = $companyEntity->getProfileFields();

            if ($lead) {
                $companyLead     = $this->companyLeadRepository->getCompaniesByLeadId($lead->getId(), $companyEntity->getId());
                if ([] !== $companyLead) {
                    $addContactToCompany = false;
                }
            }
        } else {
            $companyData = $parameters;

            // create new company
            $companyEntity = new Company();
            $this->companyModel->setFieldValues($companyEntity, $companyData, true);
            $this->companyModel->saveEntity($companyEntity);
            $companyData['id'] = $companyEntity->getId();
        }

        return [$companyData, $addContactToCompany, $companyEntity];
    }

    public function findCompany(array $data): array
    {
        $parameters = $this->normalizeParameters($data);

        if (!$this->hasCompanyParameters($parameters)) {
            return [[], []];
        }

        try {
            $companyEntities = $this->companyModel->checkForDuplicateCompanies($parameters);
        } catch (UniqueFieldNotFoundException) {
            return [[], []];
        }

        $companyData     = $parameters;
        if ([] !== $companyEntities) {
            $key               = array_key_last($companyEntities);
            $companyData['id'] = $companyEntities[$key]->getId();
        }

        return [$companyData, $companyEntities];
    }

    private function hasCompanyParameters(array $parameters): bool
    {
        $companyFields = $this->companyModel->fetchCompanyFields();
        foreach ($parameters as $alias => $value) {
            foreach ($companyFields as $companyField) {
                if ($companyField['alias'] === $alias) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $parameters
     *
     * @return mixed[]
     */
    private function normalizeParameters(array $parameters): array
    {
        if (isset($parameters['company'])) {
            $parameters['companyname'] = filter_var($parameters['company']);
            unset($parameters['company']);
        }

        $fields= ['country', 'city', 'state'];
        foreach ($fields as $field) {
            if (isset($parameters[$field]) && !isset($parameters['company'.$field])) {
                $parameters['company'.$field] = $parameters[$field];
                unset($parameters[$field]);
            }
        }

        return $parameters;
    }
}
