<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\IntegrationsBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ConfigIntegrationPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $taggedServices     = $container->findTaggedServiceIds('mautic.config_integration');
        $integrationsHelper = $container->findDefinition('mautic.integrations.helper.config_integrations');

        foreach ($taggedServices as $id => $tags) {
            $integrationsHelper->addMethodCall('addIntegration', [new Reference($id)]);
        }
    }
}
