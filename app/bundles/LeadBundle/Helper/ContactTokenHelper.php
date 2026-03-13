<?php

namespace Mautic\LeadBundle\Helper;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\CompanyBundle\Entity\Company;
use Mautic\CompanyBundle\Model\CompanyModel;

class ContactTokenHelper
{
    /**
     * @var CompanyModel
     */
    private $companyModel;

    /**
     * @param CompanyModel $companyModel
     */
    public function __construct(CompanyModel $companyModel)
    {
        $this->companyModel = $companyModel;
    }

    /**
     * Get contact field value, including company fields.
     *
     * @param string $field
     * @param Lead $contact
     * @return mixed
     */
    public function getContactFieldValue($field, Lead $contact)
    {
        // Check if this is a company field
        if ($this->isCompanyField($field)) {
            $company = $this->getCompany($contact);
            if ($company) {
                return $this->getCompanyFieldValue($company, $field);
            }
            return null;
        }
        
        // Regular contact field logic
        return $contact->getFieldValue($field);
    }

    /**
     * Check if field is a company field.
     *
     * @param string $field
     * @return bool
     */
    private function isCompanyField($field)
    {
        $companyFields = [
            'companyname',
            'companyemail',
            'companyphone',
            'companywebsite',
            'companyaddress',
            'companycity',
            'companystate',
            'companyzipcode',
            'companycountry',
            'companyindustry',
            'companynumberofemployees',
            'companyfax',
            'companydescription',
        ];
