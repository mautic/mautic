<?php

namespace MauticPlugin\MauticIntegrationsBundle;

use Mautic\PluginBundle\Bundle\PluginBundleBase;
use MauticPlugin\MauticIntegrationsBundle\DependencyInjection\Compiler\AuthPass;
use MauticPlugin\MauticIntegrationsBundle\DependencyInjection\Compiler\AuthenticationIntegrationPass;
use MauticPlugin\MauticIntegrationsBundle\DependencyInjection\Compiler\BasicIntegrationPass;
use MauticPlugin\MauticIntegrationsBundle\DependencyInjection\Compiler\DispatcherIntegrationPass;
use MauticPlugin\MauticIntegrationsBundle\DependencyInjection\Compiler\EncryptionIntegrationPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class MauticIntegrationsBundle
 *
 * @package MauticPlugin\MauticIntegrationsBundle
 */
class MauticIntegrationsBundle extends PluginBundleBase
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new AuthPass());
        $container->addCompilerPass(new BasicIntegrationPass());
        $container->addCompilerPass(new EncryptionIntegrationPass());
        $container->addCompilerPass(new DispatcherIntegrationPass());
        $container->addCompilerPass(new AuthenticationIntegrationPass());
    }
}
