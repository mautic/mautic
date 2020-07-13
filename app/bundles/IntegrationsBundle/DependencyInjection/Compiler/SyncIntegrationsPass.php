<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\IntegrationsBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class SyncIntegrationsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $taggedServices         = $container->findTaggedServiceIds('mautic.sync_integration');
        $syncIntegrationsHelper = $container->findDefinition('mautic.integrations.helper.sync_integrations');

        foreach ($taggedServices as $id => $tags) {
            $syncIntegrationsHelper->addMethodCall('addIntegration', [new Reference($id)]);
        }

        $taggedServices   = $container->findTaggedServiceIds('mautic.sync.notification_handler');
        $handlerContainer = $container->findDefinition('mautic.integrations.sync.notification.handler_container');

        foreach ($taggedServices as $id => $tags) {
            $handlerContainer->addMethodCall('registerHandler', [new Reference($id)]);
        }
    }
}
