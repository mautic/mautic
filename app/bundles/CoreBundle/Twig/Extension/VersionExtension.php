<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use Mautic\CoreBundle\Helper\AppVersion;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class VersionExtension extends AbstractExtension
{
    public function __construct(
        private readonly AppVersion $appVersion,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('mauticAppVersion', $this->getVersion(...)),
        ];
    }

    public function getVersion(): string
    {
        return $this->appVersion->getVersion();
    }
}
