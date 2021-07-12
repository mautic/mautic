<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Helper;

use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Exception\UniqueFieldNotFoundException;
use Mautic\LeadBundle\Model\CompanyModel;

/**
 * Class IdentifyCompanyHelper.
 */
class IdentifyCompanyHelper
{
    /**
     * @param array $data
     * @param mixed $lead
     *
     * @return array
     */
    public static function identifyLeadsCompany($data, $lead, CompanyModel $companyModel)
    {
        $addContactToCompany = true;

        $parameters = self::normalizeParameters($data);

        if (!self::hasCompanyParameters($parameters, $companyModel)) {
            return [null, false, null];
        }

        $companies = $companyModel->checkForDuplicateCompanies($parameters);
        if (!empty($companies)) {
            $companyEntity = end($companies);
            $companyData   = $companyEntity->getProfileFields();

            if ($lead) {
                $companyLeadRepo = $companyModel->getCompanyLeadRepository();
                $companyLead     = $companyLeadRepo->getCompaniesByLeadId($lead->getId(), $companyEntity->getId());
                if (!empty($companyLead)) {
                    $addContactToCompany = false;
                }
            }
        } else {
            $companyData = $parameters;

            //create new company
            $companyEntity = new Company();
            $companyModel->setFieldValues($companyEntity, $companyData, true);
            $companyModel->saveEntity($companyEntity);
            $companyData['id'] = $companyEntity->getId();
        }

        return [$companyData, $addContactToCompany, $companyEntity];
    }

    /**
     * @return array
     */
    public static function findCompany(array $data, CompanyModel $companyModel)
    {
        $parameters = self::normalizeParameters($data);

        if (!self::hasCompanyParameters($parameters, $companyModel)) {
            return [[], []];
        }

        try {
            $companyEntities = $companyModel->checkForDuplicateCompanies($parameters);
        } catch (UniqueFieldNotFoundException $uniqueFieldNotFoundException) {
            return [[], []];
        }

        $companyData     = $parameters;
        if (!empty($companyEntities)) {
            end($companyEntities);
            $key               = key($companyEntities);
            $companyData['id'] = $companyEntities[$key]->getId();
        }

        return [$companyData, $companyEntities];
    }

    private static function hasCompanyParameters(array $parameters, CompanyModel $companyModel)
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

    private static function normalizeParameters(array $parameters)
    {
        $companyName   = null;
        $companyDomain = null;

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

    /**
     * Checks if email address' domain has a DNS MX record. Returns the domain if found.
     *
     * @param string $email
     *
     * @return string|false
     */
    protected static function domainExists($email)
    {
        if (!strstr($email, '@')) { //not a valid email adress
            return false;
        }

        [$user, $domain]     = explode('@', $email);
        $arr                 = dns_get_record($domain, DNS_MX);

        if (empty($arr)) {
            return false;
        }

        if ($arr[0]['host'] === $domain) {
            return $domain;
        }

        return false;
    }
}
