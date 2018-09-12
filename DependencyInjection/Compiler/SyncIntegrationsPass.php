<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class SyncIntegrationsPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $taggedServices         = $container->findTaggedServiceIds('mautic.sync_integration');
        $syncIntegrationsHelper = $container->findDefinition('mautic.integrations.helper.sync_integrations');

        foreach ($taggedServices as $id => $tags) {
            $syncIntegrationsHelper->addMethodCall('addIntegration', [new Reference($id)]);
        }
    }
}