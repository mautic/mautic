<?php

namespace Mautic\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class ConfiguratorPass.
 */
class ConfiguratorPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('mautic.configurator')) {
            return;
        }

        $configuratorDef = $container->findDefinition('mautic.configurator');

        foreach ($container->findTaggedServiceIds('mautic.configurator.step') as $id => $tags) {
            $priority = isset($tags[0]['priority']) ? $tags[0]['priority'] : 0;
            $configuratorDef->addMethodCall('addStep', [new Reference($id), $priority]);
        }
    }
}
