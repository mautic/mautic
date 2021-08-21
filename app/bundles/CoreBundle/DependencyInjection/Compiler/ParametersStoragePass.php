<?php

/*
 * @copyright   2021 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ParametersStoragePass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('mautic.parameters.storage')) {
            return;
        }

        $storageDef = $container->findDefinition('mautic.parameters.storage');
        foreach ($container->findTaggedServiceIds('mautic.parameters.storage.service') as $id => $tags) {
            $storageDef->addMethodCall('addStorage', [$id, new Reference($id)]);
        }
    }
}
