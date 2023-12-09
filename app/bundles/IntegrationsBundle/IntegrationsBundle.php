<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle;

use Mautic\IntegrationsBundle\Bundle\AbstractPluginBundle;
use Mautic\IntegrationsBundle\DependencyInjection\Compiler\AuthenticationIntegrationPass;
use Mautic\IntegrationsBundle\DependencyInjection\Compiler\BuilderIntegrationPass;
use Mautic\IntegrationsBundle\DependencyInjection\Compiler\ConfigIntegrationPass;
use Mautic\IntegrationsBundle\DependencyInjection\Compiler\IntegrationsPass;
use Mautic\IntegrationsBundle\DependencyInjection\Compiler\SyncIntegrationsPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class IntegrationsBundle extends AbstractPluginBundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new IntegrationsPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 0);
        $container->addCompilerPass(new AuthenticationIntegrationPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 0);
        $container->addCompilerPass(new SyncIntegrationsPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 0);
        $container->addCompilerPass(new ConfigIntegrationPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 0);
        $container->addCompilerPass(new BuilderIntegrationPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 0);
    }
}
