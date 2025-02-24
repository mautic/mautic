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

    public const CAN_REMOVE_PACKAGES  = self::BASE.':'.self::PACKAGES.':remove';

    public function __construct(
        CoreParametersHelper $coreParametersHelper,
        private Config $config
    ) {
        parent::__construct($coreParametersHelper->all());
    }

    public function definePermissions(): void
    {
        $this->addStandardPermissions(self::PACKAGES, false);
    }

    public function isEnabled(): bool
    {
        return $this->config->marketplaceIsEnabled();
    }

    public function getName(): string
    {
        return self::BASE;
    }

    public function buildForm(FormBuilderInterface &$builder, array $options, array $data): void
    {
        $this->addStandardFormFields(self::BASE, self::PACKAGES, $builder, $data, false);
    }
}
