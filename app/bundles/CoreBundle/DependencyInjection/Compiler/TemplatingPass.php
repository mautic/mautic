<?php

namespace Mautic\CoreBundle\DependencyInjection\Compiler;

use Mautic\CoreBundle\Twig\Helper\AssetsHelper;
use Mautic\CoreBundle\Twig\Helper\FormHelper;
use Mautic\CoreBundle\Twig\Helper\SlotsHelper;
use Mautic\CoreBundle\Twig\Helper\TranslatorHelper;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class TemplatingPass.
 */
class TemplatingPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition('templating')) {
            return;
        }

        if ($container->hasDefinition(AssetsHelper::class)) {
            $container->getDefinition(AssetsHelper::class)
                ->addMethodCall('setPathsHelper', [new Reference('mautic.helper.paths')])
                ->addMethodCall('setAssetHelper', [new Reference('mautic.helper.assetgeneration')])
                ->addMethodCall('setBuilderIntegrationsHelper', [new Reference('mautic.integrations.helper.builder_integrations')])
                ->addMethodCall('setInstallService', [new Reference('mautic.install.service')])
                ->addMethodCall('setSiteUrl', ['%mautic.site_url%'])
                ->addMethodCall('setVersion', ['%mautic.secret_key%', MAUTIC_VERSION]);
        }

        if ($container->hasDefinition('templating.helper.slots')) {
            $container->getDefinition('templating.helper.slots')
                ->setClass(SlotsHelper::class)
                ->setPublic(true);
        }

        if ($container->hasDefinition('templating.helper.form')) {
            $container->getDefinition('templating.helper.form')
                ->setClass(FormHelper::class)
                ->setPublic(true);
        }

        if ($container->hasDefinition('templating.helper.translator')) {
            $container->getDefinition('templating.helper.translator')
                ->setClass(TranslatorHelper::class)
                ->setPublic(true);
        }
    }
}
