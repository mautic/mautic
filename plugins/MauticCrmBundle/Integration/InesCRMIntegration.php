<?php

namespace MauticPlugin\MauticCrmBundle\Integration;

use Mautic\LeadBundle\Entity\Lead;

class InesCRMIntegration extends CrmAbstractIntegration
{
    public function getName()
    {
        return 'InesCRM';
    }

    public function getDisplayName()
    {
        return 'Ines CRM';
    }

    public function getRequiredKeyFields()
    {
        return [
            'account'  => 'mautic.ines_crm.form.account',
            'username' => 'mautic.ines_crm.form.username',
            'password' => 'mautic.ines_crm.form.password',
        ];
    }

    public function getSecretKeys()
    {
        return [
            'password',
        ];
    }

    public function getSupportedFeatures()
    {
        return ['push_lead', 'push_leads'];
    }

    public function pushLead($lead, $config = []) {
        $config = $this->mergeConfigToFeatureSettings($config);

        $leadFields = $config['leadFields'];
        $mappedData = [];

        foreach ($leadFields as $integrationField => $mauticField) {
            $method = 'get' . ucfirst($mauticField);
            $mappedData[$integrationField] = $lead->$method();
        }

        $this->getApiHelper()->createLead($mappedData);
    }

    public function pushLeads($params = []) {
        $config                  = $this->mergeConfigToFeatureSettings();
        list($fromDate, $toDate) = $this->getSyncTimeframeDates($params);
        $fetchAll                = $params['fetchAll'];
        $limit                   = $params['limit'];

        $leadRepo = $this->em->getRepository(Lead::class);
        $qb = $leadRepo->createQueryBuilder('l');
        $qb->where('l.email is not null')->andWhere('l.email != \'\'');

        if (!$fetchAll) {
            $qb->andWhere('l.dateAdded >= :fromDate')
               ->andWhere('l.dateAdded <= :toDate')
               ->setParameters(compact('fromDate', 'toDate'));
       }

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        $iterableLeads = $qb->getQuery()->iterate();

        foreach($iterableLeads as $lead) {
            $this->pushLead($lead[0]);
        }
    }

    public function getDataPriority()
    {
        return true;
    }

    public function getFormLeadFields($settings = []) {
        return [
            'ines_email' => [
                'label' => 'Email address',
                'required' => true,
            ],
            'ines_firstname' => [
                'label' => 'First name',
                'required' => false,
            ],
            'ines_lastname' => [
                'label' => 'Last name',
                'required' => false,
            ],
        ];
    }

    public function getFormCompanyFields($settings = []) {
        return [
            'ines_company_name' => [
                'label' => 'Company Name',
                'required' => false,
            ],
            'ines_company_address' => [
                'label' => 'Company Address',
                'required' => false,
            ],
        ];
    }

    public function appendToForm(&$builder, $data, $formArea)
    {
        if ($formArea == 'features') {
            $builder->add('objects', 'choice', [
                'choices' => [
                    'lead'    => 'mautic.ines_crm.object.lead',
                    'company' => 'mautic.ines_crm.object.company',
                ],
                'expanded'    => true,
                'multiple'    => true,
                'label'       => 'mautic.ines_crm.form.objects_to_push_to',
                'label_attr'  => ['class' => ''],
                'empty_value' => false,
                'required'    => false,
            ]);
        }
    }
}
