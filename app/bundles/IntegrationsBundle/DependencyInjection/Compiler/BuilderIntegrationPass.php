<?php

declare(strict_types=1);

/*
 * @copyright   2021 Mautic. All rights reserved
 * @author      Mautic Contributors.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\IntegrationsBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class BuilderIntegrationPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $taggedServices     = $container->findTaggedServiceIds('mautic.builder_integration');
        $integrationsHelper = $container->findDefinition('mautic.integrations.helper.builder_integrations');

        foreach ($taggedServices as $id => $tags) {
            $integrationsHelper->addMethodCall('addIntegration', [new Reference($id)]);
        }
    }
}
