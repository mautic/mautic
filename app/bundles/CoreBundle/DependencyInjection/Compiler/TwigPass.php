<?php

namespace Mautic\CoreBundle\DependencyInjection\Compiler;

use Mautic\CoreBundle\Twig\Helper\AssetsHelper;
use Mautic\CoreBundle\Twig\Helper\SlotsHelper;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class TwigPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if ($container->hasDefinition(AssetsHelper::class)) {
            $container->getDefinition(AssetsHelper::class)
                ->addMethodCall('setPathsHelper', [new Reference('mautic.helper.paths')])
                ->addMethodCall('setAssetHelper', [new Reference('mautic.helper.assetgeneration')])
                ->addMethodCall('setBuilderIntegrationsHelper', [new Reference('mautic.integrations.helper.builder_integrations')])
                ->addMethodCall('setInstallService', [new Reference('mautic.install.service')])
                ->addMethodCall('setSiteUrl', ['%mautic.site_url%'])
                ->addMethodCall('setVersion', ['%mautic.secret_key%', MAUTIC_VERSION]);
        }

        if ($container->hasDefinition('twig.helper.slots')) {
            $container->getDefinition('twig.helper.slots')
                ->setClass(SlotsHelper::class)
                ->setPublic(true);
        }
    }
}
