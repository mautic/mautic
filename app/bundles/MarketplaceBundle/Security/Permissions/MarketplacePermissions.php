<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Security\Permissions;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;
use Mautic\MarketplaceBundle\Service\Config;
use Symfony\Component\Form\FormBuilderInterface;

final class MarketplacePermissions extends AbstractPermissions
{
    public const string BASE                 = 'marketplace';

    public const string PACKAGES             = 'packages';

    public const string CAN_VIEW_PACKAGES    = self::BASE.':'.self::PACKAGES.':view';

    public const string CAN_INSTALL_PACKAGES = self::BASE.':'.self::PACKAGES.':create';

    public const string CAN_REMOVE_PACKAGES  = self::BASE.':'.self::PACKAGES.':remove';

    public function __construct(
        CoreParametersHelper $coreParametersHelper,
        private readonly Config $config,
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
