<?php

namespace Mautic\CoreBundle\DependencyInjection\Compiler;

use Mautic\CoreBundle\Shortener\Shortener;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ShortenerServicePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(Shortener::class)) {
            return;
        }

        $storageDef = $container->findDefinition(Shortener::class);
        foreach ($container->findTaggedServiceIds('mautic.shortener.service') as $id => $tags) {
            $storageDef->addMethodCall('addService', [new Reference($id)]);
        }
    }
}
