<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Helper;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\CompanyModel;

/**
 * Class IdentifyCompanyHelper
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
    public function __construct (MauticFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @param array $parameters
     * @param Lead $lead
     * @param CompanyModel $companyModel
     * @return mixed
     *
     */
    static public function identifyLeadsCompany ($parameters = array(), Lead $lead, CompanyModel $companyModel)
    {
        $companyName = $companyDomain = null;
        $leadAdded = false;

        if (isset($parameters['company'])) {
            $companyName = filter_var($parameters['company'], FILTER_SANITIZE_SPECIAL_CHARS);
        } elseif(isset($parameters['email'])) {
            $companyName = $companyDomain = IdentifyCompanyHelper::domain_exists($parameters['email']);
        }
        if ($companyName and isset($parameters['city']) and isset($parameters['country'])) {
            $companyRepo = $companyModel->getRepository();

            $city = $parameters['city'];
            $country = $parameters['country'];
            $state = null;

            if (isset($parameters['state'])) {
                $state = $parameters['state'];
            }
            $company = $companyRepo->identifyCompany($companyName, $city, $country, $state);
            if (!empty($company)) {
                //check if lead is already assigned to company
                $companyLeadRepo = $companyModel->getCompanyLeadRepository();
                if (empty($companyLeadRepo->getCompaniesByLeadId($lead->getId(),$company[0]['id']))) {
                    $companyLeadRepo->getCompaniesByLeadId($lead->getId(),$company[0]['id']);
                    $company = $companyModel->getEntity($company[0]['id']);
                    $companyModel->addLeadToCompany($company,$lead);
                    $leadAdded = true;
                }
            } else {
                //create new company
                $companyData = [
                    'companyname'    => $companyName,
                    'companywebsite' => $companyDomain,
                    'companycity'    => $city,
                    'companystate'   => $state,
                    'companycountry' => $country
                ];
                $entity = $companyModel->getEntity();
                $companyModel->setFieldValues($entity, $companyData, true);
                $companyModel->saveEntity($entity);
                $companyModel->addLeadToCompany($entity,$lead);
                $leadAdded = true;
            }
            return [$company, $leadAdded];
        }
        return false;
    }

    private function domain_exists($email){
        list($user, $domain) = explode('@', $email);
        $arr= dns_get_record($domain,DNS_MX);
        if($arr[0]['host']==$domain&&!empty($arr[0]['target'])){
                return $arr[0]['target'];
        }
    }
}
