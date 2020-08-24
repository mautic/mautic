<?php

declare(strict_types=1);

namespace Mautic\ApiBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class SerializerPass implements CompilerPassInterface
{
    /**
     * Replaces the available metadata drivers (yaml, xml, and annotation)
     * with our metadata driver, as we do not use any of those. There's
     * currently no other way that I can find to get our driver into the
     * chain in front of the rest.
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition('jms_serializer.metadata.chain_driver')) {
            $definition = $container->getDefinition('jms_serializer.metadata.chain_driver');
            $drivers    = $definition->getArgument(0);

            array_unshift($drivers, new Reference('jms_serializer.metadata.api_metadata_driver'));

            $definition->replaceArgument(0, $drivers);
        }
    }
}
