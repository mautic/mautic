<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Security\Permissions;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;
use Symfony\Component\Form\FormBuilderInterface;

class MarketplacePermissions extends AbstractPermissions
{
    public const BASE                 = 'marketplace';
    public const PACKAGES             = 'packages';
    public const CAN_VIEW_PACKAGES    = self::BASE.':'.self::PACKAGES.':view';
    public const CAN_INSTALL_PACKAGES = self::BASE.':'.self::PACKAGES.':create';

    public function __construct(CoreParametersHelper $coreParametersHelper)
    {
        parent::__construct($coreParametersHelper->all());
    }

    public function definePermissions()
    {
        $this->addStandardPermissions(self::PACKAGES, false);
    }

    public function getName()
    {
        return self::BASE;
    }

    public function buildForm(FormBuilderInterface &$builder, array $options, array $data)
    {
        $this->addStandardFormFields(self::BASE, self::PACKAGES, $builder, $data, false);
    }
}
