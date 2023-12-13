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
        $this->addStandardPermissions('categories');
    }

    public function getName(): string
    {
        return 'campaign';
    }

    public function buildForm(FormBuilderInterface &$builder, array $options, array $data): void
    {
        $this->addStandardFormFields('campaign', 'categories', $builder, $data);
        $this->addExtendedFormFields('campaign', 'campaigns', $builder, $data);
    }
}
