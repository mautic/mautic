<?php

namespace Mautic\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class UpdateStepPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('mautic.update.step_provider')) {
            return;
        }

        $definition     = $container->getDefinition('mautic.update.step_provider');
        $taggedServices = $container->findTaggedServiceIds('mautic.update_step');
        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('addStep', [new Reference($id)]);
        }
    }
}
