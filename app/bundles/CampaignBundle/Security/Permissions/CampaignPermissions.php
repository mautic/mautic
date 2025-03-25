<?php

namespace Mautic\CampaignBundle\Security\Permissions;

use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;
use Symfony\Component\Form\FormBuilderInterface;

class CampaignPermissions extends AbstractPermissions
{
    public function __construct($params)
    {
        parent::__construct($params);
        $this->addExtendedPermissions('campaigns');
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
            'export',
            $builder,
            'mautic.core.permissions.export',
            ['mautic.core.permissions.enable' => 'enable'],
            $data
        );
        $this->addStandardFormFields($this->getName(), 'imports', $builder, $data);
    }
}
