<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use Mautic\CoreBundle\Helper\AppVersion;

class VersionExtension
{
    public function __construct(
        private AppVersion $appVersion,
    ) {
    }

    #[\Twig\Attribute\AsTwigFunction('mauticAppVersion')]
    public function getVersion(): string
    {
        return $this->appVersion->getVersion();
    }
}
