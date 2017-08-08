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
        // Push lead to integration
    }

    public function getDataPriority()
    {
        return true;
    }

    public function getAvailableLeadFields($settings = [])
    {
        return [
            'email' => [
                'label' => 'Email address',
                'required' => true,
            ],
            'firstname' => [
                'label' => 'First name',
                'required' => false,
            ],
            'lastname' => [
                'label' => 'Last name',
                'required' => false,
            ],
        ];
    }

    public function getFormLeadFields($settings = []) {
        return $this->getAvailableLeadFields($settings);
    }
}
