<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Templating\Twig\Extension;

use DeviceDetector\Parser\Device\AbstractDeviceParser;

class DeviceExtension extends AbstractExtension
{
    public function getFunctions()
    {
        return [
            new TwigFunction('deviceGetFullName', [AbstractDeviceParser::class, 'getFullName']),
        ];
    }
}
