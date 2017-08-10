<?php

namespace MauticPlugin\MauticCrmBundle\Integration;

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
        return ['push_lead'];
    }

    public function pushLead($lead, $config = []) {
        $config = $this->mergeConfigToFeatureSettings($config);

        $leadFields = $config['leadFields'];
        $mappedData = [];

        foreach ($leadFields as $integrationField => $mauticField) {
            $mappedData[$integrationField] = $lead->getFieldValue($mauticField);
        }

        $this->getApiHelper()->createLead($mappedData);
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
