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

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\LeadBundle\Model\CompanyModel;

/**
 * Class IdentifyCompanyHelper.
 */
class IdentifyCompanyHelper
{
    /**
     * @var MauticFactory
     */
    private $factory;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @param array        $parameters
     * @param mixed        $lead
     * @param CompanyModel $companyModel
     *
     * @return array
     */
    public static function identifyLeadsCompany($parameters, $lead, CompanyModel $companyModel)
    {
        $companyName = $companyDomain = null;
        $leadAdded   = false;

        if (isset($parameters['company'])) {
            $companyName = filter_var($parameters['company'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        } elseif (isset($parameters['email'])) {
            $companyName = $companyDomain = self::domainExists($parameters['email']);
        } elseif (isset($parameters['companyname'])) {
            $companyName = filter_var($parameters['companyname'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        }

        if ($companyName) {
            $companyRepo = $companyModel->getRepository();

            $city    = isset($parameters['city']) ? $parameters['city'] : null;
            $country = isset($parameters['country']) ? $parameters['country'] : null;
            $state   = isset($parameters['state']) ? $parameters['state'] : null;

            $companyEntity = $companyRepo->identifyCompany($companyName, $city, $country, $state);

            if (!empty($company)) {
                if ($lead) {
                    $companyLeadRepo = $companyModel->getCompanyLeadRepository();
                    if (empty($companyLeadRepo->getCompaniesByLeadId($lead->getId(), $company['id']))) {
                        $leadAdded = true;
                    }
                }
            } else {
                //create new company
                $company = [
                    'companyname'    => $companyName,
                    'companywebsite' => $companyDomain,
                    'companycity'    => $city,
                    'companystate'   => $state,
                    'companycountry' => $country,
                ];
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

        return [null, false];
    }

    /**
     * @param $email
     *
     * @return mixed
     */
    private function domainExists($email)
    {
        list($user, $domain) = explode('@', $email);
        $arr                 = dns_get_record($domain, DNS_MX);
        if ($arr[0]['host'] == $domain && !empty($arr[0]['target'])) {
            return $arr[0]['target'];
        }
    }
}
