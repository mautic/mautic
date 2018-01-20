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
     * @param array        $parameters
     * @param mixed        $lead
     * @param CompanyModel $companyModel
     *
     * @return array
     */
    public static function identifyLeadsCompany($parameters, $lead, CompanyModel $companyModel)
    {
        list($company, $companyEntities) = self::findCompany($parameters, $companyModel);
        if (!empty($company)) {
            $leadAdded = false;
            if (count($companyEntities)) {
                foreach ($companyEntities as $entity) {
                    $companyEntity   = $entity;
                    $companyLeadRepo = $companyModel->getCompanyLeadRepository();
                    if ($lead) {
                        $companyLead = $companyLeadRepo->getCompaniesByLeadId($lead->getId(), $entity->getId());
                        if (empty($companyLead)) {
                            $leadAdded = true;
                        }
                    }
                }
            } else {
                //create new company
                $companyEntity = new Company();
                $companyModel->setFieldValues($companyEntity, $company, true);
                $companyModel->saveEntity($companyEntity);
                $company['id'] = $companyEntity->getId();
                if ($lead) {
                    $leadAdded = true;
                }
            }

            return [$company, $leadAdded, $companyEntity];
        }

        return [null, false, null];
    }

    /**
     * @param array        $parameters
     * @param CompanyModel $companyModel
     *
     * @return array
     */
    public static function findCompany(array $parameters, CompanyModel $companyModel)
    {
        $companyName   = null;
        $companyDomain = null;
        $companyEntity = null;

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

        if ($companyName) {
            $filter['force'] = [
                'column' => 'companyname',
                'expr'   => 'eq',
                'value'  => $companyName,
            ];

            self::setCompanyFilter('city', $parameters, $filter);
            self::setCompanyFilter('state', $parameters, $filter);
            self::setCompanyFilter('country', $parameters, $filter);

            $companyEntities = $companyModel->getEntities(
                [
                    'limit'          => 1,
                    'filter'         => ['force' => [$filter['force']]],
                    'withTotalCount' => false,
                ]
            );

            $company = array_merge([
                'companyname'    => $companyName,
                'companywebsite' => $companyDomain,
            ], $parameters);

            if (1 === count($companyEntities)) {
                end($companyEntities);
                $key           = key($companyEntities);
                $company['id'] = $companyEntities[$key]->getId();
            }

            return [$company, $companyEntities];
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
        list($user, $domain) = explode('@', $email);
        $arr                 = dns_get_record($domain, DNS_MX);

        if ($arr && $arr[0]['host'] === $domain) {
            return $domain;
        }

        return false;
    }

    /**
     * @param string $field
     * @param array  $parameters
     * @param array  $filter
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
