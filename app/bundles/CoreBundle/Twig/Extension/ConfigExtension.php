<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use Mautic\CoreBundle\Twig\Helper\ConfigHelper;

class ConfigExtension
{
    public function __construct(
        private readonly ConfigHelper $configHelper,
    ) {
    }

    /**
     * @param mixed $default
     *
     * @return mixed
     */
    #[\Twig\Attribute\AsTwigFunction(name: 'configGetParameter')]
    public function get(string $name, $default = null)
    {
        return $this->configHelper->get($name, $default);
    }
}
