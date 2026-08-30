<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Helper;

use Mautic\Middleware\ConfigAwareTrait;

final class ParamsLoaderHelper
{
    use ConfigAwareTrait;

    private array $parameters = [];

    /**
     * Get parameters for static method.
     */
    public function getParameters(): array
    {
        if ([] === $this->parameters) {
            $this->parameters = $this->getConfig();
        }

        return $this->parameters;
    }
}
