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
}
