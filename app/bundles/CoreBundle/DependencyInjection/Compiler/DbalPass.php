<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class DbalPass implements CompilerPassInterface
{
    /**
     * Allows result caching with DBAL using the same configuration as the orm if provided and enabled.
     *
     * See config_prod.php
     */
    public function process(ContainerBuilder $container): void
    {
        $id = 'doctrine.dbal.default_connection.configuration';

        if ($container->hasDefinition($id)) {
            $container
                ->getDefinition($id)
                ->addMethodCall('setResultCache', [new Reference('doctrine.orm.default_result_cache')]);
        }
    }
}
