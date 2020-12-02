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

        $companies = $companyModel->checkForDuplicateCompanies($parameters);
        if (!empty($companies)) {
            end($companies);
            $companyEntity = key($companies);
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

    private static function normalizeParameters(array $parameters)
    {
        if (isset($parameters['company'])) {
            $companyName = filter_var($parameters['company']);
        } elseif (isset($parameters['companyname'])) {
            $companyName = filter_var($parameters['companyname']);
        }

        if (isset($parameters['email']) || isset($parameters['companyemail'])) {
            $companyDomain = isset($parameters['email']) ? self::domainExists($parameters['email']) : self::domainExists($parameters['companyemail']);
        }

        if (empty($parameters['companywebsite']) && !empty($parameters['companyemail'])) {
            $companyDomain = self::domainExists($parameters['companyemail']);
        }

        $fields= ['country', 'city', 'state'];
        foreach ($fields as $field) {
            if (isset($parameters[$field]) && !isset($parameters['company'.$field])) {
                $parameters['company'.$field] = $parameters[$field];
                unset($parameters[$field]);
            }
        }

        return array_merge([
            'companyname'    => $companyName,
            'companywebsite' => $companyDomain,
        ], $parameters);
    }

    /**
     * @return array
     */
    public static function findCompany(array $data, CompanyModel $companyModel)
    {
        $parameters = self::normalizeParameters($data);

        $duplicateCompanies = $companyModel->checkForDuplicateCompanies($parameters);
        if (!empty($duplicateCompanies)) {
        }

        return [[], []];
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

    /**
     * @param string $field
     */
    private static function setCompanyFilter($field, array &$parameters, array &$filter)
    {
        if (isset($parameters[$field]) || isset($parameters['company'.$field])) {
            if (!isset($parameters['company'.$field])) {
                $parameters['company'.$field] = $parameters[$field];
            }

            $filter['force'][] = [
                'column' => 'company'.$field,
                'expr'   => 'eq',
                'value'  => $parameters['company'.$field],
            ];
        } else {
            $parameters['company'.$field] = '';
        }
    }
}
