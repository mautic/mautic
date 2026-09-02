<?php

namespace Mautic\LeadBundle\Helper;

use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Exception\UniqueFieldNotFoundException;
use Mautic\LeadBundle\Model\CompanyModel;

final class IdentifyCompanyHelper
{
    /**
     * @param mixed $lead
     */
    public static function identifyLeadsCompany(array $data, $lead, CompanyModel $companyModel): array
    {
        $addContactToCompany = true;

        $parameters = self::normalizeParameters($data);

        if (!self::hasCompanyParameters($parameters, $companyModel)) {
            return [null, false, null];
        }

        try {
            $companies = $companyModel->checkForDuplicateCompanies($parameters);
        } catch (UniqueFieldNotFoundException) {
            return [null, false, null];
        }

        if ([] !== $companies) {
            $companyEntity = end($companies);
            $companyData   = $companyEntity->getProfileFields();

            if ($lead) {
                $companyLeadRepo = $companyModel->getCompanyLeadRepository();
                $companyLead     = $companyLeadRepo->getCompaniesByLeadId($lead->getId(), $companyEntity->getId());
                if ([] !== $companyLead) {
                    $addContactToCompany = false;
                }
            }
        } else {
            $companyData = $parameters;

            // create new company
            $companyEntity = new Company();
            $companyModel->setFieldValues($companyEntity, $companyData, true);
            $companyModel->saveEntity($companyEntity);
            $companyData['id'] = $companyEntity->getId();
        }

        return [$companyData, $addContactToCompany, $companyEntity];
    }

    public static function findCompany(array $data, CompanyModel $companyModel): array
    {
        $parameters = self::normalizeParameters($data);

        if (!self::hasCompanyParameters($parameters, $companyModel)) {
            return [[], []];
        }

        try {
            $companyEntities = $companyModel->checkForDuplicateCompanies($parameters);
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

    private static function hasCompanyParameters(array $parameters, CompanyModel $companyModel): bool
    {
        $companyFields = $companyModel->fetchCompanyFields();
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
    private static function normalizeParameters(array $parameters): array
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
