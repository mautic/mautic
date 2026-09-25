<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class PermissionsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $corePermissions = $container->findDefinition('mautic.security');

        foreach (array_keys($container->findTaggedServiceIds('mautic.permissions')) as $id) {
            $permissionObject = $container->findDefinition($id);
            $corePermissions->addMethodCall('setPermissionObject', [$permissionObject]);
        }
    }
}
