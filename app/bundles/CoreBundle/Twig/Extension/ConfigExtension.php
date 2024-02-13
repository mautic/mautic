<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use Mautic\CoreBundle\Twig\Helper\ConfigHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ConfigExtension extends AbstractExtension
{
    public function __construct(
        private ConfigHelper $configHelper
    ) {
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('configGetParameter', [$this, 'get']),
        ];
    }

    /**
     * @param mixed $default
     *
     * @return mixed
     */
    public function get(string $name, $default = null)
    {
        return $this->configHelper->get($name, $default);
    }
}
