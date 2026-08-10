<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use Mautic\CoreBundle\Helper\AppVersion;
use Twig\Attribute\AsTwigFunction;

final readonly class VersionExtension
{
    public function __construct(
        private AppVersion $appVersion,
    ) {
    }

    #[AsTwigFunction(name: 'mauticAppVersion')]
    public function getVersion(): string
    {
        return $this->appVersion->getVersion();
    }
}
