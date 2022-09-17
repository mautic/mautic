<?php

namespace Mautic\EmailBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class EmailTransportPass.
 */
class EmailTransportPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $definition     = $container->getDefinition('mautic.email.transport_wrapper');
        $taggedServices = $container->findTaggedServiceIds('mautic.email.transport_extension');
        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('addTransportExtension', [new Reference($id)]);
        }
    }
}
