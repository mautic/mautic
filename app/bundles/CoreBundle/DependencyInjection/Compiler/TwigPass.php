<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\DependencyInjection\Compiler;

use Mautic\CoreBundle\Helper\AssetGenerationHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Twig\Helper\AssetsHelper;
use Mautic\IntegrationsBundle\Helper\BuilderIntegrationsHelper;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class TwigPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if ($container->hasDefinition(AssetsHelper::class)) {
            $container->getDefinition(AssetsHelper::class)
                ->addMethodCall('setPathsHelper', [new Reference(PathsHelper::class)])
                ->addMethodCall('setAssetHelper', [new Reference(AssetGenerationHelper::class)])
                ->addMethodCall('setBuilderIntegrationsHelper', [new Reference(BuilderIntegrationsHelper::class)])
                ->addMethodCall('setInstallService', [new Reference('mautic.install.service')])
                ->addMethodCall('setSiteUrl', ['%mautic.site_url%'])
                ->addMethodCall('setVersion', ['%mautic.secret_key%', MAUTIC_VERSION]);
        }
    }
}
