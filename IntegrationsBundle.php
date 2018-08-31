<?php

namespace MauticPlugin\IntegrationsBundle;

use Mautic\PluginBundle\Bundle\PluginBundleBase;
use MauticPlugin\IntegrationsBundle\DependencyInjection\Compiler\AuthPass;
use MauticPlugin\IntegrationsBundle\DependencyInjection\Compiler\AuthenticationIntegrationPass;
use MauticPlugin\IntegrationsBundle\DependencyInjection\Compiler\BasicIntegrationPass;
use MauticPlugin\IntegrationsBundle\DependencyInjection\Compiler\DispatcherIntegrationPass;
use MauticPlugin\IntegrationsBundle\DependencyInjection\Compiler\EncryptionIntegrationPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class IntegrationsBundle
 *
 * @package MauticPlugin\IntegrationsBundle
 */
class IntegrationsBundle extends PluginBundleBase
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
