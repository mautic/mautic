<?php

namespace MauticPlugin\MauticIntegrationsBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class AuthPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('mautic.integrations.auth.factory')) {
            return;
        }

        $authFactory = $container->getDefinition('mautic.integrations.auth.factory');

        foreach ($container->findTaggedServiceIds('mautic.integrations.auth.provider') as $id => $tags) {
            $authFactory->addMethodCall('registerAuthProvider', [new Reference($id)]);
        }
    }
}
