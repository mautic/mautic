<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Helper;

use Symfony\Component\DependencyInjection\Loader\Configurator\DefaultsConfigurator;

final class ServiceDeprecator
{
    private const PACKAGE = 'mautic/mautic';

    public function __construct(private DefaultsConfigurator $configurator)
    {
    }

    public function setDeprecatedService(string $fcqn, string $message = '', string $version = '5.1'): void
    {
        $this->configurator->get($fcqn)
            ->deprecate(self::PACKAGE, $version, 'The "%service_id%" service is deprecated. '.$message);
    }

    public function setDeprecatedAlias(string $alias, string $fqcn, string $message = '', string $version = '5.1'): void
    {
        $this->configurator->alias($alias, $fqcn)
            ->deprecate(self::PACKAGE, $version, 'The "%alias_id%" service alias is deprecated. Use FQCN instead. '.$message);
    }
}
