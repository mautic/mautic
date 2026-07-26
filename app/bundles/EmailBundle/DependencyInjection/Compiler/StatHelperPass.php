<?php

namespace Mautic\EmailBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class StatHelperPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        // replace manual tagging with autoconfiguration
        $container->registerForAutoconfiguration(\Mautic\EmailBundle\Stats\Helper\StatHelperInterface::class)
            ->addTag('mautic.email_stat_helper');

        //        $definition     = $container->getDefinition('mautic.email.stats.helper_container');
        //        $taggedServices = $container->findTaggedServiceIds('mautic.email_stat_helper');
        //        foreach ($taggedServices as $id => $tags) {
        //            $definition->addMethodCall('addHelper', [
        //                new Reference($id),
        //            ]);
        //        }
    }
}
