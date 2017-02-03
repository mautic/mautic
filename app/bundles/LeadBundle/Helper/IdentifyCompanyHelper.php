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

        if (!empty($copmany)) {
            if (!empty($companyEntities)) {
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
                $companyEntity = $companyModel->getEntity();
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
     * @param              $parameters
     * @param CompanyModel $companyModel
     *
     * @return array
     */
    public static function findCompany($parameters, CompanyModel $companyModel)
    {
        $companyName   = $companyDomain   = null;
        $companyEntity = null;

        if (isset($parameters['company'])) {
            $companyName = filter_var($parameters['company']);
        } elseif (isset($parameters['email'])) {
            $companyName = $companyDomain = self::domainExists($parameters['email']);
        } elseif (isset($parameters['companyname'])) {
            $companyName = filter_var($parameters['companyname']);
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

            $company = [
                'companyname'    => $companyName,
                'companywebsite' => $companyDomain,
                'companycity'    => $parameters['companycity'],
                'companystate'   => $parameters['companystate'],
                'companycountry' => $parameters['companycountry'],
            ];

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
     * @param $email
     *
     * @return mixed
     */
    private static function domainExists($email)
    {
        list($user, $domain) = explode('@', $email);
        $arr                 = dns_get_record($domain, DNS_MX);
        if ($arr[0]['host'] == $domain && !empty($arr[0]['target'])) {
            return $arr[0]['target'];
        }
    }

    /**
     * @param $field
     * @param $parameters
     * @param $filter
     */
    private static function setCompanyFilter($field, &$parameters, &$filter)
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
