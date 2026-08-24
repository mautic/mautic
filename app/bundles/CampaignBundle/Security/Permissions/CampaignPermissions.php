<?php

namespace Mautic\CampaignBundle\Security\Permissions;

use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;
use Symfony\Component\Form\FormBuilderInterface;

class CampaignPermissions extends AbstractPermissions
{
    public const LEADS = 'campaign:leads';

    public const LEADS_ADD_OWN   = 'campaign:leads:addown';

    public const LEADS_ADD_OTHER = 'campaign:leads:addother';

    /**
     * @param mixed[] $params
     */
    public function __construct(array $params)
    {
        parent::__construct($params);
        $this->addExtendedPermissions('campaigns');
        $this->addCustomPermission('leads', ['addown' => 2, 'addother' => 4]);
        $this->addStandardPermissions(['categories']);
        $this->addStandardPermissions(['imports']);
        $this->addCustomPermission('export', ['enable' => 1024]);
    }

    public function getName(): string
    {
        return 'campaign';
    }

    public function buildForm(FormBuilderInterface &$builder, array $options, array $data): void
    {
        $this->addStandardFormFields('campaign', 'categories', $builder, $data);
        $this->addExtendedFormFields('campaign', 'campaigns', $builder, $data);
        $this->addCustomFormFields(
            $this->getName(),
            'leads',
            $builder,
            'mautic.campaign.permissions.leads',
            [
                'mautic.campaign.permissions.addown'   => 'addown',
                'mautic.campaign.permissions.addother' => 'addother',
            ],
            $data
        );
        $this->addCustomFormFields(
            $this->getName(),
            'export',
            $builder,
            'mautic.core.permissions.export',
            ['mautic.core.permissions.enable' => 'enable'],
            $data
        );
        $this->addStandardFormFields($this->getName(), 'imports', $builder, $data);
    }

    /**
     * @param mixed[] $allPermissions
     */
    public function analyzePermissions(array &$permissions, $allPermissions, $isSecondRound = false): bool
    {
        parent::analyzePermissions($permissions, $allPermissions, $isSecondRound);

        if (isset($permissions['leads'])
            && in_array('addother', $permissions['leads'])
            && !in_array('addown', $permissions['leads'])
        ) {
            $permissions['leads'][] = 'addown';
        }

        return false;
    }
}
