<?php

namespace Mautic\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ShortenerServicePass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('mautic.shortener')) {
            return;
        }

        $storageDef = $container->findDefinition('mautic.shortener');
        foreach ($container->findTaggedServiceIds('mautic.shortener.service') as $id => $tags) {
            $storageDef->addMethodCall('addService', [$id, new Reference($id)]);
        }
    }
}
