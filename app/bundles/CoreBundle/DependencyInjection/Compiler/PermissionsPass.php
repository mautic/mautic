<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class PermissionsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $corePermissions = $container->findDefinition('mautic.security');

        foreach ($container->findTaggedServiceIds('mautic.permissions') as $id => $tags) {
            $permissionObject = $container->findDefinition($id);
            $corePermissions->addMethodCall('setPermissionObject', [$permissionObject]);
        }
    }
}
