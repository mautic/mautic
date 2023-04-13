<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use DeviceDetector\Parser\Device\AbstractDeviceParser;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class DeviceExtension extends AbstractExtension
{
    public function getFunctions()
    {
        return [
            new TwigFunction('deviceGetFullName', [AbstractDeviceParser::class, 'getFullName']),
        ];
    }
}
