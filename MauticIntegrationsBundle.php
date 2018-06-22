<?php

namespace MauticPlugin\MauticIntegrationsBundle;

use MauticPlugin\MauticIntegrationsBundle\DependencyInjection\Compiler\AuthenticationIntegrationPass;
use MauticPlugin\MauticIntegrationsBundle\DependencyInjection\Compiler\BasicIntegrationPass;
use MauticPlugin\MauticIntegrationsBundle\DependencyInjection\Compiler\DispatcherIntegrationPass;
use MauticPlugin\MauticIntegrationsBundle\DependencyInjection\Compiler\EncryptionIntegrationPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class MauticIntegrationsBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new BasicIntegrationPass());
        $container->addCompilerPass(new EncryptionIntegrationPass());
        $container->addCompilerPass(new DispatcherIntegrationPass());
        $container->addCompilerPass(new AuthenticationIntegrationPass());
    }
}
