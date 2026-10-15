<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\DependencyInjection\Compiler;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class DbalPass implements CompilerPassInterface
{
    /**
     * Allows result caching with DBAL using the same configuration as the orm if provided and enabled.
     *
     * See config_prod.php
     */
    public function process(ContainerBuilder $container): void
    {
        if ($container->hasDefinition('doctrine.orm.default_configuration')) {
            // Keep AUTO identifiers consistent with installer-created SERIAL columns after a cold boot.
            $container->getDefinition('doctrine.orm.default_configuration')
                ->addMethodCall('setIdentityGenerationPreferences', [[PostgreSQLPlatform::class => ClassMetadata::GENERATOR_TYPE_IDENTITY]]);
        }

        $id = 'doctrine.dbal.default_connection.configuration';

        if ($container->hasDefinition($id)) {
            $container
                ->getDefinition($id)
                ->addMethodCall('setResultCache', [new Reference('doctrine.orm.default_result_cache')]);
        }
    }
}
