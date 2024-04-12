<?php

namespace Mautic\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ConfiguratorPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('mautic.configurator')) {
            return;
        }

        $configuratorDef = $container->findDefinition('mautic.configurator');

        foreach ($container->findTaggedServiceIds('mautic.configurator.step') as $id => $tags) {
            $priority = $tags[0]['priority'] ?? 0;
            $configuratorDef->addMethodCall('addStep', [new Reference($id), $priority]);
        }
    }
}
