<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Security\Permissions;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;
use Mautic\MarketplaceBundle\Service\Config;
use Symfony\Component\Form\FormBuilderInterface;

class MarketplacePermissions extends AbstractPermissions
{
    public const BASE                 = 'marketplace';
    public const PACKAGES             = 'packages';
    public const CAN_VIEW_PACKAGES    = self::BASE.':'.self::PACKAGES.':view';
    public const CAN_INSTALL_PACKAGES = self::BASE.':'.self::PACKAGES.':create';

    private Config $config;

    public function __construct(CoreParametersHelper $coreParametersHelper, Config $config)
    {
        parent::__construct($coreParametersHelper->all());
        $this->config = $config;
    }

    public function definePermissions()
    {
        $this->addStandardPermissions(self::PACKAGES, false);
    }

    public function isEnabled()
    {
        return $this->config->marketplaceIsEnabled();
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
