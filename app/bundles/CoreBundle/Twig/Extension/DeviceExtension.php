<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use DeviceDetector\Parser\Device\AbstractDeviceParser;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class DeviceExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('deviceGetFullName', AbstractDeviceParser::getFullName(...)),
        ];
    }
}
